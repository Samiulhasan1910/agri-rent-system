<aside class="auth-aside">
    <h2><?= $asideHeading ?? e(t('Rent Quality Equipment.')) . '<br><span>' . e(t('Grow Better.')) . '</span>' ?></h2>
    <p><?= e($asideText ?? t('Join thousands of farmers who trust AgriRent to rent quality equipment easily, safely and securely.')) ?></p>
    <ul class="auth-features">
        <li><span><?= icon('shield-check') ?></span><div><strong><?= e(t('Trusted & Verified')) ?></strong><small><?= e(t('All equipment and owners are verified for your safety.')) ?></small></div></li>
        <li><span><?= icon('calendar-check') ?></span><div><strong><?= e(t('Easy Booking')) ?></strong><small><?= e(t('Find, book and rent equipment in just a few clicks.')) ?></small></div></li>
        <li><span><?= icon('lock') ?></span><div><strong><?= e(t('Secure Payment')) ?></strong><small><?= e(t('Your payments are safe with our secure system.')) ?></small></div></li>
    </ul>
    <img class="auth-photo" src="assets/img/hero-tractor.jpg" alt="" loading="lazy" onerror="this.remove()">
</aside>
