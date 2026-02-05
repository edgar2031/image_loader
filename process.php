<?php

// Autoload classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Configuration
$storageDir = __DIR__ . '/processed/';
$targetSize = 200;

// Initialize and run application
$app = new ImageLoaderApp($storageDir, $targetSize);
$app->handleRequest();
