<?php
declare(strict_types=1);

use Compose\Web\Config;
use Compose\Starter;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$config = array_replace_recursive((new Config())(), [
    'app' => ['name' => 'Compose Web'],
    'template' => ['dir' => __DIR__ . '/../templates'],
    'pages' => ['dir' => __DIR__ . '/../pages'],
]);

Starter::start($config);
