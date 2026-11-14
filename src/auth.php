<?php
declare(strict_types=1);

function find_operador_por_usuario(string $usuario): ?array
{
    $stmt = db()->prepare('SELECT * FROM operadores WHERE usuario = :usuario');
    $stmt->execute([':usuario' => $usuario]);
    return $stmt->fetch() ?: null;
}

function create_operador(string $nombre, string $usuario, string $password, string $rol, int $ventanilla): int
{
    if (!in_array($rol, ['operador', 'admin'], true)) {
        throw new InvalidArgumentException('Rol inválido.');
    }
    $stmt = db()->prepare('INSERT INTO operadores(nombre, usuario, password_hash, rol, ventanilla, created_at)
                           VALUES(:nombre, :usuario, :hash, :rol, :ventanilla, :created_at)');
    $stmt->execute([
        ':nombre' => $nombre,
        ':usuario' => $usuario,
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':rol' => $rol,
        ':ventanilla' => $ventanilla,
        ':created_at' => now(),
    ]);
    return (int) db()->lastInsertId();
}

function verificar_credenciales(string $usuario, string $password): ?array
{
    $operador = find_operador_por_usuario($usuario);
    if ($operador === null || (int) $operador['activo'] !== 1) {
        return null;
    }
    return password_verify($password, (string) $operador['password_hash']) ? $operador : null;
}

function iniciar_sesion(array $operador): void
{
    session_regenerate_id(true);
    $_SESSION['operador'] = [
        'id' => (int) $operador['id'],
        'nombre' => (string) $operador['nombre'],
        'usuario' => (string) $operador['usuario'],
        'rol' => (string) $operador['rol'],
        'ventanilla' => (int) $operador['ventanilla'],
    ];
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function operador_actual(): ?array
{
    return isset($_SESSION['operador']['id']) ? $_SESSION['operador'] : null;
}

function require_login(): array
{
    $operador = operador_actual();
    if ($operador === null) {
        set_flash('Inicia sesión para entrar al panel.', 'error');
        redirect('login.php');
    }
    return $operador;
}
