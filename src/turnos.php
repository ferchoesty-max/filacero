<?php
declare(strict_types=1);

function emitir_turno(int $servicioId): array
{
    $servicio = find_servicio($servicioId);
    if ($servicio === null) {
        throw new ReglaNegocioException('El servicio no existe.');
    }
    if ((int) $servicio['activo'] !== 1) {
        throw new ReglaNegocioException('Ese servicio no está disponible en este momento.');
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

function cola_pendiente(?int $servicioId = null): array
{
    $sql = "SELECT t.*,
                   s.nombre  AS servicio_nombre,
                   s.prefijo AS servicio_prefijo
            FROM turnos t
            INNER JOIN servicios s ON s.id = t.servicio_id
            WHERE t.fecha = :fecha
              AND t.estado = 'pendiente'";

    $params = [':fecha' => today()];

    if ($servicioId !== null) {
        $sql .= " AND t.servicio_id = :servicio_id";
        $params[':servicio_id'] = $servicioId;
    }

    $sql .= " ORDER BY t.id ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
