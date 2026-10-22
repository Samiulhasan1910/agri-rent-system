// Date limits are rendered by PHP using the application's Bangladesh timezone.

document.addEventListener('click', (event) => {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar || !sidebar.classList.contains('open')) return;
  if (!sidebar.contains(event.target) && !event.target.closest('.menu-button')) sidebar.classList.remove('open');
});

const networkStatus = document.getElementById('networkStatus');
function updateNetworkStatus() {
  if (!networkStatus) return;
  const online = navigator.onLine;
  const bn = document.body.dataset.language === 'Bangla';
  networkStatus.textContent = online ? (bn ? 'অনলাইন' : 'Online') : (bn ? 'অফলাইন' : 'Offline');
  networkStatus.classList.toggle('offline', !online);
}
window.addEventListener('online', updateNetworkStatus);
window.addEventListener('offline', updateNetworkStatus);
updateNetworkStatus();

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('service-worker.js').catch(() => {}));
}

if (document.body.dataset.language === 'Bangla') {
  const bangla = {
    'Dashboard':'ড্যাশবোর্ড','Browse Equipment':'সরঞ্জাম খুঁজুন','My Bookings':'আমার বুকিং',
    'Wishlist':'পছন্দের তালিকা','Emergency Request':'জরুরি অনুরোধ','Rental Management':'ভাড়া ব্যবস্থাপনা',
    'Manage Equipment':'সরঞ্জাম ব্যবস্থাপনা','Deliveries':'ডেলিভারি','Maintenance':'রক্ষণাবেক্ষণ',
    'Inspections':'পরিদর্শন','User Verification':'ব্যবহারকারী যাচাই','Categories':'ক্যাটাগরি',
    'Reports':'রিপোর্ট','Support Tickets':'সহায়তা টিকিট','Messages':'বার্তা',
    'Notifications':'নোটিফিকেশন','Profile':'প্রোফাইল','Logout':'লগআউট','Online':'অনলাইন',
    'Offline':'অফলাইন','WELCOME BACK':'স্বাগতম','ACCOUNT SETTINGS':'অ্যাকাউন্ট সেটিংস',
    'Edit profile':'প্রোফাইল সম্পাদনা','Update profile':'প্রোফাইল আপডেট','Full name':'পূর্ণ নাম',
    'Phone':'ফোন','Language':'ভাষা','Address':'ঠিকানা','Account status':'অ্যাকাউন্ট অবস্থা',
    'Member since':'সদস্য হওয়ার তারিখ','Delete account':'অ্যাকাউন্ট মুছুন'
  };
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);
  nodes.forEach((node) => {
    const raw = node.nodeValue;
    const key = raw.trim();
    if (bangla[key]) node.nodeValue = raw.replace(key, bangla[key]);
  });
}

document.querySelectorAll('[data-rental-quote]').forEach((form) => {
  const start = form.elements.start_date;
  const end = form.elements.end_date;
  const result = form.querySelector('[data-quote-result]');
  const submit = form.querySelector('[data-booking-submit]');
  const bangla = document.body.dataset.language === 'Bangla';
  const message = (en, bn) => bangla ? bn : en;
  const currency = new Intl.NumberFormat(bangla ? 'bn-BD' : 'en-BD', { style: 'currency', currency: 'BDT', maximumFractionDigits: 2 });
  let request = null;
  let revision = 0;

  async function updateQuote() {
    const current = ++revision;
    if (request) request.abort();
    end.min = start.value || start.min;
    end.setCustomValidity('');
    result.classList.remove('is-error');
    if (!start.value || !end.value) {
      result.textContent = message('Choose both dates to see the total rental cost and available units.', 'মোট ভাড়া ও খালি ইউনিট দেখতে দুটি তারিখ বেছে নিন।');
      submit.disabled = false;
      return;
    }
    if (end.value < start.value) {
      const error = message('End date must be on or after the start date.', 'শেষের তারিখ শুরুর তারিখের আগে হতে পারবে না।');
      end.setCustomValidity(error);
      result.textContent = error;
      result.classList.add('is-error');
      submit.disabled = true;
      return;
    }
    if (!start.validity.valid || !end.validity.valid) {
      result.textContent = message('Choose a valid current or future rental period.', 'আজকের বা ভবিষ্যতের সঠিক তারিখ বেছে নিন।');
      submit.disabled = true;
      return;
    }
    submit.disabled = true;
    result.textContent = message('Checking availability…', 'খালি ইউনিট দেখা হচ্ছে…');
    request = new AbortController();
    const timeout = setTimeout(() => { if (current === revision) request.abort(); }, 10000);
    try {
      const params = new URLSearchParams({ equipment_id: form.elements.equipment_id.value, start_date: start.value, end_date: end.value });
      const response = await fetch('quote.php?' + params, { signal: request.signal, cache: 'no-store', headers: { Accept: 'application/json' } });
      const quote = await response.json();
      if (current !== revision) return;
      if (!response.ok) {
        result.textContent = quote.error || message('Unable to check these dates.', 'এই তারিখ যাচাই করা যায়নি।');
        result.classList.add('is-error');
        submit.disabled = response.status < 500;
        return;
      }
      result.replaceChildren();
      const cost = document.createElement('strong');
      cost.textContent = bangla
        ? quote.days + ' দিন × ' + currency.format(quote.price_per_day) + ' = ' + currency.format(quote.total)
        : quote.days + (quote.days === 1 ? ' day' : ' days') + ' × ' + currency.format(quote.price_per_day) + ' = ' + currency.format(quote.total);
      const availability = document.createElement('span');
      availability.textContent = quote.available_units > 0
        ? (bangla ? quote.available_units + 'টি ইউনিট এই সময় খালি আছে।' : quote.available_units + ' unit' + (quote.available_units === 1 ? '' : 's') + ' available for this period.')
        : message('Fully booked or unavailable. Please choose other dates.', 'এই সময়ে সরঞ্জাম খালি নেই। অন্য তারিখ বেছে নিন।');
      result.append(cost, availability);
      result.classList.toggle('is-error', quote.available_units === 0);
      submit.disabled = quote.available_units === 0;
    } catch (error) {
      if (current !== revision) return;
      result.textContent = message('Live estimate unavailable. You can submit your request; availability and price will be checked on the server.', 'এখন হিসাব দেখা যাচ্ছে না। অনুরোধ জমা দিলে ভাড়া ও খালি ইউনিট যাচাই করা হবে।');
      result.classList.add('is-error');
      submit.disabled = false;
    } finally {
      clearTimeout(timeout);
    }
  }
  start.addEventListener('change', updateQuote);
  end.addEventListener('change', updateQuote);
  updateQuote();
});
