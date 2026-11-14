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
