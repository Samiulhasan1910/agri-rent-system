<?php
declare(strict_types=1);

/**
 * Sends HTML email via SMTP, PHP mail() or the local "log" outbox
 * (storage/mail, readable at index.php?r=mailbox) used for offline demos.
 */
final class Mailer
{
    public static ?string $lastError = null;

    public static function send(string $to, string $subject, string $html): bool
    {
        self::$lastError = null;
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid recipient address.';
            return false;
        }
        $driver = (string)config('mail.driver', 'log');
        try {
            if ($driver === 'smtp') {
                return self::smtp($to, $subject, $html);
            }
            if ($driver === 'mail') {
                return @mail($to, self::encodeHeader($subject), $html, implode("\r\n", self::headers($to, false)));
            }
            return self::log($to, $subject, $html);
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    /** Branded HTML email layout. */
    public static function template(string $heading, string $bodyHtml, ?string $buttonText = null, ?string $buttonUrl = null): string
    {
        $name = e(setting('platform_name', 'AgriRent'));
        $button = $buttonText && $buttonUrl
            ? '<p style="margin:28px 0;text-align:center"><a href="' . e($buttonUrl) . '" style="background:#16803c;color:#fff;padding:13px 26px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">' . e($buttonText) . '</a></p>'
              . '<p style="font-size:12px;color:#6b7c72;word-break:break-all">' . e(t('If the button does not work, copy this link into your browser:')) . '<br>' . e($buttonUrl) . '</p>'
            : '';
        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>'
            . '<body style="margin:0;background:#f1f6f2;font-family:Arial,Helvetica,sans-serif;color:#1d2b22">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:28px 12px"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #dfeae2">'
            . '<tr><td style="background:#16803c;padding:18px 28px;color:#fff;font-size:20px;font-weight:700">&#127807; ' . $name . '</td></tr>'
            . '<tr><td style="padding:28px">'
            . '<h1 style="font-size:21px;margin:0 0 14px;color:#14532d">' . e($heading) . '</h1>'
            . '<div style="font-size:15px;line-height:1.6">' . $bodyHtml . '</div>' . $button
            . '</td></tr><tr><td style="background:#f6faf7;padding:16px 28px;font-size:12px;color:#6b7c72">'
            . e(t('This is an automated message from :name. Need help? Contact :email.', ['name' => setting('platform_name', 'AgriRent'), 'email' => setting('support_email', 'support@agrirent.com')]))
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private static function log(string $to, string $subject, string $html): bool
    {
        $dir = STORAGE_PATH . '/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $meta = json_encode(['to' => $to, 'subject' => $subject, 'date' => date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.html';
        return file_put_contents($file, '<!--' . base64_encode((string)$meta) . "-->\n" . $html) !== false;
    }

    private static function headers(string $to, bool $includeTo = true): array
    {
        $from = (string)config('mail.from_email');
        $name = self::encodeHeader((string)config('mail.from_name', 'AgriRent'));
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $name . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', $from)[1] ?? 'agrirent.local') . '>',
        ];
        if ($includeTo) {
            array_unshift($headers, 'To: <' . $to . '>');
        }
        return $headers;
    }

    private static function encodeHeader(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private static function smtp(string $to, string $subject, string $html): bool
    {
        $c = config('mail');
        $remote = ($c['encryption'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['host'] . ':' . (int)$c['port'];
        $socket = @stream_socket_client($remote, $errno, $errstr, 12);
        if (!$socket) {
            self::$lastError = "SMTP connection failed: {$errstr} ({$errno})";
            return false;
        }
        stream_set_timeout($socket, 12);

        $read = static function () use ($socket): string {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $command = static function (?string $cmd, array $expect) use ($socket, $read): string {
            if ($cmd !== null) {
                fwrite($socket, $cmd . "\r\n");
            }
            $response = $read();
            if (!in_array((int)substr($response, 0, 3), $expect, true)) {
                throw new RuntimeException('SMTP error: ' . trim($response));
            }
            return $response;
        };

        try {
            $command(null, [220]);
            $command('EHLO agrirent.local', [250]);
            if ($c['encryption'] === 'tls') {
                $command('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Could not start TLS encryption.');
                }
                $command('EHLO agrirent.local', [250]);
            }
            if ((string)$c['username'] !== '') {
                $command('AUTH LOGIN', [334]);
                $command(base64_encode((string)$c['username']), [334]);
                $command(base64_encode((string)$c['password']), [235]);
            }
            $command('MAIL FROM:<' . $c['from_email'] . '>', [250]);
            $command('RCPT TO:<' . $to . '>', [250, 251]);
            $command('DATA', [354]);
            $message = implode("\r\n", self::headers($to)) . "\r\nSubject: " . self::encodeHeader($subject) . "\r\n\r\n"
                . chunk_split(base64_encode($html)) . "\r\n.";
            $command($message, [250]);
            $command('QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();
            fclose($socket);
            return false;
        }
    }
}
