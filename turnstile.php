<?php
// Επαλήθευση Cloudflare Turnstile token στο backend.

require_once __DIR__ . '/config.php';

// Επιστρέφει true/false. Ποτέ μην εμπιστεύεστε μόνο τον client-side έλεγχο.
function turnstile_verify($token, $remoteIp = null) {
    global $TURNSTILE_SECRET_KEY;

    if (empty($TURNSTILE_SECRET_KEY) || empty($token)) {
        return false;
    }

    $postFields = [
        'secret' => $TURNSTILE_SECRET_KEY,
        'response' => $token,
    ];
    if ($remoteIp) {
        $postFields['remoteip'] = $remoteIp;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $body = curl_exec($ch);
    curl_close($ch);

    if ($body === false) {
        return false;
    }

    $result = json_decode($body, true);

    return !empty($result['success']);
}
