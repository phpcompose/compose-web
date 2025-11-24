<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

$connection = DriverManager::getConnection([
    'url' => $_ENV['DB_URL'] ?? null,
    'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? null,
    'dbname' => $_ENV['DB_NAME'] ?? null,
    'user' => $_ENV['DB_USER'] ?? null,
    'password' => $_ENV['DB_PASSWORD'] ?? null,
]);

return DependencyFactory::fromConnection(
    new PhpFile(__DIR__ . '/migrations.php'),
    new ExistingConnection($connection)
);
