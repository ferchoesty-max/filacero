<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_admin();
ensure_post_csrf();

$volver = ['pagina' => max(1, input_id($_POST, 'pagina'))];
$busqueda = input_string($_POST, 'q', 60);
if ($busqueda !== '') {
    $volver['q'] = $busqueda;
}
$urlVolver = 'servicios.php?' . http_build_query($volver);

$id = input_id($_POST);
$activo = $_POST['activo'] ?? null;
$servicio = $id > 0 ? find_servicio($id) : null;

if ($servicio === null) {
    set_flash('El servicio no existe.', 'error');
    redirect($urlVolver);
}

if ($activo !== '0' && $activo !== '1') {
    set_flash('La acción no es válida.', 'error');
    redirect($urlVolver);
}

set_servicio_activo($id, $activo === '1');

if ($activo === '1') {
    set_flash('Servicio "' . $servicio['nombre'] . '" activado. Ya aparece en el kiosco.');
} else {
    set_flash('Servicio "' . $servicio['nombre'] . '" desactivado. Ya no aparece en el kiosco.', 'warning');
}
redirect($urlVolver);
