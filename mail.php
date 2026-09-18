<?php
// Ελάχιστος SMTP client (χωρίς εξωτερικές βιβλιοθήκες), για αποστολή email
// μέσω Amazon SES SMTP ή οποιουδήποτε SMTP server με STARTTLS + AUTH LOGIN.

require_once __DIR__ . '/config.php';

function smtp_read_response($socket) {
    $data = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $data .= $line;
        // Τελευταία γραμμή απάντησης: "250 ..." (χωρίς παύλα μετά τον κωδικό).
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    return $data;
}

function smtp_command($socket, $command, $expectedCode) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtp_read_response($socket);
    $code = (int) substr($response, 0, 3);

    if ($code !== $expectedCode) {
        throw new RuntimeException("SMTP σφάλμα: αναμενόταν $expectedCode, ελήφθη: " . trim($response));
    }

    return $response;
}

// Στέλνει email μέσω SMTP. Επιστρέφει ['ok' => bool, 'error' => string|null].
function smtp_send_mail($to, $subject, $htmlBody, $textBody = null) {
    global $mailhost, $mailport, $mailusername, $mailpassword, $mail_from_email, $mail_from_name;

    if (empty($mailhost) || empty($mailusername) || empty($mailpassword)) {
        return ['ok' => false, 'error' => 'Λείπουν οι ρυθμίσεις SMTP στο config.php'];
    }

    $port = $mailport ?? 587;
    $textBody = $textBody ?: strip_tags($htmlBody);

    $socket = null;

    try {
        $socket = @stream_socket_client("tcp://{$mailhost}:{$port}", $errno, $errstr, 15);
        if (!$socket) {
            throw new RuntimeException("Αδυναμία σύνδεσης στο SMTP server: $errstr ($errno)");
        }

        stream_set_timeout($socket, 15);

        smtp_read_response($socket); // greeting 220
        smtp_command($socket, 'EHLO ' . php_uname('n'), 250);
        smtp_command($socket, 'STARTTLS', 220);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Αποτυχία ενεργοποίησης TLS.');
        }

        smtp_command($socket, 'EHLO ' . php_uname('n'), 250);
        smtp_command($socket, 'AUTH LOGIN', 334);
        smtp_command($socket, base64_encode($mailusername), 334);
        smtp_command($socket, base64_encode($mailpassword), 235);

        smtp_command($socket, 'MAIL FROM:<' . $mail_from_email . '>', 250);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', 250);
        smtp_command($socket, 'DATA', 354);

        $boundary = 'paroxos-' . bin2hex(random_bytes(8));
        $fromHeader = '=?UTF-8?B?' . base64_encode($mail_from_name) . '?= <' . $mail_from_email . '>';
        $subjectHeader = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [
            'From: ' . $fromHeader,
            'To: <' . $to . '>',
            'Subject: ' . $subjectHeader,
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . php_uname('n') . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($textBody))
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($htmlBody))
            . "--{$boundary}--\r\n";

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        // Γραμμές που ξεκινούν με τελεία πρέπει να "διπλασιαστούν" (SMTP dot-stuffing).
        $message = preg_replace('/^\./m', '..', $message);

        fwrite($socket, $message . "\r\n.\r\n");
        smtp_command($socket, null, 250);

        smtp_command($socket, 'QUIT', 221);
        fclose($socket);

        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        if ($socket) {
            fclose($socket);
        }
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}
