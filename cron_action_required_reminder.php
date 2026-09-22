<?php
// Cron script: υπενθύμιση email στους πελάτες με αιτήσεις Novus σε status ACTION_REQUIRED
// (απορρίφθηκε το ανεβασμένο αρχείο σύμβασης, περιμένουμε νέο ανέβασμα) ή PENDING_SIGNATURE
// (δεν έχουν καν ανεβάσει υπογεγραμμένη σύμβαση ακόμη). Στέλνει το πολύ 1 υπενθύμιση ανά
// αίτηση ανά 24ωρο.
//
// Παράδειγμα cron entry (τρέχει καθημερινά στις 09:00):
//   0 9 * * * /usr/bin/php /home/.../paroxos/cron_action_required_reminder.php >> /home/.../paroxos/cron.log 2>&1

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Αυτό το script τρέχει μόνο από cron/CLI.\n");
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';

$requests = list_action_required_requests_needing_reminder();

echo date('c') . " — βρέθηκαν " . count($requests) . " αιτήσεις (ACTION_REQUIRED/PENDING_SIGNATURE) προς υπενθύμιση.\n";

foreach ($requests as $req) {
    $company = get_company_by_afm($req['company_afm']);
    $companyName = $company['onomasia'] ?? $req['company_afm'];

    $viewUrl = rtrim((string) $APP_BASE_URL, '/') . '/novus_request_view.php?id=' . urlencode($req['novus_request_id']);

    $messageHtml = 'Φαίνεται ότι δεν έχετε προχωρήσει με όλα τα βήματα της διαδικασίας ενεργοποίησης '
        . 'παρόχου ηλεκτρονικής τιμολόγησης. Επισκεφτείτε τη σελίδα '
        . '<a href="' . htmlspecialchars($viewUrl, ENT_QUOTES) . '">' . htmlspecialchars($viewUrl, ENT_QUOTES) . '</a>'
        . ' για να ολοκληρώσετε τη διαδικασία.'
        . '<br><br>'
        . 'Αν έχετε κάποια απορία, επικοινωνήστε με το '
        . '<a href="mailto:support@totalschool.io">support@totalschool.io</a>';

    $html = render_notification_email($messageHtml);
    $subject = 'Υπενθύμιση: απαιτείται ενέργεια στην αίτησή σας — ' . $companyName;

    $mailResult = smtp_send_mail($req['customer_email'], $subject, $html, null, $mail_to);

    if ($mailResult['ok']) {
        mark_reminder_sent($req['novus_request_id']);
        echo "  OK   {$req['novus_request_id']} -> {$req['customer_email']}\n";
    } else {
        echo "  FAIL {$req['novus_request_id']} -> {$req['customer_email']}: {$mailResult['error']}\n";
        error_log('cron_action_required_reminder: αποτυχία αποστολής σε ' . $req['novus_request_id'] . ': ' . $mailResult['error']);
    }
}

echo date('c') . " — ολοκληρώθηκε.\n";
