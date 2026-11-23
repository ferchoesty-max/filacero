<?php
$bodyClasses = $bodyClass ?? "min-h-screen flex flex-col bg-slate-50 text-slate-800 font-['Plus_Jakarta_Sans']";
$flash = pull_flash();

$operadorNav = function_exists('operador_actual') ? operador_actual() : null;
$paginaActual = basename($_SERVER['SCRIPT_NAME'] ?? '');

$enlacesNav = [
    [
        'url' => 'index.php',
        'texto' => 'Kiosco',
        'paginas' => ['index.php', 'ticket.php'],
        'icono' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z',
    ],
    [
        'url' => 'pantalla.php',
        'texto' => 'Pantalla',
        'paginas' => ['pantalla.php'],
        'icono' => 'M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125Z',
    ],
];

if ($operadorNav !== null) {
    $enlacesNav[] = [
        'url' => 'panel.php',
        'texto' => 'Panel',
        'paginas' => ['panel.php'],
        'icono' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
    ];
    if (es_admin()) {
        $enlacesNav[] = [
            'url' => 'servicios.php',
            'texto' => 'Servicios',
            'paginas' => ['servicios.php', 'servicio-editar.php'],
            'icono' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
        ];
    }

    preg_match_all('/(?<!\p{L})\p{L}/u', (string) $operadorNav['nombre'], $navLetras);
    $inicialesNav = strtoupper(implode('', array_slice($navLetras[0], 0, 2)));
    $rolNav = $operadorNav['rol'] === 'admin' ? 'Administrador' : 'Operador';
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="FilaCero: sistema web de turnos para ventanillas y módulos de atención.">
    <title><?= e($pageTitle ?? 'Inicio') ?> | FilaCero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
      @keyframes aviso-desvanecer {
        to { opacity: 0; }
      }
      @keyframes aviso-cerrar {
        to { max-height: 0; margin-top: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0; border-width: 0; }
      }
      .aviso-temporal {
        max-height: 12rem;
        overflow: hidden;
        animation: aviso-desvanecer 3s ease-in 3s forwards, aviso-cerrar 0.4s ease-out 6s forwards;
      }
      @media (prefers-reduced-motion: reduce) {
        .aviso-temporal { animation: none; }
      }
    </style>
  </head>
  <body class="<?= e($bodyClasses) ?>">
    <?php if (empty($hideNav)): ?>
      <header class="bg-white border-b border-slate-200">
        <nav class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap gap-3 items-center justify-between">
          <a href="index.php" class="text-2xl font-extrabold tracking-tight text-indigo-600">
            Fila<span class="text-slate-800">Cero</span>
          </a>

          <div class="flex flex-wrap items-center gap-2 text-sm font-semibold">
            <?php foreach ($enlacesNav as $navEnlace): ?>
              <?php $navActivo = in_array($paginaActual, $navEnlace['paginas'], true); ?>
              <a
                href="<?= e($navEnlace['url']) ?>"
                title="<?= e($navEnlace['texto']) ?>"
                <?= $navActivo ? 'aria-current="page"' : '' ?>
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border transition <?= $navActivo ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="<?= $navEnlace['icono'] ?>" />
                </svg>
                <span class="hidden sm:inline"><?= e($navEnlace['texto']) ?></span>
              </a>
            <?php endforeach; ?>

            <?php if ($operadorNav !== null): ?>
              <div class="flex items-center gap-2 sm:ml-2 sm:pl-3 sm:border-l sm:border-slate-200">
                <span class="w-9 h-9 shrink-0 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center" aria-hidden="true">
                  <?= e($inicialesNav) ?>
                </span>
                <span class="hidden md:flex flex-col leading-tight">
                  <span class="text-slate-800"><?= e($operadorNav['nombre']) ?></span>
                  <span class="text-xs font-medium text-slate-500"><?= e($rolNav) ?> · Ventanilla <?= e($operadorNav['ventanilla']) ?></span>
                </span>
                <form method="post" action="logout.php">
                  <?= csrf_field() ?>
                  <button
                    type="submit"
                    title="Cerrar sesión"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 hover:border-rose-300 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    <span class="hidden sm:inline">Salir</span>
                  </button>
                </form>
              </div>
            <?php else: ?>
              <a
                href="login.php"
                title="Entrar como operador"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border transition sm:ml-2 <?= $paginaActual === 'login.php' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-indigo-600 text-indigo-700 hover:bg-indigo-50' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span>Operadores</span>
              </a>
            <?php endif; ?>
          </div>
        </nav>
      </header>
    <?php endif; ?>
    <?php if ($flash !== null): ?>
      <?php
        $flashType = $flash['type'] ?? 'success';
        $isError = $flashType === 'error';
        $flashStyles = match ($flashType) {
            'error' => 'bg-rose-50 border-rose-200 text-rose-800',
            'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
            default => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        };
      ?>
      <div class="<?= $isError ? '' : 'aviso-temporal ' ?>max-w-6xl mx-auto w-full px-4 pt-6" role="status">
        <div class="rounded-xl border p-4 font-semibold <?= $flashStyles ?>">
          <?= e($flash['message']) ?>
        </div>
      </div>
    <?php endif; ?>
