<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_login();

$operador    = operador_actual();
$turnoActivo = turno_activo_de((int) $operador['id']);
$cola        = cola_pendiente();
$totalEnFila = count($cola);

$pageTitle = 'Panel del operador';
require __DIR__ . '/../src/views/header.php';
?>

<main class="max-w-6xl mx-auto w-full px-4 py-8 flex-1">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Panel de atención</h1>
            <p class="text-sm text-slate-500">
                Operador: <span class="font-medium text-indigo-600"><?= e($operador['nombre']) ?></span>
                · Ventanilla: <span class="font-medium text-indigo-600"><?= e((string) $operador['ventanilla']) ?></span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section class="lg:col-span-5">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Turno en mi ventanilla</h2>

                <?php if ($turnoActivo === null): ?>
                    <div class="rounded-xl bg-slate-50 border border-dashed border-slate-300 p-6 text-center mb-5">
                        <p class="text-sm text-slate-500">No tienes ningún turno asignado en este momento.</p>
                    </div>

                    <form method="post" action="turno-accion.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="llamar">
                        <button
                            type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition cursor-pointer"
                        >
                            Llamar siguiente
                        </button>
                    </form>
                <?php else: ?>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-5 text-center mb-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <?= e($turnoActivo['servicio_nombre']) ?>
                        </p>
                        <p class="text-5xl font-extrabold text-slate-900 my-2 tracking-tight">
                            <?= e($turnoActivo['folio']) ?>
                        </p>
                        <div class="mt-2">
                            <?php
                            $estado = $turnoActivo['estado'];
                            require __DIR__ . '/../src/views/estado-badge.php';
                            ?>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php if ($turnoActivo['estado'] === 'llamado'): ?>
                            <form method="post" action="turno-accion.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="en_atencion">
                                <input type="hidden" name="turno_id" value="<?= e((string) $turnoActivo['id']) ?>">
                                <button
                                    type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl transition cursor-pointer"
                                >
                                    Iniciar atención
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($turnoActivo['estado'] === 'en_atencion'): ?>
                            <form method="post" action="turno-accion.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="atendido">
                                <input type="hidden" name="turno_id" value="<?= e((string) $turnoActivo['id']) ?>">
                                <button
                                    type="submit"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2.5 rounded-xl transition cursor-pointer"
                                >
                                    Marcar atendido
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="turno-accion.php" data-confirm="¿Seguro que deseas cancelar el turno <?= e($turnoActivo['folio']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="cancelado">
                            <input type="hidden" name="turno_id" value="<?= e((string) $turnoActivo['id']) ?>">
                            <button
                                type="submit"
                                class="w-full bg-rose-600 hover:bg-rose-700 text-white font-medium px-4 py-2 rounded-xl transition cursor-pointer"
                            >
                                Cancelar turno
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="lg:col-span-7">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-800">Cola de turnos pendientes</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                        <?= e((string) $totalEnFila) ?> en fila
                    </span>
                </div>

                <?php if ($totalEnFila === 0): ?>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-8 text-center">
                        <p class="text-slate-600 font-medium">No hay turnos pendientes</p>
                        <p class="text-sm text-slate-400 mt-1">Los nuevos turnos aparecerán aquí en orden de llegada.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 text-xs uppercase text-slate-500">
                                    <th class="py-3 px-2">Folio</th>
                                    <th class="py-3 px-2">Servicio</th>
                                    <th class="py-3 px-2">Hora</th>
                                    <th class="py-3 px-2 text-right">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php foreach ($cola as $turno): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3 px-2 font-bold text-slate-900">
                                            <?= e($turno['folio']) ?>
                                        </td>
                                        <td class="py-3 px-2 text-slate-700">
                                            <?= e($turno['servicio_nombre']) ?>
                                        </td>
                                        <td class="py-3 px-2 text-slate-500">
                                            <?= e(hora($turno['creado_en'])) ?>
                                        </td>
                                        <td class="py-3 px-2 text-right">
                                            <?php
                                            $estado = $turno['estado'];
                                            require __DIR__ . '/../src/views/estado-badge.php';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>