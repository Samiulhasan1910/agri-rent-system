<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private User $users;
    private Token $tokens;
    private SecurityLog $logs;

    public function __construct()
    {
        $this->users = new User();
        $this->tokens = new Token();
        $this->logs = new SecurityLog();
    }

    private function guestOnly(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard/index');
        }
    }

    /* ==============================================================
     * Registration & email verification
     * ============================================================== */
    public function register(): void
    {
        $this->guestOnly();
        if (!$this->isPost()) {
            $this->view('auth/register', ['title' => t('Create Account'), 'districts' => array_keys(Emergency::DISTRICTS)], 'public');
            return;
        }
        $this->requirePost();

        $v = new Validator($_POST);
        $email = mb_strtolower($v->value('email'));
        $v->required('name', t('Name is required'))->max('name', 100, t('Name is too long'))
            ->required('email', t('Email is required'))->email('email', t('Invalid email address'))
            ->check('email', $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$this->users->emailExists($email), t('Email already used'))
            ->required('phone', t('Phone number is required'))->regex('phone', '/^(\+?88)?01[3-9]\d{8}$/', t('Invalid phone number'))
            ->required('address', t('Please select your location'))
            ->required('password', t('Password is required'))->min('password', 8, t('Invalid password'))
            ->required('password_confirmation', t('Please confirm your password'))->same('password_confirmation', 'password', t('Password does not match'))
            ->required('role', t('Please select a user type'))->in('role', ['farmer', 'employee'], t('Please select a user type'))
            ->check('terms', !empty($_POST['terms']), t('Please accept the terms and conditions'));
        if ($v->fails()) {
            $this->fail(t('Please fix the highlighted fields.'), $v->errors());
        }

        $document = null;
        if (Upload::has('verification_document')) {
            try {
                $document = Upload::document('verification_document', 'verification');
            } catch (RuntimeException $e) {
                $this->fail($e->getMessage(), ['verification_document' => $e->getMessage()]);
            }
        }

        $id = $this->users->create([
            'name' => $v->value('name'),
            'email' => $email,
            'phone' => preg_replace('/\s+/', '', $v->value('phone')),
            'address' => mb_substr($v->value('address'), 0, 255),
            'password' => password_hash($v->value('password'), PASSWORD_DEFAULT),
            'role' => $v->value('role'),
            'status' => 'inactive',
            'verification_status' => 'pending',
            'verification_document' => $document,
            'language' => Lang::get(),
        ]);
        $this->logs->log('register', $id, $email, 'Registered as ' . $v->value('role'));

        Session::set('verify_email', $email);
        $sent = $this->sendVerificationEmail($this->users->find($id));
        $message = $sent ? t('Please verify your email') : t('Your account was created, but the verification email could not be sent. Please use Resend.');
        if ($this->isAjax()) {
            Session::flash($sent ? 'success' : 'warning', $message);
            $this->json(['ok' => true, 'message' => $message, 'redirect' => url('auth/verify-email')]);
        }
        Session::flash($sent ? 'success' : 'warning', $message);
        $this->redirect('auth/verify-email');
    }

    private function sendVerificationEmail(array $user): bool
    {
        $issued = $this->tokens->issue((int)$user['id'], 'email_verify', (int)config('security.email_link_hours', 24) * 3600, (int)config('security.email_link_hours', 24) * 3600);
        $link = abs_url('auth/verify', ['token' => $issued['token']]);
        $body = '<p>' . e(t('Hi :name,', ['name' => $user['name']])) . '</p>'
            . '<p>' . e(t('Welcome to AgriRent! Use this code to verify your email address:')) . '</p>'
            . '<p style="font-size:30px;font-weight:700;letter-spacing:8px;color:#16803c;margin:18px 0">' . e($issued['code']) . '</p>'
            . '<p>' . e(t('Or click the button below. The link expires in :hours hours.', ['hours' => (int)config('security.email_link_hours', 24)])) . '</p>';
        return Notifier::email($user['email'], t('Verify your AgriRent email'), t('Verify your email address'), $body, t('Verify Email'), $link, true);
    }

    public function verifyEmail(): void
    {
        $this->guestOnly();
        $email = (string)Session::get('verify_email', '');
        if (!$this->isPost()) {
            $this->view('auth/verify_email', ['title' => t('Verify Your Email'), 'email' => $email], 'public');
            return;
        }
        $this->requirePost();
        $code = preg_replace('/\D/', '', $this->input('code'));
        $user = $email !== '' ? $this->users->findByEmail($email) : null;
        if (!$user) {
            $this->fail(t('Your verification session expired. Please log in to request a new code.'), [], url('auth/login'));
        }
        if ($user['email_verified_at']) {
            $this->success(t('Your email is already verified. Please log in.'), url('auth/login'));
        }
        if (strlen($code) !== 6) {
            $this->fail(t('Please enter the 6-digit code.'), ['code' => t('Please enter the 6-digit code.')]);
        }
        $check = $this->tokens->checkCode((int)$user['id'], 'email_verify', $code);
        if (!$check['ok']) {
            $msg = $check['reason'] === 'locked' ? t('Too many incorrect attempts. Please request a new code.') : t('Invalid or expired verification code');
            $this->fail($msg, ['code' => $msg]);
        }
        $this->activate($user, (int)$check['row']['id']);
        $this->success(t('Email verified successfully. You can now log in.'), url('auth/login'));
    }

    /** Email link: index.php?r=auth/verify&token=... */
    public function verify(): void
    {
        $row = $this->tokens->findByLink('email_verify', $this->input('token'));
        if (!$row) {
            $this->view('auth/verify_failed', ['title' => t('Verification failed')], 'public');
            return;
        }
        $user = $this->users->find((int)$row['user_id']);
        if ($user) {
            $this->activate($user, (int)$row['id']);
        }
        Session::flash('success', t('Email verified successfully. You can now log in.'));
        $this->redirect(Auth::check() ? 'dashboard/index' : 'auth/login');
    }

    private function activate(array $user, int $tokenId): void
    {
        $this->users->markEmailVerified((int)$user['id']);
        $this->tokens->consume($tokenId);
        $this->logs->log('email_verified', (int)$user['id'], $user['email']);
        Session::forget('verify_email');
        $roles = $user['role'] === 'farmer' ? ['employee', 'admin'] : ['admin'];
        Notifier::roles($roles, 'Verification request', t(':name registered as :role and is waiting for account verification.', ['name' => $user['name'], 'role' => role_label($user['role'])]), url('verification/index'));
    }

    public function resendVerification(): void
    {
        $this->requirePost();
        $email = mb_strtolower($this->input('email') ?: (string)Session::get('verify_email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail(t('Email is required'), ['email' => t('Email is required')]);
        }
        $user = $this->users->findByEmail($email);
        if ($user && $user['email_verified_at']) {
            $this->success(t('Your email is already verified. Please log in.'), url('auth/login'));
        }
        if ($user && $user['status'] === 'inactive') {
            $latest = $this->tokens->latest((int)$user['id'], 'email_verify');
            if ($latest && strtotime($latest['created_at']) > time() - 45) {
                $this->fail(t('Please wait a moment before requesting another email.'));
            }
            Session::set('verify_email', $email);
            if (!$this->sendVerificationEmail($user)) {
                $this->fail(t('The verification email could not be sent. Please try again later.'));
            }
        }
        $this->success(t('A new verification email has been sent.'), url('auth/verify-email'));
    }

    /* ==============================================================
     * Login, lockout, suspicious activity and 2FA
     * ============================================================== */
    public function login(): void
    {
        $this->guestOnly();
        if (!$this->isPost()) {
            $this->view('auth/login', ['title' => t('Login'), 'googleEnabled' => $this->googleEnabled()], 'public');
            return;
        }
        $this->requirePost();

        $v = new Validator($_POST);
        $v->required('email', t('Email is required'))->required('password', t('Password is required'));
        if ($v->fails()) {
            $this->fail(t('Please fill in all required fields.'), $v->errors());
        }
        $email = mb_strtolower($v->value('email'));
        $password = (string)($_POST['password'] ?? '');
        $user = $this->users->findByEmail($email);
        $mismatch = t('Email or password does not match!');

        if (!$user) {
            $this->logs->log('login_failed', null, $email, 'Unknown email');
            $this->detectCredentialStuffing($email);
            $this->fail($mismatch, ['password' => $mismatch]);
        }
        if (in_array($user['status'], ['deleted', 'suspended'], true)) {
            $this->logs->log('login_blocked', (int)$user['id'], $email, 'Account ' . $user['status']);
            $this->fail(t('This account no longer exists or has been deactivated.'));
        }
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $this->fail(t('Too many failed attempts. Please try again later') . ' (' . t(':n min', ['n' => (int)ceil((strtotime($user['locked_until']) - time()) / 60)]) . ')');
        }
        if (!password_verify($password, $user['password'])) {
            $max = max(3, (int)setting('max_login_attempts', 5));
            [$attempts, $lockedUntil] = $this->users->recordFailedLogin((int)$user['id'], $max, max(1, (int)setting('lockout_minutes', 15)));
            $this->logs->log('login_failed', (int)$user['id'], $email, 'Wrong password (attempt ' . $attempts . ')');
            $this->detectCredentialStuffing($email);
            if ($lockedUntil) {
                $this->logs->log('account_locked', (int)$user['id'], $email, 'Locked until ' . $lockedUntil);
                $this->alertSuspicious($user, t('Your account was temporarily locked after :n failed sign-in attempts from IP :ip.', ['n' => $max, 'ip' => client_ip()]));
                $this->fail(t('Too many failed attempts. Please try again later'));
            }
            $remaining = $max - $attempts;
            $this->fail($mismatch . ($remaining <= 2 ? ' ' . t(':n attempt(s) left before your account is locked.', ['n' => $remaining]) : ''), ['password' => $mismatch]);
        }
        if ($user['status'] === 'inactive' || !$user['email_verified_at']) {
            Session::set('verify_email', $user['email']);
            $this->fail(t('Please verify your email before logging in'), [], url('auth/verify-email'), 403, ['redirect' => url('auth/verify-email')]);
        }
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->users->updateProfile((int)$user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $remember = !empty($_POST['remember']);
        if ((int)$user['two_factor_enabled'] === 1 || Settings::enabled('enable_2fa_all')) {
            Session::regenerate();
            Session::set('2fa_uid', (int)$user['id']);
            Session::set('2fa_remember', $remember);
            $sent = $this->sendLoginOtp($user);
            $msg = $sent ? t('A 6-digit verification code has been sent to your email.') : t('The verification code could not be sent. Please use Resend.');
            $this->success($msg, url('auth/two-factor'));
        }
        $this->completeLogin($user, $remember);
    }

    private function sendLoginOtp(array $user): bool
    {
        $seconds = (int)config('security.otp_seconds', 120);
        $issued = $this->tokens->issue((int)$user['id'], 'login_otp', $seconds, $seconds);
        $this->logs->log('2fa_sent', (int)$user['id'], $user['email']);
        $body = '<p>' . e(t('Your AgriRent login verification code is:')) . '</p>'
            . '<p style="font-size:30px;font-weight:700;letter-spacing:8px;color:#16803c;margin:18px 0">' . e($issued['code']) . '</p>'
            . '<p>' . e(t('This code expires in :s seconds. If you did not try to sign in, change your password immediately.', ['s' => $seconds])) . '</p>';
        return Notifier::email($user['email'], t('Your AgriRent login code'), t('Two-factor verification'), $body, null, null, true);
    }

    public function twoFactor(): void
    {
        $uid = (int)Session::get('2fa_uid', 0);
        $user = $uid ? $this->users->find($uid) : null;
        if (!$user) {
            $this->redirect('auth/login');
        }
        if (!$this->isPost()) {
            $latest = $this->tokens->latest($uid, 'login_otp');
            $this->view('auth/two_factor', [
                'title' => t('Two-factor verification'),
                'email' => $user['email'],
                'expiresIn' => $latest ? max(0, strtotime($latest['code_expires_at']) - time()) : 0,
            ], 'public');
            return;
        }
        $this->requirePost();
        $check = $this->tokens->checkCode($uid, 'login_otp', preg_replace('/\D/', '', $this->input('code')));
        if (!$check['ok']) {
            $this->logs->log('2fa_failed', $uid, $user['email'], $check['reason']);
            $messages = [
                'expired' => t('The code has expired. Please request a new one.'),
                'locked' => t('Too many incorrect attempts. Please request a new code.'),
            ];
            $msg = $messages[$check['reason']] ?? t('Invalid verification code');
            $this->fail($msg, ['code' => $msg]);
        }
        $this->tokens->consume((int)$check['row']['id']);
        $this->completeLogin($user, (bool)Session::get('2fa_remember', false));
    }

    public function resendOtp(): void
    {
        $this->requirePost();
        $uid = (int)Session::get('2fa_uid', 0);
        $user = $uid ? $this->users->find($uid) : null;
        if (!$user) {
            $this->fail(t('Your verification session expired. Please log in again.'), [], url('auth/login'));
        }
        $latest = $this->tokens->latest($uid, 'login_otp');
        if ($latest && strtotime($latest['created_at']) > time() - 30) {
            $this->fail(t('Please wait a moment before requesting another code.'));
        }
        if (!$this->sendLoginOtp($user)) {
            $this->fail(t('The verification code could not be sent. Please try again later.'));
        }
        $this->success(t('A new code has been sent to your email.'), null, ['expires_in' => (int)config('security.otp_seconds', 120)]);
    }

    private function completeLogin(array $user, bool $remember): void
    {
        $known = $this->logs->hasSeenDevice((int)$user['id']);
        $hadLogin = $this->logs->hasLoggedInBefore((int)$user['id']);
        $previousFailures = (int)$user['failed_attempts'];

        Auth::login($user, $remember);
        $this->users->recordLogin((int)$user['id']);
        $this->logs->log('login_success', (int)$user['id'], $user['email'], 'Signed in');

        if ($hadLogin && !$known) {
            $this->logs->log('suspicious_login', (int)$user['id'], $user['email'], 'New device or location');
            $this->alertSuspicious($user, t('New sign-in to your account from IP :ip (:agent). If this was not you, change your password now.', ['ip' => client_ip(), 'agent' => str_limit(user_agent(), 60)]));
        } elseif ($previousFailures >= 3) {
            $this->alertSuspicious($user, t('Your account was signed in after :n failed attempts. If this was not you, change your password now.', ['n' => $previousFailures]));
        }

        $intended = (string)Session::pull('intended', '');
        $target = (str_starts_with($intended, '/') && !str_starts_with($intended, '//') && str_contains($intended, 'index.php')) ? $intended : url('dashboard/index');
        $this->success(t('Welcome back, :name!', ['name' => explode(' ', $user['name'])[0]]), $target);
    }

    /** Notifies the user (in-app + email) and, when enabled, the administrators. */
    private function alertSuspicious(array $user, string $message): void
    {
        Notifier::send((int)$user['id'], 'Security alert', $message, url('profile/index'), 'danger');
        Notifier::email($user['email'], t('AgriRent security alert'), t('Suspicious activity on your account'), '<p>' . e($message) . '</p>', t('Review account'), abs_url('profile/index'), true);
        if (Settings::enabled('suspicious_activity_alert')) {
            Notifier::admins('Suspicious activity', t(':name (:email): :message', ['name' => $user['name'], 'email' => $user['email'], 'message' => $message]), url('settings/index', ['tab' => 'logs']), 'danger');
        }
    }

    /** Many failures from one IP across accounts => alert administrators once. */
    private function detectCredentialStuffing(string $email): void
    {
        if (Settings::enabled('suspicious_activity_alert') && $this->logs->recentFailuresFromIp(15) === 10) {
            $this->logs->log('suspicious_ip', null, $email, '10 failed logins in 15 minutes');
            Notifier::admins('Suspicious activity', t('10 failed sign-in attempts in 15 minutes from IP :ip (last tried :email).', ['ip' => client_ip(), 'email' => $email]), url('settings/index', ['tab' => 'logs']), 'danger');
        }
    }

    public function logout(): void
    {
        if ($this->isPost()) {
            $this->requirePost();
        }
        $user = Auth::user();
        if ($user) {
            $this->logs->log('logout', (int)$user['id'], $user['email']);
        }
        Auth::logout();
        Session::flash('success', t('You have been logged out.'));
        $this->redirect('auth/login');
    }

    /* ==============================================================
     * Forgot / reset password (link + 120-second OTP)
     * ============================================================== */
    public function forgot(): void
    {
        $this->guestOnly();
        if (!$this->isPost()) {
            $this->view('auth/forgot', ['title' => t('Forgot Password')], 'public');
            return;
        }
        $this->requirePost();
        $v = new Validator($_POST);
        $v->required('email', t('Email is required'))->email('email', t('Invalid email address'));
        if ($v->fails()) {
            $this->fail($v->first(), $v->errors());
        }
        $user = $this->users->findByEmail($v->value('email'));
        if (!$user || $user['status'] === 'deleted') {
            $this->fail(t('No email found!'), ['email' => t('No email found!')]);
        }
        $latest = $this->tokens->latest((int)$user['id'], 'password_reset');
        if ($latest && !$latest['used_at'] && strtotime($latest['created_at']) > time() - 30) {
            Session::set('reset_email', $user['email']);
            $this->success(t('Password reset link sent!'), url('auth/reset-code'));
        }
        if (!$this->sendResetEmail($user)) {
            $this->fail(t('The reset email could not be sent. Please try again later.'));
        }
        Session::set('reset_email', $user['email']);
        $this->logs->log('password_reset_requested', (int)$user['id'], $user['email']);
        $this->success(t('Password reset link sent!'), url('auth/reset-code'));
    }

    private function sendResetEmail(array $user): bool
    {
        $otp = (int)config('security.otp_seconds', 120);
        $minutes = (int)config('security.reset_link_minutes', 30);
        $issued = $this->tokens->issue((int)$user['id'], 'password_reset', $minutes * 60, $otp);
        $body = '<p>' . e(t('Hi :name,', ['name' => $user['name']])) . '</p>'
            . '<p>' . e(t('We received a request to reset your password. Your verification code is:')) . '</p>'
            . '<p style="font-size:30px;font-weight:700;letter-spacing:8px;color:#16803c;margin:18px 0">' . e($issued['code']) . '</p>'
            . '<p>' . e(t('The code expires in :s seconds. You can also use the button below (valid for :m minutes).', ['s' => $otp, 'm' => $minutes])) . '</p>'
            . '<p>' . e(t('If you did not request this, you can safely ignore this email.')) . '</p>';
        return Notifier::email($user['email'], t('Reset your AgriRent password'), t('Password reset request'), $body, t('Reset Password'), abs_url('auth/reset-password', ['token' => $issued['token']]), true);
    }

    public function resetCode(): void
    {
        $this->guestOnly();
        $email = (string)Session::get('reset_email', '');
        $user = $email !== '' ? $this->users->findByEmail($email) : null;
        if (!$user) {
            $this->redirect('auth/forgot');
        }
        if (!$this->isPost()) {
            $latest = $this->tokens->latest((int)$user['id'], 'password_reset');
            $this->view('auth/reset_code', [
                'title' => t('Verify reset code'),
                'email' => $email,
                'expiresIn' => $latest && !$latest['used_at'] ? max(0, strtotime($latest['code_expires_at']) - time()) : 0,
            ], 'public');
            return;
        }
        $this->requirePost();
        $check = $this->tokens->checkCode((int)$user['id'], 'password_reset', preg_replace('/\D/', '', $this->input('code')));
        if (!$check['ok']) {
            $messages = [
                'expired' => t('The code has expired. Please request a new one.'),
                'locked' => t('Too many incorrect attempts. Please request a new code.'),
                'none' => t('Invalid or expired reset link'),
            ];
            $msg = $messages[$check['reason']] ?? t('Invalid verification code');
            $this->fail($msg, ['code' => $msg]);
        }
        Session::set('reset_token_id', (int)$check['row']['id']);
        Session::set('reset_uid', (int)$user['id']);
        $this->success(t('Code verified. Please create a new password.'), url('auth/reset-password'));
    }

    public function resendReset(): void
    {
        $this->requirePost();
        $user = $this->users->findByEmail((string)Session::get('reset_email', ''));
        if (!$user) {
            $this->fail(t('No email found!'), [], url('auth/forgot'));
        }
        $latest = $this->tokens->latest((int)$user['id'], 'password_reset');
        if ($latest && strtotime($latest['created_at']) > time() - 30) {
            $this->fail(t('Please wait a moment before requesting another code.'));
        }
        if (!$this->sendResetEmail($user)) {
            $this->fail(t('The reset email could not be sent. Please try again later.'));
        }
        $this->success(t('A new code has been sent to your email.'), null, ['expires_in' => (int)config('security.otp_seconds', 120)]);
    }

    public function resetPassword(): void
    {
        $token = $this->input('token');
        if ($token !== '' && !$this->isPost()) {
            $row = $this->tokens->findByLink('password_reset', $token);
            if (!$row) {
                $this->view('auth/reset_invalid', ['title' => t('Invalid or expired reset link')], 'public');
                return;
            }
            Session::set('reset_token_id', (int)$row['id']);
            Session::set('reset_uid', (int)$row['user_id']);
            $this->redirect('auth/reset-password');
        }

        $tokenId = (int)Session::get('reset_token_id', 0);
        $uid = (int)Session::get('reset_uid', 0);
        $valid = $tokenId && $uid && $this->tokenStillValid($tokenId, $uid);
        if (!$this->isPost()) {
            if (!$valid) {
                $this->view('auth/reset_invalid', ['title' => t('Invalid or expired reset link')], 'public');
                return;
            }
            $this->view('auth/reset_password', ['title' => t('Reset Password')], 'public');
            return;
        }
        $this->requirePost();
        if (!$valid) {
            $this->fail(t('Invalid or expired reset link'), [], url('auth/forgot'));
        }
        $v = new Validator($_POST);
        $strength = t('Password must be at least 8 characters long and include a special character');
        $v->required('password', t('Password is required'))
            ->check('password', mb_strlen($v->value('password')) >= 8 && preg_match('/[^A-Za-z0-9]/', $v->value('password')) === 1, $strength)
            ->required('password_confirmation', t('Please re-type your password'))
            ->same('password_confirmation', 'password', t('Password does not match'));
        if ($v->fails()) {
            $this->fail($v->first(), $v->errors());
        }
        $this->users->setPassword($uid, (string)$_POST['password']);
        $this->tokens->consume($tokenId);
        $user = $this->users->find($uid);
        $this->logs->log('password_reset', $uid, $user['email'] ?? null);
        Notifier::send($uid, 'Password changed', t('Your password was changed. If this was not you, contact support immediately.'), null, 'warning');
        Session::forget('reset_token_id');
        Session::forget('reset_uid');
        Session::forget('reset_email');
        $this->success(t('Password changed'), url('auth/login'));
    }

    private function tokenStillValid(int $tokenId, int $uid): bool
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM user_tokens WHERE id=? AND user_id=? AND type='password_reset' AND used_at IS NULL AND expires_at > NOW()");
        $stmt->execute([$tokenId, $uid]);
        return (bool)$stmt->fetchColumn();
    }

    /* ==============================================================
     * Google SSO (OAuth 2.0)
     * ============================================================== */
    private function googleEnabled(): bool
    {
        return config('google.client_id') !== '' && config('google.client_secret') !== '';
    }

    public function google(): void
    {
        if (!$this->googleEnabled()) {
            Session::flash('warning', t('Google sign-in is not configured yet. Ask the administrator to add Google OAuth credentials in config/config.php.'));
            $this->redirectTo($this->backUrl('auth/login'));
        }
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);
        $query = http_build_query([
            'client_id' => config('google.client_id'),
            'redirect_uri' => abs_url('auth/google-callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
        $this->redirectTo('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function googleCallback(): void
    {
        if (!$this->googleEnabled() || !hash_equals((string)Session::pull('oauth_state', ''), $this->input('state')) || $this->input('code') === '') {
            Session::flash('error', t('Google sign-in failed. Please try again.'));
            $this->redirect('auth/login');
        }
        $token = $this->httpJson('https://oauth2.googleapis.com/token', [
            'code' => $this->input('code'),
            'client_id' => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'redirect_uri' => abs_url('auth/google-callback'),
            'grant_type' => 'authorization_code',
        ]);
        $profile = !empty($token['access_token']) ? $this->httpJson('https://openidconnect.googleapis.com/v1/userinfo', null, $token['access_token']) : null;
        if (!$profile || empty($profile['email']) || empty($profile['sub']) || empty($profile['email_verified'])) {
            Session::flash('error', t('Google sign-in failed. Please try again.'));
            $this->redirect('auth/login');
        }
        $user = $this->users->findByGoogle((string)$profile['sub']) ?? $this->users->findByEmail((string)$profile['email']);
        if ($user && in_array($user['status'], ['deleted', 'suspended'], true)) {
            Session::flash('error', t('This account no longer exists or has been deactivated.'));
            $this->redirect('auth/login');
        }
        if (!$user) {
            $id = $this->users->create([
                'name' => mb_substr((string)($profile['name'] ?? 'Farmer'), 0, 100),
                'email' => (string)$profile['email'],
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'role' => 'farmer',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'google_id' => (string)$profile['sub'],
                'language' => Lang::get(),
            ]);
            $user = $this->users->find($id);
            Notifier::staff('Verification request', t(':name registered as :role and is waiting for account verification.', ['name' => $user['name'], 'role' => role_label('farmer')]), url('verification/index'));
            Session::flash('info', t('Welcome! Please complete your phone number and address in your profile.'));
        } else {
            $this->users->updateProfile((int)$user['id'], ['google_id' => (string)$profile['sub']]);
            $this->users->markEmailVerified((int)$user['id']);
            $user = $this->users->find((int)$user['id']);
        }
        $this->logs->log('google_login', (int)$user['id'], $user['email']);
        $this->completeLogin($user, false);
    }

    private function httpJson(string $url, ?array $post, ?string $bearer = null): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if ($bearer) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $bearer]);
        }
        $body = curl_exec($ch);
        curl_close($ch);
        $data = is_string($body) ? json_decode($body, true) : null;
        return is_array($data) ? $data : null;
    }
}
