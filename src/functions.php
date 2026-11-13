<?php
declare(strict_types=1);

const ESTADOS_TURNO = ['pendiente', 'llamado', 'en_atencion', 'atendido', 'cancelado'];

const ESTADO_LABELS = [
    'pendiente'   => 'Pendiente',
    'llamado'     => 'Llamado',
    'en_atencion' => 'En atención',
    'atendido'    => 'Atendido',
    'cancelado'   => 'Cancelado',
];

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function ensure_post_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? null;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($sent)
        || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Solicitud no autorizada. Regresa e inténtalo de nuevo.');
    }
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (is_array($flash) && isset($flash['message'])) {
        return $flash;
    }
    return null;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function hora(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '' : date('H:i', $ts);
}

function input_string(array $source, string $key, int $maxLen = 255): string
{
    $val = isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
    return function_exists('mb_substr') ? mb_substr($val, 0, $maxLen, 'UTF-8') : substr($val, 0, $maxLen);
}

function input_id(array $source, string $key = 'id'): int
{
    $id = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : $id;
}
