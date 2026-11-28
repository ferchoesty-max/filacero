<?php
declare(strict_types=1);

if (!class_exists('ReglaNegocioException')) {
    class ReglaNegocioException extends RuntimeException {}
}

// Mapa de transiciones de estado permitidas. 
// Los estados 'atendido' y 'cancelado' son estados finales y no tienen salida.
const TRANSICIONES = [
    'pendiente'   => ['llamado', 'cancelado'],
    'llamado'     => ['en_atencion', 'cancelado'],
    'en_atencion' => ['atendido', 'cancelado'],
];

// Verifica si un cambio de estado es válido según la máquina de estados.
function transicion_permitida(string $actual, string $nuevo): bool
{
    return isset(TRANSICIONES[$actual]) && in_array($nuevo, TRANSICIONES[$actual], true);
}

// Verifica si un operador tiene un turno activo (llamado o en atención) hoy.
function turno_activo_de(int $operadorId): ?array
{
    $sql = "SELECT t.*, s.nombre AS servicio_nombre, s.prefijo AS servicio_prefijo
            FROM turnos t
            INNER JOIN servicios s ON s.id = t.servicio_id
            WHERE t.operador_id = :operador_id
              AND t.fecha = :hoy
              AND t.estado IN ('llamado', 'en_atencion')
            ORDER BY t.llamado_en DESC
            LIMIT 1";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':operador_id' => $operadorId,
        ':hoy'         => today(),
    ]);

    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    return $turno ?: null;
}

// Registra un cambio de estado en la bitácora de atenciones.
function registrar_atencion(
    int $turnoId,
    int $operadorId,
    ?string $anterior,
    string $nuevo,
    ?string $nota = null
): void {
    $sql = "INSERT INTO atenciones (turno_id, operador_id, estado_anterior, estado_nuevo, nota, creado_en)
            VALUES (:turno_id, :operador_id, :anterior, :nuevo, :nota, :creado_en)";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':turno_id'    => $turnoId,
        ':operador_id' => $operadorId,
        ':anterior'    => $anterior,
        ':nuevo'       => $nuevo,
        ':nota'        => $nota,
        ':creado_en'   => now(),
    ]);
}

// Toma el siguiente turno pendiente y lo asigna al operador y ventanilla especificados.
function llamar_siguiente(int $operadorId, int $ventanilla, ?int $servicioId = null): ?array
{
    if (turno_activo_de($operadorId) !== null) {
        throw new ReglaNegocioException('Ya tienes un turno activo en tu ventanilla. Termínalo o cancélalo antes de llamar otro.');
    }

    $pdo = db();
    $ahora = now();

    $sqlSelect = "SELECT id FROM turnos
                  WHERE fecha = :hoy AND estado = 'pendiente'";
    $paramsSelect = [':hoy' => today()];

    if ($servicioId !== null) {
        $sqlSelect .= " AND servicio_id = :servicio_id";
        $paramsSelect[':servicio_id'] = $servicioId;
    }
    $sqlSelect .= " ORDER BY id ASC LIMIT 1";

    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->execute($paramsSelect);
    $siguiente = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$siguiente) {
        return null; 
    }

    $turnoId = (int) $siguiente['id'];

    $stmtUpdate = $pdo->prepare(
        "UPDATE turnos
         SET estado = 'llamado',
             operador_id = :operador_id,
             ventanilla = :ventanilla,
             llamado_en = :llamado_en
         WHERE id = :id AND estado = 'pendiente'"
    );
    $stmtUpdate->execute([
        ':operador_id' => $operadorId,
        ':ventanilla'  => $ventanilla,
        ':llamado_en'  => $ahora,
        ':id'          => $turnoId,
    ]);

    if ($stmtUpdate->rowCount() !== 1) {
        throw new ReglaNegocioException('El turno fue tomado por otro operador un instante antes. Intenta de nuevo.');
    }

    registrar_atencion($turnoId, $operadorId, 'pendiente', 'llamado');

    return find_turno($turnoId);
}

// Cambia el estado de un turno validando existencia, transiciones permitidas y concurrencia.
function cambiar_estado(int $turnoId, string $nuevoEstado, int $operadorId, ?string $motivo = null): array
{
    $turno = find_turno($turnoId);

    if ($turno === null) {
        throw new ReglaNegocioException('El turno no existe');
    }

    $estadoActual = (string) $turno['estado'];

    if (!transicion_permitida($estadoActual, $nuevoEstado)) {
        throw new ReglaNegocioException("Transición no permitida: no se puede pasar de '$estadoActual' a '$nuevoEstado'.");
    }

    $motivoLimpio = null;
    if ($nuevoEstado === 'cancelado' && $motivo !== null) {
        $recortado = trim($motivo);
        if ($recortado !== '') {
            $motivoLimpio = function_exists('mb_substr')
                ? mb_substr($recortado, 0, 120, 'UTF-8')
                : substr($recortado, 0, 120);
        }
    }

    $ahora = now();
    $camposExtra = '';
    $params = [
        ':nuevo_estado'  => $nuevoEstado,
        ':id'            => $turnoId,
        ':estado_actual' => $estadoActual,
    ];

    // Dependiendo del nuevo estado, se actualizan los campos de tiempo correspondientes.
    if ($nuevoEstado === 'en_atencion') {
        $camposExtra = ', atencion_en = :momento';
        $params[':momento'] = $ahora;
    } elseif ($nuevoEstado === 'atendido') {
        $camposExtra = ', cerrado_en = :momento';
        $params[':momento'] = $ahora;
    } elseif ($nuevoEstado === 'cancelado') {
        $camposExtra = ', cerrado_en = :momento, motivo_cancelacion = :motivo';
        $params[':momento'] = $ahora;
        $params[':motivo']  = $motivoLimpio;
    }

    $sql = "UPDATE turnos
            SET estado = :nuevo_estado{$camposExtra}
            WHERE id = :id AND estado = :estado_actual";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() !== 1) {
        throw new ReglaNegocioException('No se pudo actualizar el turno porque su estado cambió recientemente.');
    }

    registrar_atencion($turnoId, $operadorId, $estadoActual, $nuevoEstado, $motivoLimpio);

    $actualizado = find_turno($turnoId);
    if ($actualizado === null) {
        throw new RuntimeException('No se pudo recuperar el turno actualizado.');
    }

    return $actualizado;
}