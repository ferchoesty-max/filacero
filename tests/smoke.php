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

$operadoresIds = [
    'admin'     => create_operador('Administración Demo', 'admin', 'Admin2026!', 'admin', 1),
    'operador2' => create_operador('Operador Demo 2', 'operador2', 'Operador2026!', 'operador', 2),
    'operador3' => create_operador('Operador Demo 3', 'operador3', 'Operador2026!', 'operador', 3),
];

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

prueba('T-06', 'No emite turno de servicio inactivo o inexistente', static function () use ($serviciosIds): void {
    debe_rechazar(
        static fn() => emitir_turno(99999),
        'Debe rechazarse emitir turno para un servicio inexistente.'
    );

    $desactivado = set_servicio_activo($serviciosIds['INS'], false);
    check($desactivado === true, 'set_servicio_activo() debe devolver true al desactivar INS.');

    debe_rechazar(
        static fn() => emitir_turno($serviciosIds['INS']),
        'Debe rechazarse emitir turno de un servicio inactivo.'
    );

    $reactivado = set_servicio_activo($serviciosIds['INS'], true);
    check($reactivado === true, 'set_servicio_activo() debe devolver true al reactivar INS.');
});

prueba('T-07', 'Llamar siguiente respeta el orden de llegada', static function () use ($operadoresIds): void {
    $llamado = llamar_siguiente($operadoresIds['admin'], 1);
    check($llamado !== null, 'llamar_siguiente() debe devolver un turno cuando hay cola.');
    check($llamado['folio'] === 'ASE-001', 'El primer llamado debe ser ASE-001.');
    check($llamado['estado'] === 'llamado', 'El turno llamado debe quedar en estado llamado.');
    check((int) $llamado['operador_id'] === $operadoresIds['admin'], 'El operador_id no coincide con admin.');
    check((int) $llamado['ventanilla'] === 1, 'La ventanilla debe ser 1.');
    check(!empty($llamado['llamado_en']), 'Debe registrarse llamado_en.');

    debe_rechazar(
        static fn() => llamar_siguiente($operadoresIds['admin'], 1),
        'Un operador con turno activo no debe poder llamar otro.'
    );

    $segundoLlamado = llamar_siguiente($operadoresIds['operador2'], 2);
    check($segundoLlamado !== null && $segundoLlamado['folio'] === 'ASE-002', 'El segundo llamado debe ser ASE-002.');
    check(array_column(cola_pendiente(), 'folio') === ['CON-001'], 'Solo debe quedar CON-001 en la cola pendiente.');
});

prueba('T-08', 'Flujo llamado -> en_atencion -> atendido y bitácora', static function () use ($operadoresIds): void {
    $adminId = $operadoresIds['admin'];
    $activo = turno_activo_de($adminId);
    check($activo !== null && $activo['folio'] === 'ASE-001', 'El turno activo de admin debe ser ASE-001.');
    $turnoId = (int) $activo['id'];

    debe_rechazar(
        static fn() => cambiar_estado($turnoId, 'atendido', $adminId),
        'No se debe permitir pasar directo de llamado a atendido.'
    );

    $enAtencion = cambiar_estado($turnoId, 'en_atencion', $adminId);
    check($enAtencion['estado'] === 'en_atencion', 'El turno debe pasar a en_atencion.');
    check(!empty($enAtencion['atencion_en']), 'Debe registrarse atencion_en.');

    $atendido = cambiar_estado($turnoId, 'atendido', $adminId);
    check($atendido['estado'] === 'atendido', 'El turno debe pasar a atendido.');
    check(!empty($atendido['cerrado_en']), 'Debe registrarse cerrado_en.');

    $stmt = db()->prepare('SELECT estado_anterior, estado_nuevo, operador_id FROM atenciones WHERE turno_id = :id ORDER BY id ASC');
    $stmt->execute([':id' => $turnoId]);
    $bitacora = $stmt->fetchAll();
    unset($stmt);

    check(count($bitacora) === 3, 'La bitácora debe registrar exactamente 3 transiciones.');
    $pares = array_map(static fn(array $r): array => [$r['estado_anterior'], $r['estado_nuevo']], $bitacora);
    check(
        $pares === [['pendiente', 'llamado'], ['llamado', 'en_atencion'], ['en_atencion', 'atendido']],
        'Las transiciones en bitácora no coinciden.'
    );
    foreach ($bitacora as $row) {
        check((int) $row['operador_id'] === $adminId, 'El operador_id en bitácora debe ser admin.');
    }
});

