<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'Paroxos\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Λείπει το config/config.php. Αντιγράψτε το config/config.example.php και συμπληρώστε τα στοιχεία σας.');
}
