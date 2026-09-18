<?php
// Αντιγράψτε αυτό το αρχείο σε config.php και συμπληρώστε τα στοιχεία σας.
// Το config.php ΔΕΝ πρέπει να μπει ποτέ σε git (βλ. .gitignore).

return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'paroxos',
        'username' => 'paroxos_user',
        'password' => 'change_me',
        'charset'  => 'utf8mb4',
    ],

    // Στοιχεία πρόσβασης στο webservice Μητρώου της ΑΑΔΕ (RgWsPublic2).
    // Γίνεται δωρεάν εγγραφή/ενεργοποίηση από τον ΑΦΜ της επιχείρησής σας εδώ:
    // https://www1.aade.gr/webtax/wspublicreg/wspublicreg.php
    'aade' => [
        'wsdl'     => 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?wsdl',
        'username' => 'your_aade_username',
        'password' => 'your_aade_password',
        // Το ΑΦΜ της επιχείρησης/λογαριασμού για λογαριασμό του οποίου γίνεται η κλήση.
        'afm_called_by' => '000000000',
    ],
];