prueba('T-09', 'No se puede regresar un turno atendido', static function () use ($operadoresIds): void {
    $adminId = $operadoresIds['admin'];
    $stmt = db()->prepare("SELECT id FROM turnos WHERE folio = 'ASE-001' LIMIT 1");
    $stmt->execute();
    $atendidoId = (int) $stmt->fetchColumn();
    check($atendidoId > 0, 'No se encontró ASE-001 en estado atendido.');

    foreach (['pendiente', 'llamado', 'en_atencion', 'cancelado'] as $destino) {
        debe_rechazar(
            static fn() => cambiar_estado($atendidoId, $destino, $adminId),
            "No se debe permitir cambiar un turno atendido a {$destino}."
        );
    }
});

prueba('T-10', 'Llamar siguiente nunca toma un cancelado o atendido', static function () use ($operadoresIds, $serviciosIds): void {
    $adminId = $operadoresIds['admin'];
    $becId   = $serviciosIds['BEC'];

    // Emitir un turno de Becas (BEC-001), llamarlo y cancelarlo con motivo
    $bec1 = emitir_turno($becId);
    $llamado = llamar_siguiente($adminId, 1, $becId);
    check($llamado !== null && $llamado['folio'] === $bec1['folio'], 'Debe llamar BEC-001 al filtrar por Becas.');

    $cancelado = cambiar_estado((int) $bec1['id'], 'cancelado', $adminId, 'No se presentó');
    check($cancelado['estado'] === 'cancelado', 'BEC-001 debe quedar cancelado.');
    check($cancelado['motivo_cancelacion'] === 'No se presentó', 'Debe guardarse el motivo de cancelación.');

    // Como no hay más turnos pendientes de Becas (aunque haya cancelado y atendido), llamar por Becas devuelve null
    $vacioBec = llamar_siguiente($adminId, 1, $becId);
    check($vacioBec === null, 'No debe llamar turnos cancelados ni atendidos al filtrar por servicio.');
});

prueba('T-11', 'Operar un turno inexistente se rechaza', static function () use ($operadoresIds): void {
    debe_rechazar(
        static fn() => cambiar_estado(99999, 'en_atencion', $operadoresIds['admin']),
        'Debe rechazarse operar un turno inexistente.'
    );
});

prueba('T-13', 'Cola vacía no genera error', static function () use ($operadoresIds): void {
    $adminId = $operadoresIds['admin'];

    // Consumir el único turno pendiente restante (CON-001)
    $con1 = llamar_siguiente($adminId, 1);
    check($con1 !== null && $con1['folio'] === 'CON-001', 'Debe tomar CON-001.');
    cambiar_estado((int) $con1['id'], 'cancelado', $adminId, 'Prueba de vaciado');

    // Con la cola completamente vacía, llamar_siguiente debe devolver null sin lanzar excepción
    $resultado = llamar_siguiente($adminId, 1);
    check($resultado === null, 'llamar_siguiente() debe devolver null cuando la cola está vacía.');
});

prueba('T-15', 'Prefijo repetido o inválido se rechaza', static function (): void {
    $errRepetido = validate_servicio(['prefijo' => 'ASE', 'nombre' => 'Otro servicio', 'descripcion' => '']);
    check(isset($errRepetido['prefijo']), 'validate_servicio() debe rechazar prefijo repetido ASE.');

    $errDigitos = validate_servicio(['prefijo' => 'A1', 'nombre' => 'Con número', 'descripcion' => '']);
    check(isset($errDigitos['prefijo']), 'validate_servicio() debe rechazar prefijo con números A1.');

    $errCorto = validate_servicio(['prefijo' => 'A', 'nombre' => 'Muy corto', 'descripcion' => '']);
    check(isset($errCorto['prefijo']), 'validate_servicio() debe rechazar prefijo corto A.');

    $errLargo = validate_servicio(['prefijo' => 'ABCDE', 'nombre' => 'Muy largo', 'descripcion' => '']);
    check(isset($errLargo['prefijo']), 'validate_servicio() debe rechazar prefijo largo ABCDE.');

    $errValido = validate_servicio(['prefijo' => 'TIT', 'nombre' => 'Titulación', 'descripcion' => 'Trámites de titulación']);
    check($errValido === [], 'validate_servicio() debe aceptar datos válidos para TIT.');

    debe_rechazar(
        static fn() => create_servicio(['prefijo' => 'ASE', 'nombre' => 'Duplicado']),
        'Debe rechazarse en BD un prefijo repetido.'
    );
    debe_rechazar(
        static fn() => create_servicio(['prefijo' => 'A', 'nombre' => 'Muy corto']),
        'Debe rechazarse en BD un prefijo menor a 2 caracteres.'
    );
    debe_rechazar(
        static fn() => create_servicio(['prefijo' => 'ABCDE', 'nombre' => 'Muy largo']),
        'Debe rechazarse en BD un prefijo mayor a 4 caracteres.'
    );
});

$total = $pasadas + $fallidas;
echo "\nResumen: {$pasadas}/{$total} pruebas OK (base temporal limpia)\n";
exit($fallidas === 0 ? 0 : 1);
