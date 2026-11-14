<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Datos (funciones de src/pantalla.php, cargado desde bootstrap.php)
$llamados = turnos_en_ventanilla();   // solo estado 'llamado', más reciente primero
$espera   = espera_por_servicio();    // servicios activos con la llave 'pendientes'

$actual = $llamados[0] ?? null;               // turno actual (el más reciente)
$otros  = array_slice($llamados, 1);          // resto de turnos llamados
$totalEspera = array_sum(array_map(static fn(array $s): int => (int) $s['pendientes'], $espera));

$pageTitle = 'Pantalla de sala';

// ---------------------------------------------------------------------
// MODO PANTALLA COMPLETA (sin barra de navegación)
// Para volver a mostrar el menú, comenta la línea activa de abajo
// y descomenta la alternativa.
// ---------------------------------------------------------------------
//$hideNav = true;
$hideNav = false;   // ← alternativa: mostrar menú de navegación

require dirname(__DIR__) . '/src/views/header.php';
?>
<main class="max-w-6xl mx-auto w-full px-4 py-8">
  <section class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="text-indigo-600 font-bold text-xs uppercase tracking-widest">Sala de atención</p>
      <h1 class="text-3xl md:text-4xl font-extrabold mt-1">Turno actual</h1>
    </div>
    <p class="text-slate-500 font-medium">
      <?= e($totalEspera) ?> <?= $totalEspera === 1 ? 'persona en espera' : 'personas en espera' ?>
    </p>
  </section>

  <?php if ($actual === null): ?>
    <section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center mb-8">
      <p class="text-slate-500 font-semibold text-2xl md:text-3xl">Sin llamados por ahora</p>
      <p class="text-slate-400 mt-2">Cuando se llame un turno aparecerá aquí.</p>
    </section>
  <?php else: ?>
    <section class="grid lg:grid-cols-3 gap-5 mb-8">
      <article class="lg:col-span-2 bg-indigo-600 text-white rounded-2xl shadow-sm p-8 md:p-12 flex flex-col justify-between gap-6">
        <div>
          <span class="inline-block rounded-lg bg-white/15 font-bold text-xs px-2.5 py-1 mb-4">
            <?= e($actual['servicio_nombre']) ?>
          </span>
          <p class="text-indigo-100 font-semibold">Folio</p>
          <p class="text-7xl md:text-9xl font-extrabold tracking-tight leading-none mt-1">
            <?= e($actual['folio']) ?>
          </p>
        </div>
        <div>
          <p class="text-indigo-100 font-semibold">Pasa a</p>
          <p class="text-4xl md:text-6xl font-extrabold leading-tight">
            Ventanilla <?= e($actual['ventanilla'] ?? '—') ?>
          </p>
        </div>
      </article>

      <aside class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Otros llamados</h2>
        <?php if ($otros === []): ?>
          <p class="text-slate-400 text-sm">No hay otros turnos llamados.</p>
        <?php else: ?>
          <ul class="divide-y divide-slate-100">
            <?php foreach ($otros as $turno): ?>
              <li class="py-3 flex items-center justify-between gap-3">
                <div>
                  <p class="text-2xl font-extrabold text-slate-800"><?= e($turno['folio']) ?></p>
                  <p class="text-slate-400 text-xs"><?= e($turno['servicio_nombre']) ?> · <?= e(hora($turno['llamado_en'] ?? null)) ?></p>
                </div>
                <span class="rounded-lg bg-indigo-50 text-indigo-700 font-bold text-sm px-3 py-1.5 whitespace-nowrap">
                  Ventanilla <?= e($turno['ventanilla'] ?? '—') ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </aside>
    </section>
  <?php endif; ?>

  <section>
    <h2 class="text-xl font-bold text-slate-800 mb-4">Espera por servicio</h2>
    <?php if ($espera === []): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
        <p class="text-slate-500 font-medium">No hay servicios disponibles por el momento.</p>
      </div>
    <?php else: ?>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php foreach ($espera as $servicio): ?>
          <?php $pendientes = (int) $servicio['pendientes']; ?>
          <article class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <span class="inline-block rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs px-2.5 py-1 mb-3">
              <?= e($servicio['prefijo']) ?>
            </span>
            <h3 class="text-base font-bold text-slate-800"><?= e($servicio['nombre']) ?></h3>
            <p class="mt-3 text-5xl font-extrabold <?= $pendientes === 0 ? 'text-slate-300' : 'text-slate-800' ?>">
              <?= e($pendientes) ?>
            </p>
            <p class="text-slate-400 text-sm font-medium">
              <?= $pendientes === 1 ? 'en espera' : 'en espera' ?>
            </p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require dirname(__DIR__) . '/src/views/footer.php'; ?>