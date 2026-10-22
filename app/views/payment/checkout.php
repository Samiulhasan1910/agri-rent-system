<?php
$descriptions = [
    'bkash' => t('Pay easily with bKash'),
    'nagad' => t('Pay easily with Nagad'),
    'card' => t('Pay with Visa, MasterCard, etc.'),
    'cash' => t('Pay when you receive the equipment'),
];
$logos = ['bkash' => 'logo-bkash', 'nagad' => 'logo-nagad', 'card' => 'logo-card', 'cash' => 'logo-cash'];
?>
<nav class="crumbs"><a href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= e(t('Confirm Your Booking')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e(t('Choose Your Payment Method')) ?></span></nav>
<?php $step = 2; include __DIR__ . '/_stepper.php'; ?>
<div class="grid-2-1">
    <form class="card" method="get" action="index.php">
        <input type="hidden" name="r" value="payment/pay">
        <input type="hidden" name="booking" value="<?= (int)$b['id'] ?>">
        <h1 class="h2"><?= e(t('Choose Payment Method')) ?></h1>
        <p class="muted"><?= e(t('Select your preferred payment method to complete this booking.')) ?></p>
        <div class="pay-methods">
            <?php $first = true; foreach (Payment::METHODS as $key => $label): $enabled = isset($methods[$key]); ?>
                <label class="pay-option <?= $enabled ? '' : 'disabled' ?>">
                    <input type="radio" name="method" value="<?= $key ?>" <?= $enabled && $first ? 'checked' : '' ?> <?= $enabled ? '' : 'disabled' ?>>
                    <span class="pay-logo <?= $logos[$key] ?>"><?= $key === 'bkash' ? 'b' : ($key === 'nagad' ? 'N' : icon($key === 'card' ? 'credit-card' : 'banknote')) ?></span>
                    <span class="pay-text"><strong><?= e(t($label)) ?></strong><small><?= e($enabled ? $descriptions[$key] : t('Selected payment method is not available.')) ?></small></span>
                    <span class="radio-dot"></span>
                </label>
            <?php if ($enabled) { $first = false; } endforeach; ?>
        </div>
        <?php if (!$methods): ?><div class="alert alert-danger"><?= icon('alert') ?><span><?= e(t('Selected payment method is not available.')) ?></span></div><?php endif; ?>
        <div class="alert alert-success"><?= icon('shield-check') ?><span><?= e(t('Your payment information is secure with us. We do not store your payment details.')) ?></span></div>
        <div class="form-actions split">
            <a class="back-link" href="<?= e(url('booking/show', ['id' => $b['id']])) ?>"><?= icon('arrow-left', 'icon-xs') ?><?= e(t('Back to Booking')) ?></a>
            <button class="btn btn-primary btn-lg" type="submit" <?= $methods ? '' : 'disabled' ?>><?= e(t('Pay Now')) ?><?= icon('arrow-right') ?></button>
        </div>
    </form>
    <aside class="card summary-card">
        <h2 class="card-title"><?= e(t('Order Summary')) ?></h2>
        <div class="mini-eq"><img src="<?= e(equipment_image($b)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($b['category_icon']) ?>.svg'"><div><strong><?= e($b['equipment_name']) ?></strong><small>#<?= e($b['booking_code']) ?></small></div></div>
        <dl class="kv">
            <div><dt><?= e(t('Rental Period')) ?></dt><dd><?= e(fmt_date($b['start_date'], 'd M')) ?> – <?= e(fmt_date($b['end_date'])) ?></dd></div>
            <div><dt><?= e(t('Total Cost')) ?></dt><dd><?= money($b['total_amount']) ?></dd></div>
            <div><dt><?= e(t('Already paid')) ?></dt><dd><?= money($b['paid_amount']) ?></dd></div>
            <div class="kv-total"><dt><?= e(t('Amount Due')) ?></dt><dd><?= money($due) ?></dd></div>
        </dl>
        <p class="muted small"><?= icon('tag', 'icon-xs') ?> <?= e(t('Have a coupon? You can apply it on the next step.')) ?></p>
    </aside>
</div>
