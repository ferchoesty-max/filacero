# FilaCero

Sistema web de gestión de turnos para ventanillas y módulos de atención escolar (Proyecto 08 · Aplicaciones de Internet · UG DICIS 2026).

## Problema y objetivo

En ventanillas de atención o asesorías, los visitantes suelen amontonarse porque no saben a quién le toca ni cuántas personas faltan. **FilaCero** ordena la atención mediante tres vistas principales:

1. **Kiosco y ticket** para que el visitante elija un servicio y reciba un folio consecutivo por día y servicio (`ASE-001`, `CON-001`, etc.).
2. **Panel del operador** para consultar la cola de turnos pendientes y gestionar la atención (`pendiente` -> `llamado` -> `en_atencion` -> `atendido` o `cancelado`).
3. **Pantalla pública de sala** con el turno actual en grande, turnos en ventanilla y espera por servicio.

## Alcance (MVP)

- Catálogo de servicios configurables por administrador.
- Generación de folios consecutivos por día y servicio sin duplicados.
- Cola de turnos pendientes y máquina de estados en el panel del operador.
- Pantalla pública de sala y métricas diarias.
- Fuera de alcance: WebSockets, impresora de tickets, SMS, notificaciones push y APIs externas.

## Tecnologías

- **Backend:** PHP 8.1+ puro con SQLite (`pdo_sqlite`)
- **Frontend:** HTML5, Tailwind CSS 4 (Play CDN), Google Fonts (`Plus Jakarta Sans`) y JavaScript nativo
- **Control de versiones:** Git + GitHub (`master`, `develop`, ramas `feature/huXX-*`)

## Requisitos y arranque rápido

1. Verificar PHP 8.1+ y la extensión `pdo_sqlite`:
   ```bash
   php -v
   php -m
   ```
   *(En Windows, si falta `pdo_sqlite`, abre el `php.ini` que indica `php --ini` y quita el `;` a `extension=pdo_sqlite` y `extension=sqlite3`. Si Windows bloquea `php.exe`, usa WSL anteponiendo `wsl` a cada comando.)*

2. Crear la base de datos local con los servicios iniciales:
   ```bash
   php scripts/init-db.php
   ```

3. Iniciar el servidor local de desarrollo:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

4. Abrir `http://127.0.0.1:8000` en el navegador.

## Estructura del proyecto

```text
filacero/
├── database/schema.sql          # Definición de tablas en SQLite
├── scripts/init-db.php          # Inicialización de base y datos semilla
├── storage/                     # Base local filacero.sqlite (ignorada por Git)
├── src/
│   ├── bootstrap.php            # Sesión, zona horaria y carga de módulos
│   ├── db.php                   # Conexión PDO SQLite
│   ├── functions.php            # Helpers de seguridad, CSRF, flash y fechas
│   ├── servicios.php            # Consultas del catálogo de servicios
│   └── views/                   # Encabezado y pie compartidos
├── public/                      # Páginas accesibles desde el navegador
└── docs/                        # Historias de usuario, modelo de datos y documentación
```

## Integrantes

| Integrante | Usuario GitHub | Módulo principal |
|---|---|---|
| Daniel Tolentino | `@ferchoesty-max` | Base, datos, pruebas y release |
| Carmen Pérez | `@Jose-UG` | Acceso, permisos y catálogo de servicios |
| Emilio Puente | `@EP59` | Kiosco y generación de folios |
| Ana Monroy | `@AnaMonLOp` | Panel del operador y máquina de estados |
| David Moreno | `@DYeho` | Pantalla pública y métricas del día |
