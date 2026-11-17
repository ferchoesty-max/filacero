<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$id = input_id($_GET);
$turno = $id > 0 ? find_turno($id) : null;

if ($turno === null) {
    http_response_code(404);
    $pageTitle = 'Turno no encontrado';
    require dirname(__DIR__) . '/src/views/header.php';
    ?>
    <main class="max-w-6xl mx-auto w-full px-4 py-8">
      <div class="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
        <p class="text-6xl font-extrabold text-slate-300">404</p>
        <h1 class="text-xl font-bold mt-2">El turno no existe</h1>
        <p class="text-slate-500 mt-2">Revisa tu folio o pide un turno nuevo en el kiosco.</p>
        <a href="index.php" class="inline-block mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition">
          Pedir turno
        </a>
      </div>
    </main>
    <?php
    require dirname(__DIR__) . '/src/views/footer.php';
    exit;
}

$pageTitle = 'Tu turno ' . $turno['folio'];
require dirname(__DIR__) . '/src/views/header.php';
?>
<main class="max-w-6xl mx-auto w-full px-4 py-8">
  <div class="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
    <p class="text-indigo-600 font-bold text-xs uppercase tracking-widest">Tu turno</p>
    <p class="text-6xl md:text-7xl font-extrabold tracking-tight mt-3"><?= e($turno['folio']) ?></p>
    <p class="text-lg font-semibold text-slate-700 mt-3"><?= e($turno['servicio_nombre']) ?></p>

    <dl class="grid grid-cols-2 gap-4 mt-6 text-left">
      <div class="bg-slate-50 rounded-xl p-4">
        <dt class="text-xs font-semibold text-slate-500 uppercase">Estado</dt>
        <dd class="font-bold mt-1"><?= e(ESTADO_LABELS[$turno['estado']] ?? $turno['estado']) ?></dd>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <dt class="text-xs font-semibold text-slate-500 uppercase">Hora de emisión</dt>
        <dd class="font-bold mt-1"><?= e(hora($turno['creado_en'])) ?></dd>
      </div>
    </dl>

    <p class="text-slate-500 text-sm mt-6">Espera a que tu folio aparezca en la pantalla de la sala.</p>
    <a href="index.php" class="inline-block mt-6 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl transition">
      Volver al kiosco
    </a>
  </div>
</main>
<?php require dirname(__DIR__) . '/src/views/footer.php'; ?>
