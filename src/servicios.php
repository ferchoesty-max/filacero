<?php
declare(strict_types=1);

function all_servicios(bool $soloActivos = false): array
{
    $sql = 'SELECT * FROM servicios';
    if ($soloActivos) {
        $sql .= ' WHERE activo = 1';
    }
    $sql .= ' ORDER BY id ASC';
    return db()->query($sql)->fetchAll();
}

function find_servicio(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM servicios WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function create_servicio(array $data): int
{
    $ts = now();
    $sql = 'INSERT INTO servicios(prefijo, nombre, descripcion, activo, created_at, updated_at)
            VALUES(:prefijo, :nombre, :descripcion, :activo, :created_at, :updated_at)';
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':prefijo'     => strtoupper(trim((string) $data['prefijo'])),
        ':nombre'      => trim((string) $data['nombre']),
        ':descripcion' => trim((string) ($data['descripcion'] ?? '')),
        ':activo'      => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ':created_at'  => $ts,
        ':updated_at'  => $ts,
    ]);
    return (int) db()->lastInsertId();
}

function prefijo_en_uso(string $prefijo, ?int $excluirId = null): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM servicios WHERE prefijo = :prefijo AND id != :id');
    $stmt->execute([':prefijo' => $prefijo, ':id' => $excluirId ?? 0]);
    return (int) $stmt->fetchColumn() > 0;
}

function validate_servicio(array $data, ?int $excluirId = null): array
{
    $errores = [];
    $prefijo = strtoupper(trim((string) ($data['prefijo'] ?? '')));
    $nombre = trim((string) ($data['nombre'] ?? ''));
    $descripcion = trim((string) ($data['descripcion'] ?? ''));

    if (!preg_match('/^[A-Z]{2,4}$/', $prefijo)) {
        $errores['prefijo'] = 'El prefijo debe tener de 2 a 4 letras, sin números ni espacios.';
    } elseif (prefijo_en_uso($prefijo, $excluirId)) {
        $errores['prefijo'] = 'Ese prefijo ya lo usa otro servicio.';
    }

    if (!preg_match('/^.{3,60}$/us', $nombre)) {
        $errores['nombre'] = 'El nombre debe tener entre 3 y 60 caracteres.';
    }

    if (!preg_match('/^.{0,160}$/us', $descripcion)) {
        $errores['descripcion'] = 'La descripción puede tener máximo 160 caracteres.';
    }

    return $errores;
}

function update_servicio(int $id, array $data): bool
{
    $sql = 'UPDATE servicios
            SET prefijo = :prefijo, nombre = :nombre, descripcion = :descripcion, updated_at = :updated_at
            WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':prefijo'     => strtoupper(trim((string) $data['prefijo'])),
        ':nombre'      => trim((string) $data['nombre']),
        ':descripcion' => trim((string) ($data['descripcion'] ?? '')),
        ':updated_at'  => now(),
        ':id'          => $id,
    ]);
    return $stmt->rowCount() === 1;
}

function set_servicio_activo(int $id, bool $activo): bool
{
    $stmt = db()->prepare('UPDATE servicios SET activo = :activo, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        ':activo'     => $activo ? 1 : 0,
        ':updated_at' => now(),
        ':id'         => $id,
    ]);
    return $stmt->rowCount() === 1;
}
