<section class="hero" id="top">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><?= icon('sprout', 'icon-xs') ?> <?= e(t('Smart farming starts here')) ?></span>
            <h1><?= e(t('Rent Agricultural')) ?> <span><?= e(t('Equipment Easily')) ?></span></h1>
            <p><?= e(t('Access a wide range of tractors, harvesters, pumps, and farming tools from trusted owners. Please register or log in to search, book, and manage your rentals.')) ?></p>
            <div class="hero-actions">
                <?php if (Auth::check()): ?>
                    <a class="btn btn-primary btn-lg" href="<?= e(url('equipment/index')) ?>"><?= icon('search') ?><?= e(t('Browse Equipment')) ?></a>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('dashboard/index')) ?>"><?= icon('home') ?><?= e(t('Go to Dashboard')) ?></a>
                <?php else: ?>
                    <a class="btn btn-primary btn-lg" href="<?= e(url('auth/login')) ?>"><?= icon('login') ?><?= e(t('Login to Continue')) ?></a>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('auth/register')) ?>"><?= icon('user') ?><?= e(t('Create Account')) ?></a>
                <?php endif; ?>
            </div>
            <ul class="trust-row">
                <li><?= icon('shield-check') ?><?= e(t('Verified equipment')) ?></li>
                <li><?= icon('lock') ?><?= e(t('Secure payment')) ?></li>
                <li><?= icon('truck') ?><?= e(t('Delivery tracking')) ?></li>
            </ul>
        </div>
        <div class="hero-visual">
            <img src="assets/img/hero-tractor.jpg" alt="<?= e(t('Tractor working in a green field')) ?>" onerror="this.onerror=null;this.src='assets/img/equipment/tractor.svg'">
            <div class="floating-card">
                <span class="fc-icon"><?= icon('calendar-check') ?></span>
                <div><strong><?= (int)$stats['equipment'] ?>+ <?= e(t('machines')) ?></strong><small><?= e(t('Available for daily rental')) ?></small></div>
            </div>
            <div class="floating-card fc-bottom">
                <span class="fc-icon fc-amber"><?= icon('star') ?></span>
                <div><strong>4.6 / 5</strong><small><?= e(t('Average farmer rating')) ?></small></div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="how">
    <div class="container">
        <div class="section-head center">
            <span class="eyebrow"><?= e(t('How It Works')) ?></span>
            <h2><?= e(t('From search to field in three simple steps')) ?></h2>
        </div>
        <div class="steps">
            <article class="step"><span class="step-no">1</span><span class="step-icon"><?= icon('user') ?></span><div><h3><?= e(t('Create Account')) ?></h3><p><?= e(t('Register with your details in just a few simple steps.')) ?></p></div></article>
            <span class="step-arrow"><?= icon('arrow-right') ?></span>
            <article class="step"><span class="step-no">2</span><span class="step-icon"><?= icon('search') ?></span><div><h3><?= e(t('Login & Search')) ?></h3><p><?= e(t('Log in to explore available equipment near you.')) ?></p></div></article>
            <span class="step-arrow"><?= icon('arrow-right') ?></span>
            <article class="step"><span class="step-no">3</span><span class="step-icon"><?= icon('credit-card') ?></span><div><h3><?= e(t('Book & Pay')) ?></h3><p><?= e(t('Book the equipment you need and make secure payment.')) ?></p></div></article>
        </div>
        <div class="feature-strip">
            <article><span class="fi"><?= icon('shield-check') ?></span><div><h4><?= e(t('Trusted Equipment')) ?></h4><p><?= e(t('Quality equipment from verified owners you can rely on.')) ?></p></div></article>
            <article><span class="fi"><?= icon('calendar-check') ?></span><div><h4><?= e(t('Easy Booking')) ?></h4><p><?= e(t('Simple booking process saves your time and effort.')) ?></p></div></article>
            <article><span class="fi"><?= icon('lock') ?></span><div><h4><?= e(t('Secure Payment')) ?></h4><p><?= e(t('Your payments are safe with trusted and encrypted methods.')) ?></p></div></article>
        </div>
    </div>
</section>

