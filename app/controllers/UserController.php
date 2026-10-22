<?php
declare(strict_types=1);

/** Admin user management. */
class UserController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $this->role('admin');
        $f = [
            'q' => $this->input('q'),
            'role' => in_array($this->input('role'), ['farmer', 'employee', 'admin'], true) ? $this->input('role') : '',
            'status' => in_array($this->input('status'), ['inactive', 'active', 'suspended', 'deleted'], true) ? $this->input('status') : '',
        ];
        [$rows, $meta] = $this->users->paginateList($f, max(1, $this->int('page', 1)));
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('user/_table', $data);
        }
        $this->view('user/index', $data + ['title' => t('User Management')]);
    }

    /** Admin creates employee/admin accounts (pre-verified) and sends a set-password link. */
    public function store(): void
    {
        $admin = $this->role('admin');
        $this->requirePost();
        $v = new Validator($_POST);
        $email = mb_strtolower($v->value('email'));
        $v->required('name', t('Name is required'))
            ->required('email', t('Email is required'))->email('email', t('Invalid email address'))
            ->check('email', $email === '' || !$this->users->emailExists($email), t('Email already used'))
            ->required('phone', t('Phone number is required'))->regex('phone', '/^(\+?88)?01[3-9]\d{8}$/', t('Invalid phone number'))
            ->required('role', t('Please select a user type'))->in('role', ['farmer', 'employee', 'admin'], t('Please select a user type'));
        if ($v->fails()) {
            $this->fail(t('Please fix the highlighted fields.'), $v->errors());
        }
        $id = $this->users->create([
            'name' => $v->value('name'),
            'email' => $email,
            'phone' => $v->value('phone'),
            'address' => $v->value('address'),
            'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'role' => $v->value('role'),
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_status' => 'approved',
            'verified_by' => (int)$admin['id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
        $sent = $this->sendSetPasswordLink($this->users->find($id), true);
        (new SecurityLog())->log('user_created', $id, $email, 'Created by ' . $admin['name']);
        $this->success($sent ? t('Account created. A link to set the password was emailed to :email.', ['email' => $email]) : t('Account created, but the email could not be sent.'), url('user/index'));
    }

    private function sendSetPasswordLink(array $user, bool $welcome): bool
    {
        $issued = (new Token())->issue((int)$user['id'], 'password_reset', 48 * 3600, (int)config('security.otp_seconds', 120));
        $heading = $welcome ? t('Welcome to AgriRent') : t('Password reset request');
        $body = '<p>' . e(t('Hi :name,', ['name' => $user['name']])) . '</p><p>' . e($welcome
            ? t('An administrator created an AgriRent :role account for you. Click below to set your password (link valid for 48 hours).', ['role' => role_label($user['role'])])
            : t('An administrator requested a password reset for your account. Click below to choose a new password (link valid for 48 hours).')) . '</p>';
        return Notifier::email($user['email'], $heading, $heading, $body, t('Set Password'), abs_url('auth/reset-password', ['token' => $issued['token']]), true);
    }

    public function status(): void
    {
        $admin = $this->role('admin');
        $this->requirePost();
        $target = $this->users->find($this->int('id'));
        $status = $this->input('status');
        if (!$target || $target['role'] === 'admin' || !in_array($status, ['active', 'suspended'], true) || $target['status'] === 'deleted') {
            $this->fail(t('This account cannot be changed.'));
        }
        if ($status === 'active' && !$target['email_verified_at']) {
            $this->users->markEmailVerified((int)$target['id']);
        }
        $this->users->setStatus((int)$target['id'], $status);
        if ($status === 'suspended') {
            $this->users->clearRememberToken((int)$target['id']);
        }
        (new SecurityLog())->log('user_' . $status, (int)$target['id'], $target['email'], 'By ' . $admin['name']);
        $this->success($status === 'active' ? t(':name has been activated.', ['name' => $target['name']]) : t(':name has been deactivated.', ['name' => $target['name']]));
    }

    public function resetPassword(): void
    {
        $admin = $this->role('admin');
        $this->requirePost();
        $target = $this->users->find($this->int('id'));
        if (!$target || $target['status'] === 'deleted') {
            $this->fail(t('This account cannot be changed.'));
        }
        if (!$this->sendSetPasswordLink($target, false)) {
            $this->fail(t('The reset email could not be sent. Please try again later.'));
        }
        (new SecurityLog())->log('admin_password_reset', (int)$target['id'], $target['email'], 'By ' . $admin['name']);
        $this->success(t('A password reset link was sent to :email.', ['email' => $target['email']]));
    }
}
