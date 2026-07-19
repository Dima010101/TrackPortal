<?php

declare(strict_types=1);

// entry point
if (!defined('TRACKPORTAL_BASE_DIR')) {
    define('TRACKPORTAL_BASE_DIR', __DIR__);
}

$trackPortalComposer = TRACKPORTAL_BASE_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (!is_file($trackPortalComposer)) {
    http_response_code(500);
    die(
        '<h1>Dipendenze mancanti</h1><p>Esegui <code>composer install</code> nella cartella '
        . htmlspecialchars(TRACKPORTAL_BASE_DIR, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '.</p>'
    );
}
require_once $trackPortalComposer;

// configurazione d'ambiente
$trackPortalConfig = "C:/xampp/Trackportal-config/config.php";
if (!is_file($trackPortalConfig)) {
    http_response_code(500);
    die(
        '<h1>Configurazione mancante</h1><p>Copia <code>config.example.php</code> in <code>'
        . htmlspecialchars($trackPortalConfig, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        . '</code> e imposta le credenziali.</p>'
    );
}
require_once $trackPortalConfig;

// Le eccezioni non gestite sono intercettate da CFrontController::run().
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set(APP_TIMEZONE);

require_once TRACKPORTAL_BASE_DIR . '/support/helpers.php';
require_once TRACKPORTAL_BASE_DIR . '/support/smarty.php';

CAuth::startSession();

// unico punto di avvio, ricostruisce controller+azione dall'URL
(new CFrontController())->run();
