<?php
declare(strict_types=1);


function turnos_en_ventanilla(): array
{
    $stmt = db()->prepare(
        "SELECT t.id, t.servicio_id, t.fecha, t.consecutivo, t.folio, t.estado,
                t.operador_id, t.ventanilla, t.llamado_en,
                s.nombre AS servicio_nombre, s.prefijo AS servicio_prefijo
           FROM turnos t
           JOIN servicios s ON s.id = t.servicio_id
          WHERE t.fecha = :fecha AND t.estado IN ('llamado', 'en_atencion')
          ORDER BY t.llamado_en DESC, t.id DESC"
    );
    $stmt->execute([':fecha' => today()]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function espera_por_servicio(): array
{
    $stmt = db()->prepare(
        "SELECT s.id, s.prefijo, s.nombre, s.descripcion, s.activo,
                COUNT(t.id) AS pendientes
           FROM servicios s
           LEFT JOIN turnos t
                  ON t.servicio_id = s.id
                 AND t.fecha = :fecha
                 AND t.estado = 'pendiente'
          WHERE s.activo = 1
          GROUP BY s.id
          ORDER BY s.nombre"
    );
    $stmt->execute([':fecha' => today()]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filas as &$fila) {
        $fila['pendientes'] = (int) $fila['pendientes'];
    }
    unset($fila);

    return $filas;
}


function estado_pantalla(): array
{
    $llamados = turnos_en_ventanilla();
    $espera   = espera_por_servicio();

    return [
        'actual'       => $llamados[0] ?? null,
        'otros'        => array_slice($llamados, 1),
        'espera'       => $espera,
        'total_espera' => array_sum(array_map(static fn(array $s): int => (int) $s['pendientes'], $espera)),
        'turnos_ids'   => array_map(static fn(array $t): int => (int) $t['id'], $llamados),
    ];
}

function pantalla_render_contenido(array $estado): string
{
    ob_start();
    require __DIR__ . '/views/pantalla-contenido.php';
    return (string) ob_get_clean();
}