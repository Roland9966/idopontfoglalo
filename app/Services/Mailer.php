<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class Mailer
{
    public function send(string $recipient, string $subject, string $body): void
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A címzett e-mail-címe érvénytelen.');
        }

        $driver = mb_strtolower((string) \config('mail.driver', 'log'));
        if ($driver === 'smtp') {
            $this->sendWithSmtp($recipient, $subject, $body);
            return;
        }
        if ($driver === 'mail') {
            $this->sendWithPhpMail($recipient, $subject, $body);
            return;
        }
        if ($driver !== 'log') {
            throw new RuntimeException('Ismeretlen levelezési meghajtó: ' . $driver);
        }

        $this->writeToLog($recipient, $subject, $body);
    }

    private function sendWithPhpMail(string $recipient, string $subject, string $body): void
    {
        $fromAddress = $this->fromAddress();
        $fromName = $this->safeHeader((string) \config('mail.from_name', 'Időpontfoglaló'));
        $headers = [
            'From: ' . $this->encodedHeader($fromName) . ' <' . $fromAddress . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        if (!mail($recipient, $this->encodedHeader($this->safeHeader($subject)), $body, implode("\r\n", $headers))) {
            throw new RuntimeException('A PHP mail() függvénye nem igazolta a levél elküldését.');
        }
    }

    private function writeToLog(string $recipient, string $subject, string $body): void
    {
        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s%s%s%s",
            gmdate('c'),
            $recipient,
            $this->safeHeader($subject),
            PHP_EOL,
            $body,
            PHP_EOL
        );
        if (file_put_contents(APP_ROOT . '/storage/mail.log', $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('A tesztlevél nem írható a storage/mail.log fájlba.');
        }
    }

    private function sendWithSmtp(string $recipient, string $subject, string $body): void
    {
        $host = trim((string) \config('mail.smtp.host', ''));
        $port = (int) \config('mail.smtp.port', 587);
        $encryption = mb_strtolower(trim((string) \config('mail.smtp.encryption', 'tls')));
        $username = trim((string) \config('mail.smtp.username', ''));
        $password = (string) \config('mail.smtp.password', '');
        $timeout = max(5, min(60, (int) \config('mail.smtp.timeout', 20)));

        if ($host === '' || $username === '' || $password === '' || $port < 1 || $port > 65535) {
            throw new RuntimeException('Az SMTP-beállítások hiányosak.');
        }
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            throw new RuntimeException('Az SMTP-titkosítás beállítása érvénytelen.');
        }

        $transport = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $remote = sprintf('%s://%s:%d', $transport, $host, $port);
        $errorNumber = 0;
        $errorMessage = '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => $host,
                'SNI_enabled' => true,
            ],
        ]);
        $socket = @stream_socket_client(
            $remote,
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('Nem sikerült kapcsolódni az SMTP-kiszolgálóhoz.');
        }

        stream_set_timeout($socket, $timeout);
        try {
            $this->expectResponse($socket, [220]);
            $clientName = parse_url((string) \config('url', ''), PHP_URL_HOST) ?: 'localhost';
            $clientName = preg_replace('/[^A-Za-z0-9.-]/', '', (string) $clientName) ?: 'localhost';
            $this->command($socket, 'EHLO ' . $clientName, [250]);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')
                    ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                    : STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    throw new RuntimeException('Az SMTP TLS-kapcsolat nem hozható létre.');
                }
                $this->command($socket, 'EHLO ' . $clientName, [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);

            $fromAddress = $this->fromAddress();
            $this->command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $message = $this->buildMessage($recipient, $subject, $body, $fromAddress);
            $this->writeAll($socket, $message . "\r\n.\r\n");
            $this->expectResponse($socket, [250]);
            try {
                $this->command($socket, 'QUIT', [221]);
            } catch (RuntimeException) {
                // A levél átadása már sikerült; a kapcsolat lezárási hibája nem teszi sikertelenné.
            }
        } finally {
            fclose($socket);
        }
    }

    private function buildMessage(string $recipient, string $subject, string $body, string $fromAddress): string
    {
        $fromName = $this->safeHeader((string) \config('mail.from_name', 'Időpontfoglaló'));
        $headers = [
            'Date: ' . gmdate(DATE_RFC2822),
            'From: ' . $this->encodedHeader($fromName) . ' <' . $fromAddress . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $this->encodedHeader($this->safeHeader($subject)),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . ($this->smtpMessageDomain() ?: 'localhost') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
        $normalizedBody = str_replace("\n", "\r\n", $normalizedBody);
        $normalizedBody = preg_replace('/^\./m', '..', $normalizedBody) ?? $normalizedBody;
        return implode("\r\n", $headers) . "\r\n\r\n" . rtrim($normalizedBody, "\r\n");
    }

    private function smtpMessageDomain(): string
    {
        $domain = substr(strrchr($this->fromAddress(), '@') ?: '', 1);
        return preg_replace('/[^A-Za-z0-9.-]/', '', $domain) ?: '';
    }

    private function fromAddress(): string
    {
        $address = trim((string) \config('mail.from_address', ''));
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A feladó e-mail-címe nincs megfelelően beállítva.');
        }
        return $address;
    }

    private function safeHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private function encodedHeader(string $value): string
    {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    /** @param resource $socket */
    private function command($socket, string $command, array $expectedCodes): string
    {
        $this->writeAll($socket, $command . "\r\n");
        return $this->expectResponse($socket, $expectedCodes);
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $result = fwrite($socket, substr($data, $written));
            if ($result === false || $result === 0) {
                throw new RuntimeException('Az SMTP-adatok nem küldhetők el.');
            }
            $written += $result;
        }
    }

    /** @param resource $socket */
    private function expectResponse($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $metadata = stream_get_meta_data($socket);
        if (!empty($metadata['timed_out'])) {
            throw new RuntimeException('Az SMTP-kiszolgáló válaszideje lejárt.');
        }
        $code = (int) substr($response, 0, 3);
        if ($response === '' || !in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('Az SMTP-kiszolgáló elutasította a kérést (kód: ' . $code . ').');
        }
        return $response;
    }
}
