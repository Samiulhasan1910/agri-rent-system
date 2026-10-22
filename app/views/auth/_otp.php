<div class="field otp-field">
    <input class="otp-single" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" placeholder="••••••" aria-label="<?= e(t('6-digit code')) ?>" data-otp required>
    <?= field_error('code') ?>
</div>
