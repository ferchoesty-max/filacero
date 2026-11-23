<?php
$valores = $valores ?? [];
$errores = $errores ?? [];
$botonTexto = $botonTexto ?? 'Guardar servicio';

$inputBase = 'w-full rounded-xl border bg-white px-4 py-3 outline-none focus:ring-2';
$inputNormal = $inputBase . ' border-slate-300 focus:border-indigo-600 focus:ring-indigo-200';
$inputError = $inputBase . ' border-rose-400 focus:border-rose-600 focus:ring-rose-200';
?>
<form method="post" action="<?= e($formAction) ?>" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5" novalidate>
  <?= csrf_field() ?>
  <?php if (!empty($servicioId)): ?>
    <input type="hidden" name="id" value="<?= e($servicioId) ?>">
  <?php endif; ?>

  <div>
    <label class="block font-bold text-sm mb-2" for="prefijo">Prefijo</label>
    <input id="prefijo" name="prefijo" maxlength="4"
           class="<?= isset($errores['prefijo']) ? $inputError : $inputNormal ?> uppercase"
           value="<?= e($valores['prefijo'] ?? '') ?>"
           <?= isset($errores['prefijo']) ? 'aria-invalid="true" aria-describedby="error-prefijo"' : '' ?>
           required>
    <?php if (isset($errores['prefijo'])): ?>
      <p id="error-prefijo" class="text-sm text-rose-700 font-semibold mt-2"><?= e($errores['prefijo']) ?></p>
    <?php else: ?>
      <p class="text-sm text-slate-500 mt-2">De 2 a 4 letras. Es el inicio del folio, por ejemplo TIT-001.</p>
    <?php endif; ?>
  </div>

  <div>
    <label class="block font-bold text-sm mb-2" for="nombre">Nombre</label>
    <input id="nombre" name="nombre" maxlength="60"
           class="<?= isset($errores['nombre']) ? $inputError : $inputNormal ?>"
           value="<?= e($valores['nombre'] ?? '') ?>"
           <?= isset($errores['nombre']) ? 'aria-invalid="true" aria-describedby="error-nombre"' : '' ?>
           required>
    <?php if (isset($errores['nombre'])): ?>
      <p id="error-nombre" class="text-sm text-rose-700 font-semibold mt-2"><?= e($errores['nombre']) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label class="block font-bold text-sm mb-2" for="descripcion">Descripción <span class="font-medium text-slate-500">(opcional)</span></label>
    <textarea id="descripcion" name="descripcion" maxlength="160" rows="3"
              class="<?= isset($errores['descripcion']) ? $inputError : $inputNormal ?>"
              <?= isset($errores['descripcion']) ? 'aria-invalid="true" aria-describedby="error-descripcion"' : '' ?>><?= e($valores['descripcion'] ?? '') ?></textarea>
    <?php if (isset($errores['descripcion'])): ?>
      <p id="error-descripcion" class="text-sm text-rose-700 font-semibold mt-2"><?= e($errores['descripcion']) ?></p>
    <?php endif; ?>
  </div>

  <div class="flex flex-wrap gap-3">
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition">
      <?= e($botonTexto) ?>
    </button>
    <?php if (!empty($cancelarUrl)): ?>
      <a href="<?= e($cancelarUrl) ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2.5 rounded-xl transition">Cancelar</a>
    <?php endif; ?>
  </div>
</form>
