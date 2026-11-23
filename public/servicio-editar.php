<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_admin();

$esPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($esPost) {
    ensure_post_csrf();
}

$id = input_id($esPost ? $_POST : $_GET);
$servicio = $id > 0 ? find_servicio($id) : null;

if ($servicio === null) {
    http_response_code(404);
    $pageTitle = 'Servicio no encontrado';
    require __DIR__ . '/../src/views/header.php';
    ?>
    <main class="max-w-6xl mx-auto w-full px-4 py-8 flex-1">
      <div class="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
        <p class="text-6xl font-extrabold text-slate-300">404</p>
        <h1 class="text-xl font-bold mt-2">El servicio no existe</h1>
        <p class="text-slate-500 mt-2">Puede que lo hayan quitado o que el enlace esté mal escrito.</p>
        <a
          href="servicios.php"
          class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition">
          Volver al catálogo
        </a>
      </div>
    </main>
    <?php
    require __DIR__ . '/../src/views/footer.php';
    exit;
}

$valores = [
    'prefijo'     => $servicio['prefijo'],
    'nombre'      => $servicio['nombre'],
    'descripcion' => $servicio['descripcion'],
];
$errores = [];

if ($esPost) {
    $valores = [
        'prefijo'     => input_string($_POST, 'prefijo', 10),
        'nombre'      => input_string($_POST, 'nombre'),
        'descripcion' => input_string($_POST, 'descripcion', 500),
    ];
    $errores = validate_servicio($valores, $id);

    if ($errores === []) {
        update_servicio($id, $valores);
        set_flash('Servicio "' . $valores['nombre'] . '" actualizado.');
        redirect('servicios.php');
    }
}

$activo = (int) $servicio['activo'] === 1;

$pageTitle = 'Editar servicio';
require __DIR__ . '/../src/views/header.php';
?>

<main class="max-w-6xl mx-auto w-full px-4 py-8 flex-1">
  <div class="max-w-xl mx-auto">
    <a
      href="servicios.php"
      class="inline-flex items-center gap-2 mb-6 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl transition">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
      </svg>
      Volver al catálogo
    </a>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Editar servicio</h1>
        <p class="text-sm text-slate-500"><?= e($servicio['prefijo']) ?> · <?= e($servicio['nombre']) ?></p>
      </div>
      <?php if ($activo): ?>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Activo</span>
      <?php else: ?>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 text-slate-700">Inactivo</span>
      <?php endif; ?>
    </div>

    <?php
    $formAction = 'servicio-editar.php';
    $servicioId = $id;
    $botonTexto = 'Guardar cambios';
    $cancelarUrl = 'servicios.php';
    require __DIR__ . '/../src/views/servicio-form.php';
    ?>

    <div class="mt-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900" role="note">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 mt-0.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
      </svg>
      <p class="text-sm">
        <span class="font-bold">Importante:</span> si cambias el prefijo, solo los turnos nuevos usarán el prefijo nuevo. Los folios que ya se entregaron no cambian.
      </p>
    </div>
  </div>
</main>

<?php require __DIR__ . '/../src/views/footer.php'; ?>
