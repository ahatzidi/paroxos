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
