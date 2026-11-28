<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_login();
ensure_post_csrf();

$operador = operador_actual();
$accion   = input_string($_POST, 'accion', 30);
$turnoId  = input_id($_POST, 'turno_id');

try {
    if ($accion === 'llamar') {
        $ventanilla = (int) ($operador['ventanilla'] ?? 1);
        $servicioRaw = input_id($_POST, 'servicio_id');
        $servicioId  = $servicioRaw > 0 ? $servicioRaw : null;
        $turno = llamar_siguiente((int) $operador['id'], $ventanilla, $servicioId);

        if ($turno === null) {
            set_flash('No hay turnos en espera. La cola está vacía.', 'info');
        } else {
            set_flash('Llamando al turno ' . $turno['folio'] . ' a ventanilla ' . $ventanilla . '.', 'success');
        }
    } elseif (in_array($accion, ['en_atencion', 'atendido', 'cancelado'], true)) {
        if ($turnoId === 0) {
            throw new ReglaNegocioException('El turno no existe');
        }

        $motivo = null;
        if ($accion === 'cancelado') {
            $motivoRaw = isset($_POST['motivo']) && is_string($_POST['motivo']) ? trim($_POST['motivo']) : '';
            $longitud = function_exists('mb_strlen') ? mb_strlen($motivoRaw, 'UTF-8') : strlen($motivoRaw);
            if ($longitud > 120) {
                throw new ReglaNegocioException('El motivo de cancelación no puede exceder 120 caracteres.');
            }
            $motivo = $motivoRaw !== '' ? $motivoRaw : null;
        }

        $turnoActualizado = cambiar_estado($turnoId, $accion, (int) $operador['id'], $motivo);

        $mensajes = [
            'en_atencion' => 'Atención iniciada para el folio ' . $turnoActualizado['folio'] . '.',
            'atendido'    => 'Turno ' . $turnoActualizado['folio'] . ' marcado como atendido.',
            'cancelado'   => 'Turno ' . $turnoActualizado['folio'] . ' cancelado.',
        ];
        set_flash($mensajes[$accion], 'success');
    } else {
        throw new ReglaNegocioException('Acción no reconocida.');
    }
} catch (ReglaNegocioException $e) {
    set_flash($e->getMessage(), 'error');
} catch (Throwable $e) {
    set_flash('Ocurrió un error inesperado al procesar el turno.', 'error');
}

redirect('panel.php');