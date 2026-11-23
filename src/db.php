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

/**
 * Ejecuta $bloque dentro de una transaccion BEGIN IMMEDIATE.
 *
 * BEGIN IMMEDIATE toma el candado de escritura de SQLite desde el inicio:
 * si otra peticion quiere escribir al mismo tiempo, espera (busy_timeout)
 * a que esta termine. Si todo sale bien hace COMMIT; si hay un error,
 * ROLLBACK y vuelve a lanzar el error.
 */
function with_write_lock(callable $bloque): mixed
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $resultado = $bloque($pdo);
        $pdo->exec('COMMIT');
        return $resultado;
    } catch (Throwable $ex) {
        $pdo->exec('ROLLBACK');
        throw $ex;
    }
}
