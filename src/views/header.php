<?php
$bodyClasses = $bodyClass ?? "min-h-screen flex flex-col bg-slate-50 text-slate-800 font-['Plus_Jakarta_Sans']";
$flash = pull_flash();
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
  </head>
  <body class="<?= e($bodyClasses) ?>">
    <?php if (empty($hideNav)): ?>
      <header class="bg-white border-b border-slate-200">
        <nav class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap gap-4 items-center justify-between">
          <a href="index.php" class="text-2xl font-extrabold tracking-tight text-indigo-600">
            Fila<span class="text-slate-800">Cero</span>
          </a>
          <div class="flex flex-wrap items-center gap-4 text-sm font-semibold">
            <a href="index.php" class="hover:text-indigo-600 transition">Kiosco</a>
            <a href="pantalla.php" class="hover:text-indigo-600 transition">Pantalla</a>
            <?php if (function_exists('operador_actual') && operador_actual() !== null): ?>
              <a href="panel.php" class="hover:text-indigo-600 transition">Panel</a>
            <?php else: ?>
              <a href="login.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl transition">Operadores</a>
            <?php endif; ?>
          </div>
        </nav>
      </header>
    <?php endif; ?>
    <?php if ($flash !== null): ?>
      <?php
        $isError = ($flash['type'] ?? 'success') === 'error';
        $flashStyles = $isError
            ? 'bg-rose-50 border-rose-200 text-rose-800'
            : 'bg-emerald-50 border-emerald-200 text-emerald-800';
      ?>
      <div class="max-w-6xl mx-auto w-full px-4 pt-6" role="status">
        <div class="rounded-xl border p-4 font-semibold <?= $flashStyles ?>">
          <?= e($flash['message']) ?>
        </div>
      </div>
    <?php endif; ?>
