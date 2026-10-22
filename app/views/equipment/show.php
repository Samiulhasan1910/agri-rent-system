<?php
$fallback = 'assets/img/equipment/' . e($item['category_icon']) . '.svg';
$isFarmer = $user['role'] === 'farmer';
$verified = $user['verification_status'] === 'approved';
?>
<nav class="crumbs"><a href="<?= e(url('equipment/index')) ?>"><?= e(t('Browse Equipment')) ?></a><?= icon('chevron-right', 'icon-xs') ?><a href="<?= e(url('equipment/index', ['category' => $item['category_id']])) ?>"><?= e(t($item['category_name'])) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e($item['name']) ?></span></nav>

<section class="card detail-top">
    <div class="gallery">
        <img class="gallery-main" src="<?= e(equipment_image($item)) ?>" alt="<?= e($item['name']) ?>" data-gallery-main onerror="this.onerror=null;this.src='<?= $fallback ?>'">
        <div class="gallery-thumbs">
            <button type="button" class="active" data-gallery-thumb="<?= e(equipment_image($item)) ?>"><img src="<?= e(equipment_image($item)) ?>" alt="" onerror="this.onerror=null;this.src='<?= $fallback ?>'"></button>
            <button type="button" data-gallery-thumb="<?= $fallback ?>"><img src="<?= $fallback ?>" alt=""></button>
        </div>
    </div>
    <div class="detail-info">
        <div class="detail-title">
            <div>
                <span class="eyebrow"><?= e(t($item['category_name'])) ?></span>
                <h1><?= e($item['name']) ?></h1>
                <?php if ($item['specs']): ?><p class="muted"><?= e($item['specs']) ?></p><?php endif; ?>
            </div>
            <?php if ($isFarmer): ?><button class="heart static <?= $saved ? 'on' : '' ?>" type="button" data-wishlist="<?= (int)$item['id'] ?>" data-saved="<?= $saved ? '1' : '0' ?>" aria-label="<?= e(t('Save to wishlist')) ?>"><?= icon('heart') ?></button><?php endif; ?>
        </div>
        <div class="detail-price">
            <?php if ($price): ?>
                <strong><?= money($price['price']) ?></strong><span>/ <?= e(t('day')) ?></span>
                <?php if ($price['season'] !== 'normal'): ?><small class="season-tag season-<?= e($price['season']) ?>"><?= e(Pricing::label($price['season'])) ?> · <?= e(t('Base price')) ?> <?= money($price['base']) ?></small><?php endif; ?>
            <?php else: ?><span class="text-danger"><?= e(t('Base price is missing for this equipment. Price cannot be calculated.')) ?></span><?php endif; ?>
        </div>
        <div class="rating-line"><?= stars((float)$item['rating']) ?> <small class="muted">(<?= (int)$item['review_count'] ?> <?= e(t('reviews')) ?>)</small></div>
        <dl class="kv">
            <div><dt><?= icon('map-pin', 'icon-xs') ?><?= e(t('Location')) ?></dt><dd><?= e($item['location']) ?></dd></div>
            <div><dt><?= icon('check-circle', 'icon-xs') ?><?= e(t('Availability')) ?></dt><dd><?= $blockReason === null && $freeToday > 0 ? badge('available') . ' <small class="muted">' . e(t(':n of :q units free today', ['n' => $freeToday, 'q' => $item['quantity']])) . '</small>' : badge('unavailable') ?></dd></div>
            <div><dt><?= icon('activity', 'icon-xs') ?><?= e(t('Condition')) ?></dt><dd><?= badge($item['health_status']) ?></dd></div>
            <?php if ($item['owner_name']): ?><div><dt><?= icon('user', 'icon-xs') ?><?= e(t('Owner')) ?></dt><dd><?= e($item['owner_name']) ?></dd></div><?php endif; ?>
            <?php if ($item['owner_phone']): ?><div><dt><?= icon('phone', 'icon-xs') ?><?= e(t('Contact')) ?></dt><dd><a href="tel:<?= e($item['owner_phone']) ?>"><?= e($item['owner_phone']) ?></a></dd></div><?php endif; ?>
        </dl>
        <?php if ($blockReason): ?><div class="alert alert-warning"><?= icon('alert') ?><span><?= e($blockReason) ?></span></div><?php endif; ?>
        <div class="help-box">
            <?= icon('headset') ?><div><strong><?= e(t('Need help with this equipment?')) ?></strong><small><?= e(t('Contact our employee for support.')) ?></small></div>
            <?php if ($contact && $isFarmer): ?><a class="btn btn-outline btn-sm" href="<?= e(url('chat/index', ['with' => $contact['id']])) ?>"><?= e(t('Contact Employee')) ?></a><?php endif; ?>
        </div>
        <?php if (!$isFarmer): ?>
            <div class="row-actions">
                <a class="btn btn-outline" href="<?= e(url('equipment/edit', ['id' => $item['id']])) ?>"><?= icon('edit') ?><?= e(t('Edit')) ?></a>
                <a class="btn btn-outline" href="<?= e(url('maintenance/index', ['equipment_id' => $item['id']])) ?>"><?= icon('wrench') ?><?= e(t('Schedule Maintenance')) ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="grid-2-1">
    <div>
        <section class="card">
            <h2 class="card-title"><?= e(t('Description')) ?></h2>
            <p class="prose"><?= nl2br(e($item['description'])) ?></p>
            <?php $upcoming = array_filter($maintenance, static fn($m) => in_array($m['status'], ['scheduled', 'in_progress'], true)); if ($upcoming): ?>
                <div class="alert alert-info"><?= icon('wrench') ?><span><?= e(t('Maintenance scheduled')) ?>: <?php foreach ($upcoming as $m): ?><b><?= e(fmt_date($m['scheduled_date'])) ?> – <?= e(fmt_date($m['end_date'])) ?></b> <?php endforeach; ?><?= e(t('(not bookable on these dates)')) ?></span></div>
            <?php endif; ?>
        </section>
        <section class="card">
            <div class="card-head"><h2><?= e(t('Farmer Reviews')) ?></h2><span class="muted"><?= stars((float)$item['rating']) ?></span></div>
            <?php foreach ($reviews as $r): ?>
                <article class="review">
                    <?= avatar(['name' => $r['name'], 'profile_photo' => $r['profile_photo']], 'sm') ?>
                    <div><strong><?= e($r['name']) ?></strong> <?= stars((float)$r['rating'], false) ?><small class="muted block"><?= e(fmt_date($r['created_at'])) ?></small><p><?= e($r['review']) ?></p></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$reviews): ?><p class="muted"><?= e(t('No reviews yet.')) ?></p><?php endif; ?>
        </section>
    </div>

    <aside>
        <section class="card book-card" id="book">
            <h2 class="card-title"><?= e(t('Book This Equipment')) ?></h2>
            <?php if (!$isFarmer): ?>
                <p class="muted"><?= e(t('Only farmer accounts can book equipment.')) ?></p>
            <?php else: ?>
                <form method="get" action="index.php" data-quote data-equipment="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="r" value="booking/confirm">
                    <input type="hidden" name="equipment_id" value="<?= (int)$item['id'] ?>">
                    <div class="form-grid">
                        <label class="field"><span><?= e(t('Start Date')) ?></span><input type="date" name="start_date" min="<?= today() ?>" value="<?= e($start) ?>" required></label>
                        <label class="field"><span><?= e(t('End Date')) ?></span><input type="date" name="end_date" min="<?= today() ?>" value="<?= e($end) ?>" required></label>
                    </div>
                    <div class="quote-box" data-quote-result>
                        <div class="quote-line"><span><?= e(t('Total Days')) ?></span><b data-q="days">—</b></div>
                        <div class="quote-line"><span><?= e(t('Rate')) ?></span><b data-q="rate">—</b></div>
                        <div class="quote-line"><span><?= e(t('Delivery Charge')) ?></span><b data-q="delivery">—</b></div>
                        <div class="quote-line total"><span><?= e(t('Total Price')) ?></span><b data-q="total">—</b></div>
                        <p class="quote-msg" data-q="message"><?= e(t('Choose your dates to see the total price and availability.')) ?></p>
                    </div>
                    <?php if (!$verified): ?>
                        <div class="alert alert-warning"><?= icon('shield') ?><span><?= e(t('Account verification required before booking.')) ?> <a href="<?= e(url('profile/index')) ?>#verification"><?= e(t('Verify now')) ?></a></span></div>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-block btn-lg" type="submit" data-book-btn <?= ($verified && $blockReason === null) ? '' : 'disabled' ?>><?= e(t('Book Now')) ?></button>
                    <button class="btn btn-light btn-block" type="button" data-offline-book hidden><?= icon('wifi-off') ?><?= e(t('Save request offline')) ?></button>
                </form>
            <?php endif; ?>
        </section>
        <?php if ($similar): ?>
            <section class="card">
                <h2 class="card-title"><?= e(t('Similar Equipment')) ?></h2>
                <?php foreach ($similar as $s): ?>
                    <a class="mini-eq" href="<?= e(url('equipment/show', ['id' => $s['id']])) ?>">
                        <img src="<?= e(equipment_image($s)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($s['category_icon']) ?>.svg'">
                        <div><strong><?= e($s['name']) ?></strong><small><?= e($s['location']) ?> · <?= money($s['price_per_day']) ?>/<?= e(t('day')) ?></small></div>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </aside>
</div>
