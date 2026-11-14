<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$folder = dirname(__DIR__) . '/storage';
if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
    throw new RuntimeException('No fue posible crear el directorio storage.');
}

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($schema === false) {
    throw new RuntimeException('No se pudo leer database/schema.sql.');
}

$db = db();
$db->exec($schema);

if ((int) $db->query('SELECT COUNT(*) FROM servicios')->fetchColumn() === 0) {
    $serviciosDemo = [
        ['ASE', 'Asesoría Académica', 'Orientación sobre materias, cargas y tutorías.'],
        ['CON', 'Constancias y Kardex', 'Emisión de constancias de estudio e historial académico.'],
        ['BEC', 'Becas y Apoyos', 'Información y seguimiento de becas institucionales.'],
        ['INS', 'Inscripciones', 'Trámites de inscripción, reinscripción y bajas.'],
    ];
    foreach ($serviciosDemo as [$prefijo, $nombre, $descripcion]) {
        create_servicio([
            'prefijo'     => $prefijo,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'activo'      => 1,
        ]);
    }
    echo "Base creada y 4 servicios iniciales insertados.\n";
} else {
    echo "Base existente: se conservaron los datos.\n";
}
