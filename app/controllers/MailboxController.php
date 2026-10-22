<?php
declare(strict_types=1);

/**
 * Local outbox for the "log" mail driver, so verification links and OTP codes
 * can be used on XAMPP without an SMTP server. Only available from this computer.
 */
class MailboxController extends Controller
{
    private function guard(): void
    {
        $local = in_array(client_ip(), ['127.0.0.1', '::1'], true);
        if (config('mail.driver') !== 'log' || (!$local && !config('app.debug'))) {
            throw new HttpException(404);
        }
    }

    public function index(): void
    {
        $this->guard();
        $files = glob(STORAGE_PATH . '/mail/*.html') ?: [];
        rsort($files);
        $mails = [];
        foreach (array_slice($files, 0, 60) as $file) {
            $handle = fopen($file, 'r');
            $first = (string)fgets($handle);
            fclose($handle);
            $meta = preg_match('/^<!--(.+)-->/', $first, $m) ? json_decode((string)base64_decode($m[1]), true) : null;
            $mails[] = ['file' => basename($file)] + ($meta ?: ['to' => '?', 'subject' => basename($file), 'date' => date('Y-m-d H:i:s', filemtime($file))]);
        }
        $this->view('mailbox/index', ['title' => t('Mail Outbox'), 'mails' => $mails, 'current' => $this->input('file') ?: ($mails[0]['file'] ?? '')], Auth::check() ? 'app' : 'public');
    }

    public function show(): void
    {
        $this->guard();
        $file = $this->input('file');
        if (!preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.html$/', $file) || !is_file(STORAGE_PATH . '/mail/' . $file)) {
            throw new HttpException(404);
        }
        header('Content-Type: text/html; charset=utf-8');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:");
        echo '<base target="_top">'; // links (e.g. "Verify Email") open in the main window, not the preview frame
        readfile(STORAGE_PATH . '/mail/' . $file);
        exit;
    }
}
