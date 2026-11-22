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
- Fuera de alcance: WebSockets, impresora de tickets, SMS y notificaciones push. **No usa API externa porque la ficha no la requiere.**

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

2. Crear la base de datos local con los servicios iniciales y usuarios demo (agrega `--demo` para sembrar también 6 turnos de ejemplo):
   ```bash
   php scripts/init-db.php --demo
   ```

3. Iniciar el servidor local de desarrollo:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

4. Abrir `http://127.0.0.1:8000` en el navegador.

## Usuarios demo

`php scripts/init-db.php` crea estos usuarios **ficticios** para probar el acceso de operadores en `http://127.0.0.1:8000/login.php`. Las contraseñas se guardan solo como hash (`password_hash`), nunca en texto plano.

| Usuario | Contraseña | Rol | Ventanilla |
|---|---|---|---|
| `admin` | `Admin2026!` | admin | 1 |
| `operador2` | `Operador2026!` | operador | 2 |
| `operador3` | `Operador2026!` | operador | 3 |

> ⚠️ Son solo para desarrollo local. Si el sistema se instalara en un servidor real, hay que cambiar estas contraseñas o crear usuarios nuevos.

## Pruebas

Para verificar la lógica de negocio sin alterar la base local de demostración (`storage/filacero.sqlite`), ejecuta la suite automática sobre una base SQLite temporal (`FILACERO_DB_PATH`):

```bash
php tests/smoke.php
```

El detalle de los casos automáticos y manuales (incluidos casos negativos) está documentado en [`docs/pruebas.md`](docs/pruebas.md).

## Estructura del proyecto

```text
filacero/
├── database/schema.sql          # Definición de tablas en SQLite
├── scripts/init-db.php          # Inicialización de base y datos semilla
├── storage/                     # Base local filacero.sqlite (ignorada por Git)
├── src/
│   ├── auth.php                 # Registro, hash de contraseñas y sesión de operador
│   ├── bootstrap.php            # Sesión, zona horaria y carga de módulos
│   ├── db.php                   # Conexión PDO SQLite
│   ├── functions.php            # Helpers de seguridad, CSRF, flash y fechas
│   ├── pantalla.php             # Consultas de turnos en ventanilla y espera por servicio
│   ├── servicios.php            # Consultas del catálogo de servicios
│   ├── turnos.php               # Emisión de folios y cola de turnos pendientes
│   └── views/                   # Encabezado, pie y badges compartidos
├── public/                      # Páginas accesibles desde el navegador
├── tests/smoke.php              # Pruebas automáticas sobre SQLite temporal
└── docs/                        # Historias, modelo de datos, pruebas, bocetos y entregas
```

## Integrantes

| Integrante | Usuario GitHub | Módulo principal |
|---|---|---|
| Daniel Tolentino | `@ferchoesty-max` | Base, datos, pruebas y release |
| Carmen Pérez | `@Jose-UG` | Acceso, permisos y catálogo de servicios |
| Emilio Puente | `@EP59` | Kiosco y generación de folios |
| Ana Monroy | `@AnaMonLOp` | Panel del operador y máquina de estados |
| David Moreno | `@DYeho` | Pantalla pública y métricas del día |
