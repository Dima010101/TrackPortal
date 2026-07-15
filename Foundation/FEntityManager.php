<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

/**
 * bootstrap Doctrine ORM
 */
class FEntityManager
{
    private static ?EntityManagerInterface $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): EntityManagerInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $baseDir = defined('TRACKPORTAL_BASE_DIR') ? TRACKPORTAL_BASE_DIR : dirname(__DIR__);
        $proxyDir = $baseDir . '/var/cache/doctrine/proxies';
        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, 0777, true);
        }

        // Credenziali dalla configurazione d'ambiente (costanti DB_*, definite
        // dal config esterno caricato in index.php), con default XAMPP locale.
        $dbName = defined('DB_NAME') ? (string) constant('DB_NAME') : 'trackportal';

        try {
            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: [$baseDir . '/Entity'],
                isDevMode: true,
                proxyDir: $proxyDir,
            );

            $connection = DriverManager::getConnection([
                'dbname'   => $dbName,
                'user'     => defined('DB_USER') ? (string) constant('DB_USER') : 'root',
                'password' => defined('DB_PASS') ? (string) constant('DB_PASS') : '',
                'host'     => defined('DB_HOST') ? (string) constant('DB_HOST') : 'localhost',
                'port'     => defined('DB_PORT') ? (string) constant('DB_PORT') : '3306',
                'driver'   => 'pdo_mysql',
                'charset'  => defined('DB_CHARSET') ? (string) constant('DB_CHARSET') : 'utf8mb4',
            ]);

            self::$instance = new EntityManager($connection, $config);
        } catch (DBALException $e) {
            http_response_code(500);
            die(
                '<h1>Database non raggiungibile</h1>'
                . '<p>Verifica che MySQL di XAMPP sia avviato e che il database <code>'
                . htmlspecialchars($dbName) . '</code> sia stato importato.</p>'
                . '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>'
            );
        }

        return self::$instance;
    }
}
