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
