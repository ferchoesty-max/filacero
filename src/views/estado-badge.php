<?php
// Espera la variable $estado (string)

$clasesEstado = [
    'pendiente'   => 'bg-amber-100 text-amber-800',
    'llamado'     => 'bg-blue-100 text-blue-800 animate-pulse',
    'en_atencion' => 'bg-indigo-100 text-indigo-800',
    'atendido'    => 'bg-emerald-100 text-emerald-800',
    'cancelado'   => 'bg-rose-100 text-rose-800',
];

$etiquetasEstado = [
    'pendiente'   => 'Pendiente',
    'llamado'     => 'Llamado',
    'en_atencion' => 'En atención',
    'atendido'    => 'Atendido',
    'cancelado'   => 'Cancelado',
];

$clase = $clasesEstado[$estado] ?? 'bg-slate-100 text-slate-700';
$texto = $etiquetasEstado[$estado] ?? ucfirst($estado);
?>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= e($clase) ?>">
    <?= e($texto) ?>
</span>