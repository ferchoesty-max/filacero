<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde CLI.');
}

$tmpDb = sys_get_temp_dir() . DIRECTORY_SEPARATOR
    . 'filacero_smoke_' . getmypid() . '_' . bin2hex(random_bytes(4)) . '.sqlite';

putenv('FILACERO_DB_PATH=' . $tmpDb);
$_ENV['FILACERO_DB_PATH'] = $tmpDb;

require dirname(__DIR__) . '/src/bootstrap.php';

register_shutdown_function(static function () use ($tmpDb): void {
    db_close();
    gc_collect_cycles();
    foreach ([$tmpDb, $tmpDb . '-wal', $tmpDb . '-shm'] as $archivo) {
        if (file_exists($archivo) && !@unlink($archivo)) {
            fwrite(STDERR, "Advertencia: no se pudo eliminar el archivo temporal {$archivo}\n");
        }
    }
});

$pasadas = 0;
$fallidas = 0;

/**
 * Verifica una condición booleana dentro de una prueba.
 */
function check(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

/**
 * Comprueba que una operación lance una excepción esperada.
 */
function debe_rechazar(callable $fn, string $mensaje): void
{
    try {
        $fn();
    } catch (Throwable) {
        return;
    }
    throw new RuntimeException($mensaje);
}

/**
 * Ejecuta un caso de prueba e imprime su resultado.
 */
function prueba(string $id, string $descripcion, callable $fn): void
{
    global $pasadas, $fallidas;
    try {
        $fn();
        $pasadas++;
        echo "[OK] {$id} — {$descripcion}\n";
    } catch (Throwable $ex) {
        $fallidas++;
        echo "[FAIL] {$id} — {$descripcion}: {$ex->getMessage()}\n";
    }
}

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "No se pudo leer database/schema.sql.\n");
    exit(1);
}

$db = db();
$db->exec($schema);

$normPath = static fn(string $p): string => strtolower(str_replace('\\', '/', realpath($p) ?: $p));
$dbList = $db->query('PRAGMA database_list')->fetchAll();
$dbFile = (string) ($dbList[0]['file'] ?? '');
$demoDb = dirname(__DIR__) . '/storage/filacero.sqlite';
if ($normPath($dbFile) !== $normPath($tmpDb) || $normPath($dbFile) === $normPath($demoDb)) {
    fwrite(STDERR, "Abortado: la conexión no apunta a la base temporal ({$dbFile}).\n");
    exit(1);
}
unset($dbList, $db);

$serviciosIds = [];
foreach ([
    ['ASE', 'Asesoría Académica', 'Orientación sobre materias, cargas y tutorías.'],
    ['CON', 'Constancias y Kardex', 'Emisión de constancias de estudio e historial académico.'],
    ['BEC', 'Becas y Apoyos', 'Información y seguimiento de becas institucionales.'],
    ['INS', 'Inscripciones', 'Trámites de inscripción, reinscripción y bajas.'],
] as [$prefijo, $nombre, $descripcion]) {
    $serviciosIds[$prefijo] = create_servicio([
        'prefijo'     => $prefijo,
        'nombre'      => $nombre,
        'descripcion' => $descripcion,
        'activo'      => 1,
    ]);
}

create_operador('Administración Demo', 'admin', 'Admin2026!', 'admin', 1);
create_operador('Operador Demo 2', 'operador2', 'Operador2026!', 'operador', 2);
create_operador('Operador Demo 3', 'operador3', 'Operador2026!', 'operador', 3);

prueba('T-01', 'Emitir turno genera ASE-001 en pendiente', static function () use ($serviciosIds): void {
    $turno = emitir_turno($serviciosIds['ASE']);
    check($turno['folio'] === 'ASE-001', 'Se esperaba folio ASE-001.');
    check($turno['estado'] === 'pendiente', 'Se esperaba estado pendiente.');
    check((int) $turno['consecutivo'] === 1, 'Se esperaba consecutivo 1.');

    $recuperado = find_turno((int) $turno['id']);
    check($recuperado !== null, 'find_turno() no recuperó el turno recién emitido.');
    check($recuperado['folio'] === 'ASE-001', 'El folio recuperado no coincide.');
    check($recuperado['servicio_prefijo'] === 'ASE', 'El prefijo del servicio recuperado no es ASE.');
});

prueba('T-03', 'Cada servicio tiene su propia serie y mantiene orden de llegada', static function () use ($serviciosIds): void {
    $segundoAse = emitir_turno($serviciosIds['ASE']);
    check($segundoAse['folio'] === 'ASE-002', 'Se esperaba ASE-002 como segundo turno de Asesoría.');
    check((int) $segundoAse['consecutivo'] === 2, 'Se esperaba consecutivo 2 para ASE-002.');

    $primeroCon = emitir_turno($serviciosIds['CON']);
    check($primeroCon['folio'] === 'CON-001', 'Se esperaba CON-001 al iniciar serie de Constancias.');
    check((int) $primeroCon['consecutivo'] === 1, 'Se esperaba consecutivo 1 para CON-001.');

    $cola = cola_pendiente();
    $folios = array_column($cola, 'folio');
    check($folios === ['ASE-001', 'ASE-002', 'CON-001'], 'La cola no conserva el orden de llegada esperado.');
});

prueba('T-06', 'No emite turno de servicio inexistente', static function (): void {
    debe_rechazar(
        static fn() => emitir_turno(99999),
        'Debe rechazarse emitir turno para un servicio inexistente.'
    );
    // Pendiente ampliar T-06 con servicio inactivo al integrar HU-07
});

prueba('T-15', 'Prefijo repetido o inválido se rechaza en BD', static function (): void {
    debe_rechazar(
        static fn() => create_servicio(['prefijo' => 'ASE', 'nombre' => 'Duplicado']),
        'Debe rechazarse un prefijo repetido.'
    );
    debe_rechazar(
        static fn() => create_servicio(['prefijo' => 'A', 'nombre' => 'Muy corto']),
        'Debe rechazarse un prefijo menor a 2 caracteres.'
    );
    debe_rechazar(
        static fn() => create_servicio(['prefijo' => 'ABCDE', 'nombre' => 'Muy largo']),
        'Debe rechazarse un prefijo mayor a 4 caracteres.'
    );
    // Pendiente ampliar T-15 con validate_servicio() al integrar HU-06
});

/*
 * Pendientes de Semana 2 por integrar módulos de compañeros:
 * - T-06 (servicio inactivo -> HU-07)
 * - T-07, T-08, T-11 (-> HU-08, src/atencion.php)
 * - T-15 (validate_servicio -> HU-06, src/servicios.php)
 */

$total = $pasadas + $fallidas;
echo "\nResumen: {$pasadas}/{$total} pruebas OK (base temporal limpia)\n";
exit($fallidas === 0 ? 0 : 1);
