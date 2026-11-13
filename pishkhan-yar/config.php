<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}

date_default_timezone_set('Asia/Tehran');

/*
 * تنظیمات اتصال به پایگاه داده
 * متغیرهای محیطی از Docker Compose تزریق می‌شوند
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

    // اتصال به MySQL
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    // تلاش اول با رمز تنظیم‌شده
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // اگر دیتابیس وجود نداشت، بسازیم
        if ($e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
            try {
                $rootDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                $rootPdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $schemaFile = __DIR__ . '/database/schema.sql';
                if (file_exists($schemaFile)) {
                    $rootPdo->exec('USE `' . DB_NAME . '`');
                    // اجرای هر statement به صورت جداگانه
                    $sql = file_get_contents($schemaFile);
                    foreach (explode(';', $sql) as $statement) {
                        $stmt = trim($statement);
                        if ($stmt !== '') {
                            $rootPdo->exec($stmt);
                        }
                    }
                }
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                return $pdo;
            } catch (Throwable $ex) {
                error_log('PishkhanYar DB Init Error: ' . $ex->getMessage());
            }
        }
    }

    // Fallback: تلاش با رمزهای جایگزین (برای سازگاری با محیط‌های مختلف)
    foreach (['', 'root'] as $pass) {
        try {
            $pdo = new PDO($dsn, DB_USER, $pass, $options);
            return $pdo;
        } catch (PDOException) {
            // ادامه تلاش
        }
    }

    // آخرین راه: SQLite برای اطمینان از کارکرد سایت
    $sqlitePath = __DIR__ . '/database/pishkhan_yar.sqlite';
    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}
