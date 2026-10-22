<?php
declare(strict_types=1);

class ProfileController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $user = $this->auth();
        $this->view('profile/index', [
            'title' => t('Profile'),
            'user' => $user,
            'districts' => array_keys(Emergency::DISTRICTS),
            'hasActiveRentals' => $user['role'] === 'farmer' && (new Booking())->hasActiveRentals((int)$user['id']),
        ]);
    }

    /** AJAX: current profile data (used to refresh the page after an update). */
    public function data(): void
    {
        $user = $this->auth();
        $fresh = $this->users->find((int)$user['id']);
        if (!$fresh) {
            $this->json(['ok' => false, 'message' => t('Failed to load profile information. Please try again.')], 500);
        }
        $this->json(['ok' => true, 'profile' => $this->publicProfile($fresh)]);
    }

    /** AJAX: verification status shown to farmers. */
    public function verificationStatus(): void
    {
        $user = $this->auth();
        $fresh = $this->users->find((int)$user['id']);
        if (!$fresh) {
            $this->json(['ok' => false, 'message' => t('Unable to load verification status.')], 500);
        }
        $this->json(['ok' => true, 'status' => $fresh['verification_status'], 'badge' => badge($fresh['verification_status']), 'note' => $fresh['verification_note']]);
    }

    private function publicProfile(array $u): array
    {
        return [
            'name' => $u['name'], 'email' => $u['email'], 'phone' => $u['phone'], 'address' => $u['address'],
            'role' => role_label($u['role']), 'avatar' => avatar($u, 'xl'), 'language' => $u['language'],
        ];
    }

    public function update(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $v = new Validator($_POST);
        $v->required('name', t('Name is required'))->max('name', 100, t('Name is too long'))
            ->required('phone', t('Phone number is required'))->regex('phone', '/^(\+?88)?01[3-9]\d{8}$/', t('Invalid phone number'))
            ->required('address', t('Address is required'))->max('address', 255, t('Address is too long'));
        if ($v->fails()) {
            $this->fail(t('Failed to update profile. Please ensure all fields are valid and try again.'), $v->errors());
        }
        try {
            $this->users->updateProfile((int)$user['id'], [
                'name' => $v->value('name'),
                'phone' => preg_replace('/\s+/', '', $v->value('phone')),
                'address' => $v->value('address'),
            ]);
        } catch (Throwable $e) {
            $this->fail(t('Failed to update profile. Please ensure all fields are valid and try again.'));
        }
        $fresh = $this->users->find((int)$user['id']);
        $this->success(t('Profile updated successfully.'), null, ['profile' => $this->publicProfile($fresh)]);
    }

    public function photo(): void
    {
        $user = $this->auth();
        $this->requirePost();
        try {
            $path = Upload::image('photo', 'profiles');
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage(), ['photo' => $e->getMessage()]);
        }
        Upload::delete($user['profile_photo']);
        $this->users->setPhoto((int)$user['id'], $path);
        $fresh = $this->users->find((int)$user['id']);
        $this->success(t('Profile updated successfully.'), null, ['profile' => $this->publicProfile($fresh)]);
    }

    public function password(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $v = new Validator($_POST);
        $v->required('current_password', t('Current password is required'))
            ->check('current_password', $v->value('current_password') === '' || password_verify((string)$_POST['current_password'], $user['password']), t('Current password is incorrect'))
            ->required('password', t('Password is required'))
            ->check('password', mb_strlen($v->value('password')) >= 8 && preg_match('/[^A-Za-z0-9]/', $v->value('password')) === 1, t('Password must be at least 8 characters long and include a special character'))
            ->required('password_confirmation', t('Please re-type your password'))->same('password_confirmation', 'password', t('Password does not match'));
        if ($v->fails()) {
            $this->fail($v->first(), $v->errors());
        }
        $this->users->setPassword((int)$user['id'], (string)$_POST['password']);
        (new SecurityLog())->log('password_changed', (int)$user['id'], $user['email']);
        Notifier::send((int)$user['id'], 'Password changed', t('Your password was changed. If this was not you, contact support immediately.'), null, 'warning');
        $this->success(t('Password changed'));
    }

    public function document(): void
    {
        $user = $this->auth();
        $this->requirePost();
        try {
            $path = Upload::document('document', 'verification');
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage(), ['document' => $e->getMessage()]);
        }
        $this->users->setDocument((int)$user['id'], $path);
        if ($user['verification_status'] !== 'approved') {
            Notifier::roles($user['role'] === 'farmer' ? ['employee', 'admin'] : ['admin'], 'Verification request', t(':name uploaded a verification document.', ['name' => $user['name']]), url('verification/index'));
        }
        $this->success(t('Document uploaded. Our team will review it shortly.'), url('profile/index'));
    }

    public function twoFactor(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $enable = $this->input('enable') === '1';
        $this->users->setTwoFactor((int)$user['id'], $enable);
        (new SecurityLog())->log($enable ? '2fa_enabled' : '2fa_disabled', (int)$user['id'], $user['email']);
        $this->success($enable ? t('Two-factor authentication enabled. You will receive a code by email when you log in.') : t('Two-factor authentication disabled.'), null, ['enabled' => $enable]);
    }

    /** Farmer account deletion (soft delete; login is blocked afterwards). */
    public function delete(): void
    {
        $user = Auth::user();
        if (!$user) {
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'message' => t('You must be logged in to delete your account.')], 401);
            }
            Session::flash('error', t('You must be logged in to delete your account.'));
            $this->redirect('auth/login');
        }
        if ($user['role'] !== 'farmer') {
            throw new HttpException(403, t('Only farmer accounts can be deleted from the profile page.'));
        }
        $this->requirePost();
        $errors = [];
        if ($this->input('confirmation') !== 'DELETE') {
            $errors['confirmation'] = t('Type DELETE to confirm.');
        }
        if (!password_verify((string)($_POST['password'] ?? ''), $user['password'])) {
            $errors['password'] = t('Current password is incorrect');
        }
        if ($errors) {
            $this->fail(t('Failed to process account deletion request. Please try again.'), $errors);
        }
        if ((new Booking())->hasActiveRentals((int)$user['id'])) {
            $this->fail(t('Please complete or cancel your active rentals before deleting your account.'));
        }
        try {
            $this->users->softDelete((int)$user['id']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to delete account data. Please try again.'));
        }
        (new SecurityLog())->log('account_deleted', (int)$user['id'], $user['email']);
        Auth::logout();
        $this->success(t('Your account has been deleted successfully.'), url('auth/login'));
    }
}