<section class="section section-tint">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow"><?= e(t('Browse by Category')) ?></span><h2><?= e(t('Machines for every farming stage')) ?></h2></div>
            <a class="btn btn-outline" href="<?= e(url('equipment/index')) ?>"><?= e(t('View All')) ?><?= icon('arrow-right') ?></a>
        </div>
        <div class="category-row">
            <?php foreach ($categories as $c): ?>
                <a class="category-tile" href="<?= e(url('equipment/index', ['category' => $c['id']])) ?>">
                    <img src="assets/img/equipment/<?= e($c['icon']) ?>.svg" alt="" loading="lazy">
                    <strong><?= e(t($c['name'])) ?></strong><small><?= (int)$c['total'] ?> <?= e(t('equipment')) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow"><?= e(t('Featured Equipment')) ?></span><h2><?= e(t('Top rated by farmers')) ?></h2></div>
            <a class="btn btn-outline" href="<?= e(url(Auth::check() ? 'equipment/index' : 'auth/login')) ?>"><?= e(t('See all equipment')) ?><?= icon('arrow-right') ?></a>
        </div>
        <div class="equipment-grid">
            <?php foreach ($featured as $e) { $free = null; $heart = false; include __DIR__ . '/../partials/eq_card.php'; } ?>
        </div>
    </div>
</section>

<section class="stats-band">
    <div class="container stats-band-grid">
        <div><strong><?= (int)$stats['equipment'] ?>+</strong><span><?= e(t('Equipment listed')) ?></span></div>
        <div><strong><?= (int)$stats['farmers'] ?>+</strong><span><?= e(t('Active farmers')) ?></span></div>
        <div><strong><?= (int)$stats['rentals'] ?>+</strong><span><?= e(t('Completed rentals')) ?></span></div>
        <div><strong><?= (int)$stats['districts'] ?></strong><span><?= e(t('Districts covered')) ?></span></div>
    </div>
</section>

<section class="section" id="about">
    <div class="container about-grid">
        <div>
            <span class="eyebrow"><?= e(t('About AgriRent')) ?></span>
            <h2><?= e(t('A digital hub for agricultural equipment rental')) ?></h2>
            <p><?= e(t('Small and medium farmers often cannot afford expensive machinery. AgriRent connects them with verified equipment owners, while employees manage delivery, maintenance and inspections and administrators supervise the whole system.')) ?></p>
            <ul class="check-list">
                <li><?= icon('check-circle') ?><?= e(t('Smart equipment suggestions by crop, season and farm size')) ?></li>
                <li><?= icon('check-circle') ?><?= e(t('Emergency requests matched to the nearest available machine')) ?></li>
                <li><?= icon('check-circle') ?><?= e(t('Seasonal pricing, coupons and secure bKash, Nagad or card payment')) ?></li>
                <li><?= icon('check-circle') ?><?= e(t('Full Bangla and English interface with offline support')) ?></li>
            </ul>
        </div>
        <div class="about-cards">
            <article><?= icon('sparkles') ?><h4><?= e(t('Smart Match')) ?></h4><p><?= e(t('Tell us your crop and season; we rank the best machines.')) ?></p></article>
            <article><?= icon('siren') ?><h4><?= e(t('Emergency Request')) ?></h4><p><?= e(t('GPS finds the closest available equipment in seconds.')) ?></p></article>
            <article><?= icon('truck') ?><h4><?= e(t('Delivery Tracking')) ?></h4><p><?= e(t('Follow every delivery and return step by step.')) ?></p></article>
            <article><?= icon('wrench') ?><h4><?= e(t('Health Monitoring')) ?></h4><p><?= e(t('Usage-based maintenance alerts keep machines safe.')) ?></p></article>
        </div>
    </div>
</section>

<section class="cta">
    <div class="container cta-inner">
        <div><h2><?= e(t('Ready to grow better?')) ?></h2><p><?= e(t('Join thousands of farmers who trust AgriRent to rent quality equipment easily, safely and securely.')) ?></p></div>
        <a class="btn btn-light btn-lg" href="<?= e(url(Auth::check() ? 'equipment/index' : 'auth/register')) ?>"><?= e(Auth::check() ? t('Browse Equipment') : t('Get Started')) ?><?= icon('arrow-right') ?></a>
    </div>
</section>
