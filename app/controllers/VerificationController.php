<?php
declare(strict_types=1);

/** Account verification: employees verify farmers; admins verify farmers and employees. */
class VerificationController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    private function roles(array $user): array
    {
        return $user['role'] === 'admin' ? ['farmer', 'employee'] : ['farmer'];
    }

    public function index(): void
    {
        $user = $this->role('employee', 'admin');
        $status = in_array($this->input('status'), ['pending', 'approved', 'rejected', 'all'], true) ? $this->input('status') : 'pending';
        try {
            $rows = $this->users->verificationQueue($this->roles($user), $status, $this->input('q'));
        } catch (Throwable $e) {
            $this->fail(t('Unable to load verification requests.'), [], url('dashboard/index'));
        }
        $data = ['rows' => $rows, 'status' => $status, 'q' => $this->input('q')];
        if ($this->wantsPartial()) {
            $this->partial('verification/_list', $data);
        }
        $selected = $rows ? $rows[0] : null;
        $this->view('verification/index', $data + ['title' => t('Farmer Verification'), 'selected' => $selected]);
    }

    public function show(): void
    {
        $user = $this->role('employee', 'admin');
        $target = $this->users->find($this->int('id'));
        if (!$target || !in_array($target['role'], $this->roles($user), true)) {
            $this->json(['ok' => false, 'message' => t('Unable to load farmer details.')], 404);
        }
        $this->json(['ok' => true, 'html' => View::partial('verification/_panel', ['u' => $target])]);
    }

    public function decide(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $target = $this->users->find($this->int('id'));
        $decision = $this->input('decision');
        $note = mb_substr($this->input('note'), 0, 255);
        if (!$target || !in_array($target['role'], $this->roles($user), true) || !in_array($decision, ['approved', 'rejected'], true)) {
            $this->fail(t('User verification failed. Please try again.'));
        }
        if ($decision === 'rejected' && $note === '') {
            $this->fail(t('Please explain why the account was rejected.'), ['note' => t('Please explain why the account was rejected.')]);
        }
        try {
            if (!$this->users->setVerification((int)$target['id'], $decision, (int)$user['id'], $decision === 'rejected' ? $note : null)) {
                throw new RuntimeException('unchanged');
            }
        } catch (Throwable $e) {
            $this->fail(t('User verification failed. Please try again.'));
        }
        (new SecurityLog())->log('verification_' . $decision, (int)$target['id'], $target['email'], 'By ' . $user['name'] . ($note ? ': ' . $note : ''));
        $text = $decision === 'approved'
            ? t('Your account has been verified. You can now book equipment.')
            : t('Your account verification was rejected: :note Please upload a valid document from your profile.', ['note' => $note]);
        $sent = Notifier::send((int)$target['id'], $decision === 'approved' ? 'Account verified' : 'Verification rejected', $text, url('profile/index'), $decision === 'approved' ? 'success' : 'danger');
        $sent = Notifier::email($target['email'], t('AgriRent account verification'), $decision === 'approved' ? t('Account verified') : t('Verification rejected'), '<p>' . e($text) . '</p>', t('Open AgriRent'), abs_url('profile/index')) && $sent;
        $message = $sent
            ? ($decision === 'approved' ? t(':name has been approved.', ['name' => $target['name']]) : t(':name has been rejected.', ['name' => $target['name']]))
            : t('Verification completed, but notification could not be sent.');
        $this->success($message, null, ['removed' => (int)$target['id']]);
    }

    /** Streams an uploaded verification document (never publicly accessible). */
    public function document(): void
    {
        $user = $this->auth();
        $target = $this->users->find($this->int('id'));
        $allowed = $target && ((int)$target['id'] === (int)$user['id'] || ($user['role'] !== 'farmer' && in_array($target['role'], $this->roles($user), true)));
        $root = realpath(BASE_PATH . '/uploads/verification');
        $file = $allowed && $target['verification_document'] ? realpath(BASE_PATH . '/' . $target['verification_document']) : false;
        if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) {
            throw new HttpException(404, t('Unable to load farmer details.'));
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="verification-' . (int)$target['id'] . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($file);
        exit;
    }
}
