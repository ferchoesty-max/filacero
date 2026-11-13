PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS servicios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  prefijo TEXT NOT NULL UNIQUE CHECK(length(prefijo) BETWEEN 2 AND 4),
  nombre TEXT NOT NULL,
  descripcion TEXT NOT NULL DEFAULT '',
  activo INTEGER NOT NULL DEFAULT 1 CHECK(activo IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS operadores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  usuario TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  rol TEXT NOT NULL DEFAULT 'operador' CHECK(rol IN ('operador', 'admin')),
  ventanilla INTEGER NOT NULL DEFAULT 1,
  activo INTEGER NOT NULL DEFAULT 1 CHECK(activo IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS turnos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  servicio_id INTEGER NOT NULL REFERENCES servicios(id),
  fecha TEXT NOT NULL,
  consecutivo INTEGER NOT NULL CHECK(consecutivo > 0),
  folio TEXT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK(estado IN ('pendiente', 'llamado', 'en_atencion', 'atendido', 'cancelado')),
  operador_id INTEGER REFERENCES operadores(id),
  ventanilla INTEGER,
  motivo_cancelacion TEXT,
  creado_en TEXT NOT NULL,
  llamado_en TEXT,
  atencion_en TEXT,
  cerrado_en TEXT,
  UNIQUE(servicio_id, fecha, consecutivo),
  UNIQUE(fecha, folio)
);

CREATE TABLE IF NOT EXISTS atenciones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  turno_id INTEGER NOT NULL REFERENCES turnos(id),
  operador_id INTEGER NOT NULL REFERENCES operadores(id),
  estado_anterior TEXT NOT NULL CHECK(estado_anterior IN ('pendiente', 'llamado', 'en_atencion', 'atendido', 'cancelado')),
  estado_nuevo TEXT NOT NULL CHECK(estado_nuevo IN ('pendiente', 'llamado', 'en_atencion', 'atendido', 'cancelado')),
  nota TEXT,
  creado_en TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_turnos_fecha_estado ON turnos(fecha, estado);
CREATE INDEX IF NOT EXISTS idx_turnos_servicio_fecha ON turnos(servicio_id, fecha);
