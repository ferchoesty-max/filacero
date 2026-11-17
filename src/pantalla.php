<?php
declare(strict_types=1);

/**
 * módulo de pantalla 
 *
 * SEMANA 1: las funciones devuelven datos de ejemplo con la misma forma
 * que devolverán las consultas reales. El escenario se elige con la URL:
 *
 * SEMANA 2: reemplazar el cuerpo de las dos funciones públicas por SQL
 * y borrar el bloque marcado como TEMPORAL
 */

// TEMPORAL: datos de ejemplo y selector de escenario

const PANTALLA_DEMO_ESCENARIOS = ['mixto', 'llamado', 'vacio'];


function pantalla_demo_escenario(): ?string {
    $valor = $_GET['demo'] ?? null;
    if (is_string($valor) && in_array($valor, PANTALLA_DEMO_ESCENARIOS, true)) {
        return $valor;
    }
    return null; 
    // usar null para desactivar el modo demo y usar la base de datos real, 
    // 'mixto' para demo con varios turnos llamados, 
    // 'llamado' para demo con un turno llamado, 
    // 'vacio' para demo sin llamados
}

// armamos un turno con la forma del contrato
function pantalla_demo_turno(int $id, int $servicioId, string $prefijo, 
                            string $servicio, int $consecutivo, int $ventanilla, 
                            string $hora): array{
    return [
        'id'              => $id,
        'servicio_id'     => $servicioId,
        'fecha'           => today(),
        'consecutivo'     => $consecutivo,
        'folio'           => sprintf('%s-%03d', $prefijo, $consecutivo),
        'estado'          => 'llamado',
        'ventanilla'      => $ventanilla,
        'llamado_en'      => today() . ' ' . $hora . ':00',
        'servicio_nombre' => $servicio,
        'servicio_prefijo' => $prefijo,
    ];
}

function pantalla_demo_llamados(string $escenario): array {
    return match ($escenario) {
        'vacio'   => [],
        'llamado' => [
            pantalla_demo_turno(11, 1, 'ASE', 'Asesoría Académica', 4, 2, '10:42'),
        ],
        default   => [
            pantalla_demo_turno(11, 1, 'ASE', 'Asesoría Académica', 4, 2, '10:42'),
            pantalla_demo_turno(9, 2, 'CON', 'Constancias y Kardex', 7, 1, '10:38'),
            pantalla_demo_turno(6, 3, 'BEC', 'Becas y Apoyos', 2, 3, '10:31'),
        ],
    };
}

function pantalla_demo_espera(string $escenario): array{
    $pendientes = match ($escenario) {
        'vacio'   => [2, 0, 1, 0],
        'llamado' => [1, 0, 2, 0],
        default   => [3, 5, 0, 2],
    };

    $servicios = [
        [1, 'ASE', 'Asesoría Académica', 'Orientación sobre materias, cargas y tutorías.'],
        [2, 'CON', 'Constancias y Kardex', 'Emisión de constancias de estudio e historial académico.'],
        [3, 'BEC', 'Becas y Apoyos', 'Información y seguimiento de becas institucionales.'],
        [4, 'INS', 'Inscripciones', 'Trámites de inscripción, reinscripción y bajas.'],
    ];

    $resultado = [];
    foreach ($servicios as $i => [$id, $prefijo, $nombre, $descripcion]) {
        $resultado[] = [
            'id'          => $id,
            'prefijo'     => $prefijo,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'activo'      => 1,
            'pendientes'  => $pendientes[$i],
        ];
    }
    return $resultado;
}


// FUNCIONES DEL CONTRATO
function turnos_en_ventanilla(): array {
    
    $demo = pantalla_demo_escenario();   // TEMPORAL 

    if ($demo !== null) {
        $turnos = pantalla_demo_llamados($demo);
        usort($turnos, static fn(array $a, array $b): int => strcmp($b['llamado_en'], $a['llamado_en']));
        return $turnos;
    }

    $stmt = db()->prepare(
        "SELECT t.id, t.servicio_id, t.fecha, t.consecutivo, t.folio, t.estado,
                t.operador_id, t.ventanilla, t.llamado_en,
                s.nombre AS servicio_nombre, s.prefijo AS servicio_prefijo
           FROM turnos t
           JOIN servicios s ON s.id = t.servicio_id
          WHERE t.fecha = :fecha AND t.estado = 'llamado'
          ORDER BY t.llamado_en DESC, t.id DESC"
    );

    $stmt->execute([':fecha' => today()]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function espera_por_servicio(): array {
    $demo = pantalla_demo_escenario();   // TEMPORAL 
    if ($demo !== null) {
        return pantalla_demo_espera($demo);
    }

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