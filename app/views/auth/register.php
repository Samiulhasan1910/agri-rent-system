<section class="auth-section">
    <div class="auth-card auth-card-wide">
        <?php $asideHeading = e(t('Create Your')) . '<br><span>' . e(t('AgriRent Account')) . '</span>'; include __DIR__ . '/_aside.php'; ?>
        <div class="auth-form">
            <span class="auth-icon"><?= icon('user-check') ?></span>
            <h1><?= e(t('Create Account')) ?></h1>
            <form method="post" action="<?= e(url('auth/register')) ?>" data-ajax enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="r-name"><?= e(t('Full Name')) ?></label>
                    <div class="input-icon"><?= icon('user') ?><input id="r-name" name="name" value="<?= e(old('name')) ?>" placeholder="<?= e(t('Enter your full name')) ?>" autocomplete="name" required></div>
                    <?= field_error('name') ?>
                </div>
                <div class="field">
                    <label for="r-email"><?= e(t('Email Address')) ?></label>
                    <div class="input-icon"><?= icon('mail') ?><input id="r-email" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="<?= e(t('Enter your email address')) ?>" autocomplete="email" required></div>
                    <?= field_error('email') ?>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="r-phone"><?= e(t('Phone Number')) ?></label>
                        <div class="input-icon"><?= icon('phone') ?><input id="r-phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="01XXXXXXXXX" inputmode="tel" autocomplete="tel" required></div>
                        <?= field_error('phone') ?>
                    </div>
                    <div class="field">
                        <label for="r-role"><?= e(t('User Type')) ?></label>
                        <div class="input-icon"><?= icon('users') ?><select id="r-role" name="role" required>
                            <option value=""><?= e(t('Select user type')) ?></option>
                            <option value="farmer"<?= selected(old('role'), 'farmer') ?>><?= e(t('Farmer')) ?></option>
                            <option value="employee"<?= selected(old('role'), 'employee') ?>><?= e(t('Employee')) ?></option>
                        </select></div>
                        <?= field_error('role') ?>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="r-pass"><?= e(t('Password')) ?></label>
                        <div class="input-icon"><?= icon('lock') ?><input id="r-pass" type="password" name="password" placeholder="<?= e(t('Create a password')) ?>" autocomplete="new-password" minlength="8" required data-strength><button type="button" class="pw-toggle" data-pw-toggle aria-label="<?= e(t('Show password')) ?>"><?= icon('eye') ?></button></div>
                        <small class="hint" data-strength-label><?= e(t('At least 8 characters')) ?></small>
                        <?= field_error('password') ?>
                    </div>
                    <div class="field">
                        <label for="r-pass2"><?= e(t('Confirm Password')) ?></label>
                        <div class="input-icon"><?= icon('lock') ?><input id="r-pass2" type="password" name="password_confirmation" placeholder="<?= e(t('Confirm your password')) ?>" autocomplete="new-password" required></div>
                        <?= field_error('password_confirmation') ?>
                    </div>
                </div>
                <div class="field">
                    <label for="r-loc"><?= e(t('Location')) ?></label>
                    <div class="input-icon"><?= icon('map-pin') ?><input id="r-loc" name="address" list="districts" value="<?= e(old('address')) ?>" placeholder="<?= e(t('Select your location (e.g. Sherpur, Bogura)')) ?>" required></div>
                    <datalist id="districts"><?php foreach ($districts as $d): ?><option value="<?= e($d) ?>"><?php endforeach; ?></datalist>
                    <?= field_error('address') ?>
                </div>
                <div class="field">
                    <label for="r-doc"><?= e(t('Verification Document (NID / Trade licence)')) ?> <small class="muted">— <?= e(t('optional now, required before booking')) ?></small></label>
                    <label class="file-drop" for="r-doc"><?= icon('upload') ?><span data-file-label><?= e(t('Click to upload PDF, JPG or PNG (max 5 MB)')) ?></span></label>
                    <input class="visually-hidden" id="r-doc" type="file" name="verification_document" accept=".pdf,.jpg,.jpeg,.png" data-file-input>
                    <?= field_error('verification_document') ?>
                </div>
                <div class="field">
                    <label class="check"><input type="checkbox" name="terms" value="1"<?= checked(old('terms')) ?>> <span><?= e(t('I agree to the')) ?> <a href="#terms" data-modal-open="#termsModal"><?= e(t('Terms & Conditions')) ?></a> <?= e(t('and')) ?> <a href="#terms" data-modal-open="#termsModal"><?= e(t('Privacy Policy')) ?></a></span></label>
                    <?= field_error('terms') ?>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('Create Account')) ?></button>
            </form>
            <div class="divider"><span><?= e(t('or')) ?></span></div>
            <a class="btn btn-outline btn-block google-btn" href="<?= e(url('auth/google')) ?>"><?= icon('google') ?><?= e(t('Continue with Google')) ?></a>
            <p class="auth-switch"><?= e(t('Already have an account?')) ?> <a href="<?= e(url('auth/login')) ?>"><?= e(t('Log In')) ?></a></p>
        </div>
    </div>
</section>

<div class="modal" id="termsModal" role="dialog" aria-modal="true" hidden>
    <div class="modal-card">
        <div class="modal-head"><h3><?= e(t('Terms & Conditions')) ?></h3><button type="button" class="btn-icon" data-modal-close aria-label="<?= e(t('Close')) ?>">×</button></div>
        <div class="modal-body prose">
            <p><?= e(t('By creating an account you agree to provide accurate information, use rented equipment responsibly, return it on time and in the same condition, and pay all rental charges. Farmers must complete account verification before booking.')) ?></p>
            <p><?= e(t('Your personal data is stored securely, passwords are hashed and only authorised staff can access complaint and verification details. You can delete your account at any time from your profile.')) ?></p>
        </div>
        <div class="modal-actions"><button type="button" class="btn btn-primary" data-modal-close><?= e(t('I understand')) ?></button></div>
    </div>
</div>
