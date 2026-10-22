<?php
$titles = ['bkash' => t('Complete bKash Payment'), 'nagad' => t('Complete Nagad Payment'), 'card' => t('Credit / Debit Card Payment'), 'cash' => t('Complete Cash on Delivery')];
$wallet = in_array($method, ['bkash', 'nagad'], true);
?>
<nav class="crumbs"><a href="<?= e(url('payment/checkout', ['booking' => $b['id']])) ?>"><?= e(t('Choose Payment Method')) ?></a><?= icon('chevron-right', 'icon-xs') ?><span><?= e(Payment::label($method)) ?></span></nav>
<div class="page-head"><div><h1><?= e($titles[$method]) ?></h1><p class="muted"><?= e($method === 'cash' ? t('Pay in cash when you receive your equipment.') : t('Pay securely using your account.')) ?></p></div>
    <span class="pay-brand pay-brand-<?= e($method) ?>"><?= e(Payment::label($method)) ?></span></div>

<div class="grid-2-1">
    <form class="card" method="post" action="<?= e(url('payment/process')) ?>" data-ajax data-payment-form novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="booking" value="<?= (int)$b['id'] ?>">
        <input type="hidden" name="method" value="<?= e($method) ?>">
        <div class="merchant-row">
            <div><small><?= e(t('Merchant')) ?></small><strong><?= e(setting('platform_name', 'AgriRent')) ?></strong></div>
            <div><small><?= e(t('Booking ID')) ?></small><strong>#<?= e($b['booking_code']) ?></strong></div>
            <div><small><?= e(t('Amount')) ?></small><strong class="text-green" data-pay-amount><?= money($due, true) ?></strong></div>
        </div>

        <?php if ($method === 'card'): ?>
            <div class="form-grid">
                <div class="field span-2"><label for="cn"><?= e(t('Card Number')) ?></label><div class="input-icon"><?= icon('credit-card') ?><input id="cn" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" maxlength="23" data-card-number required></div><?= field_error('card_number') ?></div>
                <div class="field"><label for="cname"><?= e(t('Cardholder Name')) ?></label><input id="cname" name="card_name" autocomplete="cc-name" placeholder="<?= e(t('Name on Card')) ?>" required><?= field_error('card_name') ?></div>
                <div class="field"><label for="exp"><?= e(t('Expiry Date')) ?></label><input id="exp" name="expiry" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5" data-expiry required><?= field_error('expiry') ?></div>
                <div class="field"><label for="cvc"><?= e(t('CVV / CVC')) ?></label><input id="cvc" name="cvc" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" required><?= field_error('cvc') ?></div>
                <div class="field"><label for="bill"><?= e(t('Billing Address')) ?></label><input id="bill" name="billing_address" value="<?= e($user['address']) ?>" placeholder="<?= e(t('Enter billing address')) ?>"></div>
            </div>
            <p class="muted small"><?= icon('info', 'icon-xs') ?> <?= e(t('Demo gateway: any valid test card works (e.g. 4242 4242 4242 4242). Cards ending in 0002 are declined.')) ?></p>
        <?php elseif ($wallet): ?>
            <div class="field"><label for="wn"><?= e(Payment::label($method)) ?> <?= e(t('Number')) ?></label>
                <div class="input-with-btn"><div class="input-icon grow"><?= icon('smartphone') ?><input id="wn" name="wallet_number" inputmode="tel" placeholder="01XXXXXXXXX" value="<?= e($user['phone']) ?>" required></div>
                <button class="btn btn-outline" type="button" data-send-otp data-countdown="30"><?= e(t('Send OTP')) ?></button></div><?= field_error('wallet_number') ?></div>
            <div class="form-grid">
                <div class="field"><label for="otp"><?= e(t('OTP Code')) ?></label><input id="otp" name="otp" inputmode="numeric" maxlength="6" placeholder="<?= e(t('Enter 6 digit OTP')) ?>" autocomplete="one-time-code" required><?= field_error('otp') ?></div>
                <div class="field"><label for="pin"><?= e(Payment::label($method)) ?> <?= e(t('PIN')) ?></label><div class="input-icon"><?= icon('lock') ?><input id="pin" type="password" name="pin" inputmode="numeric" maxlength="5" placeholder="<?= e(t('Enter 5 digit PIN')) ?>" required><button type="button" class="pw-toggle" data-pw-toggle><?= icon('eye') ?></button></div><?= field_error('pin') ?></div>
            </div>
            <p class="muted small"><?= icon('info', 'icon-xs') ?> <?= e(t('Demo gateway: the OTP is shown after you click Send OTP. PIN 0000 simulates insufficient balance.')) ?></p>
        <?php else: ?>
            <div class="sub-card">
                <dl class="kv">
                    <div><dt><?= e(t('Pay When')) ?></dt><dd><?= e(t('On delivery, when you receive the equipment')) ?></dd></div>
                    <div><dt><?= e(t('Delivery Address')) ?></dt><dd><?= e($b['delivery_address']) ?></dd></div>
                    <div><dt><?= e(t('Delivery Date')) ?></dt><dd><?= e(fmt_date($b['start_date'])) ?></dd></div>
                </dl>
            </div>
            <div class="alert alert-success"><?= icon('check-circle') ?><span><strong><?= e(t('No online payment needed')) ?></strong> — <?= e(t('pay in cash to our delivery team when you receive the equipment.')) ?></span></div>
            <ul class="check-list small">
                <li><?= icon('check', 'icon-xs') ?><?= e(t('Please keep the exact amount ready to avoid delay.')) ?></li>
                <li><?= icon('check', 'icon-xs') ?><?= e(t('Check the equipment and accessories before making payment.')) ?></li>
                <li><?= icon('check', 'icon-xs') ?><?= e(t('You will get a confirmation after successful payment.')) ?></li>
            </ul>
            <label class="check"><input type="checkbox" name="confirm_cash" value="1"> <span><?= e(t('I will pay in cash on delivery')) ?></span></label><?= field_error('confirm_cash') ?>
        <?php endif; ?>

        <div class="field coupon-field">
            <label for="coupon"><?= icon('tag', 'icon-xs') ?> <?= e(t('Coupon Code')) ?></label>
            <div class="input-with-btn"><input id="coupon" name="coupon" placeholder="<?= e(t('e.g. FARM10')) ?>" data-coupon-input autocomplete="off">
                <button class="btn btn-outline" type="button" data-apply-coupon data-url="<?= e(url('payment/coupon')) ?>" data-booking="<?= (int)$b['id'] ?>"><?= e(t('Apply')) ?></button></div>
            <?= field_error('coupon') ?><small class="hint" data-coupon-msg></small>
        </div>

        <div class="alert alert-success"><?= icon('shield-check') ?><span><?= e(t('Your payment information is secure and encrypted.')) ?></span></div>
        <button class="btn btn-primary btn-block btn-lg" type="submit"><?= icon($method === 'cash' ? 'check' : 'lock') ?>
            <span><?= e($method === 'cash' ? t('Confirm Cash On Delivery') : t('Pay')) ?></span> <?php if ($method !== 'cash'): ?><span data-pay-amount><?= money($due, true) ?></span> <?= e(t('Securely')) ?><?php endif; ?></button>
        <div class="alert alert-warning"><?= icon('alert') ?><span><?= e(t('Never share your card details, OTP or PIN with anyone. AgriRent will never ask for them.')) ?></span></div>
    </form>

    <aside>
        <section class="card">
            <h2 class="card-title"><?= icon('receipt') ?> <?= e(t('Payment Info')) ?></h2>
            <dl class="kv">
                <div><dt><?= e(t('Payment Method')) ?></dt><dd><?= e(Payment::label($method)) ?></dd></div>
                <div><dt><?= e(t('Status')) ?></dt><dd><?= badge('pending') ?></dd></div>
                <div><dt><?= e(t('Amount Due')) ?></dt><dd data-pay-amount><?= money($due, true) ?></dd></div>
                <div data-discount-row hidden><dt><?= e(t('Discount')) ?></dt><dd data-discount>—</dd></div>
            </dl>
        </section>
        <section class="card help-card">
            <?= icon('headset') ?><div><strong><?= e(t('Need Help?')) ?></strong><small><?= icon('phone', 'icon-xs') ?> <?= e(setting('support_phone')) ?> · <?= e(t('9 AM - 9 PM (Daily)')) ?></small></div>
        </section>
        <a class="back-link" href="<?= e(url('payment/checkout', ['booking' => $b['id']])) ?>"><?= icon('arrow-left', 'icon-xs') ?><?= e(t('Back to Payment Methods')) ?></a>
    </aside>
</div>
