<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_post_csrf();
    try {
        $turno = emitir_turno(input_id($_POST, 'servicio_id'));
        redirect('ticket.php?id=' . $turno['id']);
    } catch (InvalidArgumentException $ex) {
        set_flash($ex->getMessage(), 'error');
        redirect('index.php');
    }
}

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
          <form method="post" action="index.php">
            <?= csrf_field() ?>
            <input type="hidden" name="servicio_id" value="<?= e($servicio['id']) ?>">
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition">
              Tomar turno
            </button>
          </form>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
<?php require dirname(__DIR__) . '/src/views/footer.php'; ?>
