<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}

date_default_timezone_set('Asia/Tehran');

/*
 * تنظیمات اتصال به پایگاه داده
 */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pishkhan_yar');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // ۱. تلاش برای اتصال به MySQL با رمزهای معمول (خالی یا root)
    $passwordsToTry = [DB_PASS, '', 'root'];
    $mysqlConnected = false;

    foreach (array_unique($passwordsToTry) as $pass) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $mysqlConnected = true;
            break;
        } catch (PDOException $e) {
            // اگر دیتابیس وجود نداشت سعی می‌کنیم بسازیم
            if ($e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    $rootPdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, $pass);
                    $rootPdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    $schemaFile = __DIR__ . '/database/schema.sql';
                    if (file_exists($schemaFile)) {
                        $rootPdo->exec('USE `' . DB_NAME . '`');
                        $rootPdo->exec(file_get_contents($schemaFile));
                    }
                    $pdo = new PDO($dsn, DB_USER, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    $mysqlConnected = true;
                    break;
                } catch (Throwable) {}
            }
        }
    }

    // ۲. اگر MySQL فعال نبود، خودکار به SQLite محلی متصل می‌شویم تا سایت در هر شرایطی بدون خطا باز شود
    if (!$mysqlConnected) {
        $sqlitePath = __DIR__ . '/database/pishkhan_yar.sqlite';
        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    return $pdo;
}
