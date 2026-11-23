<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_admin();

$valores = ['prefijo' => '', 'nombre' => '', 'descripcion' => ''];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_post_csrf();
    $valores = [
        'prefijo'     => input_string($_POST, 'prefijo', 10),
        'nombre'      => input_string($_POST, 'nombre'),
        'descripcion' => input_string($_POST, 'descripcion', 500),
    ];
    $errores = validate_servicio($valores);

    if ($errores === []) {
        create_servicio($valores);
        set_flash('Servicio "' . $valores['nombre'] . '" creado. Ya aparece en el kiosco.');
        redirect('servicios.php');
    }
}

$busqueda = input_string($_GET, 'q', 60);
$totalServicios = contar_servicios($busqueda);
$totalPaginas = max(1, (int) ceil($totalServicios / SERVICIOS_POR_PAGINA));
$pagina = min(max(1, input_id($_GET, 'pagina')), $totalPaginas);
$servicios = buscar_servicios($busqueda, $pagina);
$desde = $totalServicios === 0 ? 0 : ($pagina - 1) * SERVICIOS_POR_PAGINA + 1;
$hasta = min($pagina * SERVICIOS_POR_PAGINA, $totalServicios);

$urlPagina = static function (int $numero) use ($busqueda): string {
    $params = ['pagina' => $numero];
    if ($busqueda !== '') {
        $params['q'] = $busqueda;
    }
    return 'servicios.php?' . http_build_query($params);
};

$pageTitle = 'Servicios';
require __DIR__ . '/../src/views/header.php';
?>

<main class="max-w-6xl mx-auto w-full px-4 py-8 flex-1">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Catálogo de servicios</h1>
    <p class="text-sm text-slate-500">Crea trámites y elige cuáles se muestran en el kiosco.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <section class="lg:col-span-4">
      <?php
      $formAction = 'servicios.php';
      $formTitulo = 'Nuevo servicio';
      $botonTexto = 'Crear servicio';
      require __DIR__ . '/../src/views/servicio-form.php';
      ?>
    </section>

    <section class="lg:col-span-8">
      <div class="h-full flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-slate-800">Servicios registrados</h2>
          <span class="text-xs font-semibold bg-slate-100 text-slate-700 px-3 py-1 rounded-full">
            <?= e($totalServicios) ?> <?= $busqueda !== '' ? 'encontrados' : 'en total' ?>
          </span>
        </div>

        <form method="get" action="servicios.php" class="flex flex-wrap gap-3 mb-4" role="search">
          <label for="q" class="sr-only">Buscar servicio</label>
          <input id="q" name="q" type="search" maxlength="60" value="<?= e($busqueda) ?>"
                 placeholder="Buscar por prefijo o nombre"
                 class="flex-1 min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-2 outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200">
          <button
            type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-xl transition cursor-pointer">
            Buscar
          </button>
          <?php if ($busqueda !== ''): ?>
            <a
              href="servicios.php"
              class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl transition">
              Limpiar
            </a>
          <?php endif; ?>
        </form>

        <?php if ($servicios === []): ?>
          <div class="rounded-xl bg-slate-50 border border-dashed border-slate-300 p-6 text-center">
            <?php if ($busqueda !== ''): ?>
              <p class="text-sm text-slate-500">No hay servicios que coincidan con "<?= e($busqueda) ?>".</p>
            <?php else: ?>
              <p class="text-sm text-slate-500">Todavía no hay servicios. Crea el primero con el formulario.</p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                  <th class="py-3 px-2">Prefijo</th>
                  <th class="py-3 px-2">Servicio</th>
                  <th class="py-3 px-2">Estado</th>
                  <th class="py-3 pl-10 pr-2 w-1 whitespace-nowrap">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($servicios as $servicio): ?>
                  <?php $activo = (int) $servicio['activo'] === 1; ?>
                  <tr class="border-b border-slate-100 last:border-0 align-top">
                    <td class="py-3 px-2 font-bold text-slate-900"><?= e($servicio['prefijo']) ?></td>
                    <td class="py-3 px-2">
                      <p class="font-semibold text-slate-800"><?= e($servicio['nombre']) ?></p>
                      <?php if ($servicio['descripcion'] !== ''): ?>
                        <p class="text-sm text-slate-500"><?= e($servicio['descripcion']) ?></p>
                      <?php endif; ?>
                    </td>
                    <td class="py-3 px-2">
                      <?php if ($activo): ?>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Activo</span>
                      <?php else: ?>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 text-slate-700">Inactivo</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-3 pl-10 pr-2">
                      <div class="flex justify-start gap-2">
                        <a
                          href="servicio-editar.php?id=<?= e($servicio['id']) ?>"
                          title="Editar"
                          aria-label="Editar <?= e($servicio['nombre']) ?>"
                          class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                          </svg>
                        </a>
                        <form method="post" action="servicio-estado.php">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= e($servicio['id']) ?>">
                          <input type="hidden" name="activo" value="<?= $activo ? '0' : '1' ?>">
                          <input type="hidden" name="pagina" value="<?= e($pagina) ?>">
                          <?php if ($busqueda !== ''): ?>
                            <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                          <?php endif; ?>
                          <?php if ($activo): ?>
                            <button
                              type="submit"
                              title="Desactivar"
                              aria-label="Desactivar <?= e($servicio['nombre']) ?>"
                              class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-rose-100 hover:bg-rose-200 text-rose-700 transition cursor-pointer">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                              </svg>
                            </button>
                          <?php else: ?>
                            <button
                              type="submit"
                              title="Activar"
                              aria-label="Activar <?= e($servicio['nombre']) ?>"
                              class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-700 transition cursor-pointer">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                              </svg>
                            </button>
                          <?php endif; ?>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="mt-auto pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">Mostrando <?= e($desde) ?>–<?= e($hasta) ?> de <?= e($totalServicios) ?></p>
            <?php if ($totalPaginas > 1): ?>
              <nav class="flex items-center gap-2" aria-label="Páginas de servicios">
                <?php if ($pagina > 1): ?>
                  <a
                    href="<?= e($urlPagina($pagina - 1)) ?>"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm px-3 py-1.5 rounded-xl transition">
                    Anterior
                  </a>
                <?php endif; ?>
                <?php for ($numero = 1; $numero <= $totalPaginas; $numero++): ?>
                  <?php if ($numero === $pagina): ?>
                    <span aria-current="page" class="bg-indigo-600 text-white font-semibold text-sm px-3 py-1.5 rounded-xl"><?= e($numero) ?></span>
                  <?php else: ?>
                    <a
                      href="<?= e($urlPagina($numero)) ?>"
                      class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm px-3 py-1.5 rounded-xl transition">
                      <?= e($numero) ?>
                    </a>
                  <?php endif; ?>
                <?php endfor; ?>
                <?php if ($pagina < $totalPaginas): ?>
                  <a
                    href="<?= e($urlPagina($pagina + 1)) ?>"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm px-3 py-1.5 rounded-xl transition">
                    Siguiente
                  </a>
                <?php endif; ?>
              </nav>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<?php require __DIR__ . '/../src/views/footer.php'; ?>
