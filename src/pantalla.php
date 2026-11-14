<?php
declare(strict_types=1);

/**
 * módulo de la pantalla pública (HU-05)
 *
 * SEMANA 1: las funciones devuelven datos de ejemplo con la misma forma
 * que devolverán las consultas reales. El escenario se elige con la URL:
 *
 *   pantalla.php                → mixto (por defecto)
 *   pantalla.php?demo=llamado   → un solo turno llamado
 *   pantalla.php?demo=vacio     → sin llamados
 *
 * SEMANA 2: reemplazar el cuerpo de las dos funciones públicas por SQL
 * y borrar el bloque marcado como TEMPORAL
 */

// TEMPORAL: datos de ejemplo y selector de escenario

const PANTALLA_DEMO_ESCENARIOS = ['mixto', 'llamado', 'vacio'];

//lee ?demo= con lista blanca. En CLI o con valor desconocido usa 'mixto'
function pantalla_demo_escenario(): string
{
    $valor = $_GET['demo'] ?? null;
    if (is_string($valor) && in_array($valor, PANTALLA_DEMO_ESCENARIOS, true)) {
        return $valor;
    }
    return 'mixto';
}

// armamos un turno con la forma del contrato
function pantalla_demo_turno(int $id, int $servicioId, string $prefijo, string $servicio, int $consecutivo, int $ventanilla, string $hora): array
{
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

/** Turnos llamados (sin ordenar) */
function pantalla_demo_llamados(string $escenario): array
{
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

// pendientes [id, prefijo, nombre, descripcion, pendientes]
function pantalla_demo_espera(string $escenario): array
{
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


// FUNCIONES DEL CONTRATO (firmas y llaves para pasar al a SQL)

/**
 * turnos en estado 'llamado' de hoy, ordenados por llamado_en DESC.
 * El primero es el turno actual
 *
 * llaves: id, servicio_id, fecha, consecutivo, folio, estado, ventanilla,
 *         llamado_en, servicio_nombre, servicio_prefijo
 */
function turnos_en_ventanilla(): array
{
    // TEMPORAL: datos de ejemplo
    $turnos = pantalla_demo_llamados(pantalla_demo_escenario());
    usort($turnos, static fn(array $a, array $b): int => strcmp($b['llamado_en'], $a['llamado_en']));
    return $turnos;
}

/**
 * servicios activos con la llave extra 'pendientes' (int, 0 si no hay)
 * cuenta solo turnos 'pendiente' de hoy
 *
 * llaves: id, prefijo, nombre, descripcion, activo, pendientes
 */
function espera_por_servicio(): array
{
    // TEMPORAL: datos de ejemplo
    return pantalla_demo_espera(pantalla_demo_escenario());
}