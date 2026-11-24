<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$estado = estado_pantalla();

$pageTitle = 'Pantalla de sala';

//$hideNav = true; para pantalla completa
$hideNav = false;

require dirname(__DIR__) . '/src/views/header.php';
?>
<main class="max-w-6xl mx-auto w-full px-4 py-8">
  <section class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="text-indigo-600 font-bold text-xs uppercase tracking-widest">Sala de atención</p>
      <h1 class="text-3xl md:text-4xl font-extrabold mt-1">Turno actual</h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
      <span id="estado-conexion" role="status" class="hidden rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm font-semibold px-3 py-2">
        Sin conexión, reintentando…
      </span>
      <button id="btn-sonido" type="button" aria-pressed="false"
        class="rounded-xl border px-3 py-2 text-sm font-semibold transition cursor-pointer bg-white text-slate-600 border-slate-200 hover:border-indigo-300">
        🔕
      </button>
    </div>
  </section>

  <div id="pantalla-contenido" data-endpoint="pantalla-estado.php"
    data-intervalo="3000" data-ids="<?= e(implode(',', $estado['turnos_ids'])) ?>">
    <?= pantalla_render_contenido($estado) ?>
  </div>
</main>
<script src="assets/js/pantalla.js" defer></script>
<?php require dirname(__DIR__) . '/src/views/footer.php'; ?>