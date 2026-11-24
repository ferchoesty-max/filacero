<?php
declare(strict_types=1);


require_once __DIR__ . '/../src/bootstrap.php';

session_write_close();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $estado = estado_pantalla();

    echo json_encode([
        'actual_id' => $estado['actual'] !== null ? (int) $estado['actual']['id'] : null,
        'ids'       => $estado['turnos_ids'],
        'html'      => pantalla_render_contenido($estado),
        'generado'  => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo obtener el estado de la pantalla.'], JSON_UNESCAPED_UNICODE);
}