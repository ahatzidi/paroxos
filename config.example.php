<?php
// Αντιγράψτε αυτό το αρχείο σε config.php και συμπληρώστε τα στοιχεία σας.
// Το config.php ΔΕΝ ανεβαίνει σε git (βλ. .gitignore).

// --- Στοιχεία σύνδεσης MariaDB/MySQL ---
$DB_HOST = '127.0.0.1';
$DB_NAME = 'paroxos';
$DB_USER = 'paroxos_user';
$DB_PASS = 'change_me';

// --- Στοιχεία πρόσβασης στο webservice Μητρώου της ΑΑΔΕ (RgWsPublic2) ---
// Δωρεάν εγγραφή/ενεργοποίηση από τον ΑΦΜ της επιχείρησής σας εδώ:
// https://www1.aade.gr/webtax/wspublicreg/wspublicreg.php
$AADE_WSDL = 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?wsdl';
$AADE_USERNAME = 'your_aade_username';
$AADE_PASSWORD = 'your_aade_password';
$AADE_AFM_CALLED_BY = '000000000'; // το ΑΦΜ σας, για λογαριασμό του οποίου γίνεται η κλήση

// --- Στοιχεία πρόσβασης στο Novus Onboarding API (πάροχος ηλεκτρονικής τιμολόγησης) ---
// Ξεκινήστε με το δοκιμαστικό URL/κλειδί, ζητήστε τα από τη Novus.
$NOVUS_API_URL = 'https://provider-dev.timologisi.online';
$NOVUS_API_KEY = 'nvuscnspts_your_key_here';

// Το secret του webhook. Η Novus το επιστρέφει ΜΙΑ ΦΟΡΑ όταν κάνετε
// POST /api/v1/webhooks (βλ. novus_webhook_register.php). Αντιγράψτε το εδώ.
$NOVUS_WEBHOOK_SECRET = '';

// Δημόσιο URL της εγκατάστασής σας (χωρίς τελικό /), για apsolute links σε emails.
$APP_BASE_URL = 'https://paroxos.totalschool.gr';

// ##########   Amazon SMTP    ##########
$mailhost = 'email-smtp.eu-west-1.amazonaws.com';
$mailport = 587;
$mailusername = 'your_ses_smtp_username';
$mailpassword = 'your_ses_smtp_password';
$mail_from_email = 'noreply@yourdomain.gr';
$mail_from_name = 'Paroxos';
$mail_to = 'you@yourdomain.gr'; // παραλήπτης ειδοποιήσεων webhook
