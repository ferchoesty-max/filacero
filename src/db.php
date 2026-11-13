<?php
declare(strict_types=1);

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }
    if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('Falta la extensión pdo_sqlite. Revisa php.ini o la guía de instalación.');
    }
    $path = getenv('FILACERO_DB_PATH') ?: dirname(__DIR__) . '/storage/filacero.sqlite';
    if (!is_dir(dirname($path))) {
        throw new RuntimeException('El directorio storage no existe.');
    }
    $connection = new PDO('sqlite:' . $path);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $connection->exec('PRAGMA foreign_keys = ON');
    $connection->exec('PRAGMA busy_timeout = 3000');

    return $connection;
}
