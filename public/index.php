<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$servicios = all_servicios(true);
$pageTitle = 'Kiosco de turnos';
require dirname(__DIR__) . '/src/views/header.php';
?>
<main class="max-w-6xl mx-auto w-full px-4 py-8">
  <section class="mb-8">
    <p class="text-indigo-600 font-bold text-xs uppercase tracking-widest">Kiosco de atención</p>
    <h1 class="text-3xl md:text-4xl font-extrabold mt-1">Selecciona un servicio</h1>
    <p class="text-slate-500 mt-2">Elige el trámite que necesitas realizar para recibir tu folio.</p>
  </section>

  <?php if ($servicios === []): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
      <p class="text-slate-500 font-medium">No hay servicios disponibles por el momento.</p>
    </div>
  <?php else: ?>
    <section class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($servicios as $servicio): ?>
        <article class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between gap-4">
          <div>
            <span class="inline-block rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs px-2.5 py-1 mb-3">
              <?= e($servicio['prefijo']) ?>
            </span>
            <h2 class="text-xl font-bold text-slate-800"><?= e($servicio['nombre']) ?></h2>
            <p class="text-slate-500 text-sm mt-2"><?= e($servicio['descripcion']) ?></p>
          </div>
          <span class="text-xs font-medium text-slate-400">
            Emisión de folios disponible en HU-03
          </span>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
<?php require dirname(__DIR__) . '/src/views/footer.php'; ?>
