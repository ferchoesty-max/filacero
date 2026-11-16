<?php
declare(strict_types=1);

/**
 * Emite un turno nuevo para el servicio indicado.
 *
 * Serie = servicio + dia: cada servicio lleva su propio consecutivo
 * y cada dia vuelve a empezar en 001. Folio = PREFIJO-NNN (ASE-001).
 */
function emitir_turno(int $servicioId): array
{
    $servicio = find_servicio($servicioId);
    if ($servicio === null) {
        throw new InvalidArgumentException('El servicio no existe.');
    }

    $fecha = today();

    $stmt = db()->prepare(
        'SELECT COALESCE(MAX(consecutivo), 0) + 1
           FROM turnos
          WHERE servicio_id = :servicio_id AND fecha = :fecha'
    );
    $stmt->execute([':servicio_id' => $servicioId, ':fecha' => $fecha]);
    $consecutivo = (int) $stmt->fetchColumn();

    $folio = sprintf('%s-%03d', $servicio['prefijo'], $consecutivo);

    $stmt = db()->prepare(
        'INSERT INTO turnos(servicio_id, fecha, consecutivo, folio, estado, creado_en)
         VALUES(:servicio_id, :fecha, :consecutivo, :folio, :estado, :creado_en)'
    );
    $stmt->execute([
        ':servicio_id' => $servicioId,
        ':fecha'       => $fecha,
        ':consecutivo' => $consecutivo,
        ':folio'       => $folio,
        ':estado'      => 'pendiente',
        ':creado_en'   => now(),
    ]);

    $turno = find_turno((int) db()->lastInsertId());
    if ($turno === null) {
        throw new RuntimeException('No se pudo recuperar el turno emitido.');
    }
    return $turno;
}

/**
 * Busca un turno por id. Incluye el nombre y prefijo de su servicio.
 * Devuelve null si no existe.
 */
function find_turno(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, s.nombre AS servicio_nombre, s.prefijo AS servicio_prefijo
           FROM turnos t
           JOIN servicios s ON s.id = t.servicio_id
          WHERE t.id = :id'
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}
