# Entrega — Revisión 1 (19 de noviembre de 2026)

**Proyecto:** FilaCero (08 — Sistema de turnos para atención en pequeños servicios)  
**Equipo:** Daniel Tolentino (`@ferchoesty-max`), Carmen Pérez (`@Jose-UG`), Emilio Puente (`@EP59`), Ana Monroy (`@AnaMonLOp`), David Moreno (`@DYeho`)  
**Repositorio GitHub:** https://github.com/ferchoesty-max/filacero  
**Rama de integración:** `develop` (`master` reservada para `release/1.0.0` en la entrega final)

## 1. Objetivo de esta entrega

Entregar la base navegable y responsiva del proyecto con SQLite PDO, autenticación de operadores, emisión real de folios consecutivos por servicio y día desde el kiosco, visualización de la cola de turnos pendientes en el panel, pantalla pública de sala con conteos de espera, y documentación inicial (historias de usuario, modelo de datos, reglas de numeración, diagrama de estados y capturas de interfaz).

## 2. Funcionalidades implementadas

| Historia / Issue | Qué hace | Responsable | Rama feature | PR | Estado |
|---|---|---|---|---|---|
| [HU-01 (#1)](https://github.com/ferchoesty-max/filacero/issues/1) | Estructura base, esquema SQLite (`schema.sql`), conexión PDO, helpers CSRF/HTML/flash, catálogo base y vistas responsivas | Daniel Tolentino | `feature/hu01-base` | [PR #16](https://github.com/ferchoesty-max/filacero/pull/16) | Cerrada e integrada |
| [HU-02 (#2)](https://github.com/ferchoesty-max/filacero/issues/2) | Registro con `password_hash`, login de operador (`public/login.php`), cierre por POST (`public/logout.php`), `require_login()` y usuarios demo | Carmen Pérez | `feature/hu02-login` | [PR #17](https://github.com/ferchoesty-max/filacero/pull/17) | Cerrada e integrada |
| [HU-03 (#3)](https://github.com/ferchoesty-max/filacero/issues/3) | Emisión de folios `PREFIJO-NNN` por servicio y día (`src/turnos.php`), formulario POST en kiosco (`public/index.php`) y ticket con 404 (`public/ticket.php`) | Emilio Puente | `feature/hu03-kiosco-folios` | [PR #18](https://github.com/ferchoesty-max/filacero/pull/18) | Cerrada e integrada |
| [HU-04 (#4)](https://github.com/ferchoesty-max/filacero/issues/4) | Consulta `cola_pendiente()`, panel protegido (`public/panel.php`), badge de estados, `--demo` en `init-db.php` y diagrama Mermaid | Ana Monroy | `feature/hu04-panel-cola` | [PR #19](https://github.com/ferchoesty-max/filacero/pull/19) | Cerrada e integrada |
| [HU-05 (#5)](https://github.com/ferchoesty-max/filacero/issues/5) | Pantalla pública (`public/pantalla.php`), `turnos_en_ventanilla()`, `espera_por_servicio()` y total en espera en kiosco | David Moreno | `feature/HU-005` | [PR #20](https://github.com/ferchoesty-max/filacero/pull/20) | Cerrada e integrada |

## 3. Instrucciones para ejecutar (paso a paso)

1. Verificar PHP 8.1+ y la extensión `pdo_sqlite`:
   ```bash
   php -v
   php -m
   ```
2. Clonar el repositorio (rama por defecto `develop`):
   ```bash
   git clone https://github.com/ferchoesty-max/filacero.git
   cd filacero
   ```
3. No requiere archivo `.env`, claves externas ni secretos.
4. Inicializar la base SQLite con los 4 servicios, 3 operadores demo y 6 turnos de ejemplo:
   ```bash
   php scripts/init-db.php --demo
   ```
5. Iniciar el servidor local de PHP:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
6. Abrir `http://127.0.0.1:8000` en el navegador. Credenciales demo para `http://127.0.0.1:8000/login.php`: `admin / Admin2026!`, `operador2 / Operador2026!`, `operador3 / Operador2026!`.

## 4. Modelo de datos y cambios en esta revisión

- **Esquema SQLite ([database/schema.sql](../../database/schema.sql) y [docs/modelo-datos.md](../modelo-datos.md)):**
  - `servicios`: catálogo con `prefijo` único (`ASE`, `CON`, `BEC`, `INS`), `nombre`, `descripcion` y bandera `activo`.
  - `operadores`: cuentas de acceso con `usuario` único, `password_hash`, `rol` (`admin` o `operador`) y `ventanilla`.
  - `turnos`: folios con `UNIQUE(servicio_id, fecha, consecutivo)` y `UNIQUE(fecha, folio)`, `CHECK` de estados (`pendiente`, `llamado`, `en_atencion`, `atendido`, `cancelado`) y llave foránea a `servicios` y `operadores`.
  - `atenciones`: bitácora de transiciones de estado para la máquina de estados (Semana 2).
- **Reglas de numeración y diagrama de estados ([docs/estados-y-numeracion.md](../estados-y-numeracion.md)):** serie por servicio y día (`PREFIJO-NNN`), reinicio diario en `001` y diagrama Mermaid de transiciones válidas.

## 5. Evidencia de pruebas

Verificación ejecutada en servidor local PHP (`http://127.0.0.1:8000`) con base recién sembrada (`php scripts/init-db.php --demo`), revisión de sintaxis `php -l` en los 17 archivos `.php` y recorrido automatizado en Chrome headless:

| ID | Acción | Resultado esperado | Resultado obtenido | Evidencia | Estado |
|---|---|---|---|---|---|
| R1-01 | Correr `php -l` en todos los `.php` y `php scripts/init-db.php --demo` | 0 errores de sintaxis; crea 4 servicios, 3 usuarios y 6 turnos | 17/17 archivos sin errores; 4 servicios, 3 usuarios y 6 turnos creados | Salida CLI | Aprobada |
| R1-02 | Abrir `/index.php` en escritorio (`1280x800`) y celular (`390x844`) | Muestra los 4 servicios activos y `6 personas en espera` | Renderizado responsivo correcto con los 4 servicios y conteo exacto | `docs/bocetos/kiosco-escritorio.png`, `docs/bocetos/kiosco-celular.png` | Aprobada |
| R1-03 | Pulsar **Tomar turno** en Asesoría Académica y recargar `/ticket.php?id=7` | Emite `ASE-003` en estado `Pendiente` y persiste al recargar | Redirigió a `/ticket.php?id=7` con `ASE-003` y conservó el folio tras recargar | `docs/bocetos/ticket-escritorio.png`, `docs/bocetos/ticket-celular.png` | Aprobada |
| R1-04 | Abrir `/ticket.php?id=9999` (negativa) | Responde código HTTP `404` y mensaje `El turno no existe` | HTTP `404` con aviso `El turno no existe` y botón al kiosco | Respuesta HTTP verificada | Aprobada |
| R1-05 | Abrir `/panel.php` sin sesión y probar login con contraseña incorrecta (negativa) | Redirige a `/login.php` y muestra `Usuario o contraseña incorrectos` | Redirigió a `login.php`; clave errónea mostró el error genérico | `docs/bocetos/login-error.png` | Aprobada |
| R1-06 | Iniciar sesión con `admin / Admin2026!` y revisar `/panel.php` | Accede al panel y lista `7 en fila` (`ASE-001`..`ASE-003`) | Sesión iniciada en Ventanilla 1; cola muestra los 7 turnos en orden | `docs/bocetos/panel-escritorio.png`, `docs/bocetos/panel-celular.png` | Aprobada |
| R1-07 | Abrir `/pantalla.php` y probar `POST /index.php` sin CSRF (negativa) | Pantalla muestra `Sin llamados por ahora` y espera; POST sin CSRF da `403` | `pantalla.php` mostró `7 personas en espera` (`ASE: 3, BEC: 1, CON: 2, INS: 1`); POST sin CSRF devolvió `403` | `docs/bocetos/pantalla-escritorio.png`, `docs/bocetos/pantalla-celular.png` | Aprobada |

### Capturas de interfaz en `docs/bocetos/`

- Kiosco: [`kiosco-escritorio.png`](../bocetos/kiosco-escritorio.png) · [`kiosco-celular.png`](../bocetos/kiosco-celular.png)
- Ticket: [`ticket-escritorio.png`](../bocetos/ticket-escritorio.png) · [`ticket-celular.png`](../bocetos/ticket-celular.png)
- Login: [`login-escritorio.png`](../bocetos/login-escritorio.png) · [`login-celular.png`](../bocetos/login-celular.png) · [`login-error.png`](../bocetos/login-error.png)
- Panel del operador: [`panel-escritorio.png`](../bocetos/panel-escritorio.png) · [`panel-celular.png`](../bocetos/panel-celular.png)
- Pantalla pública: [`pantalla-escritorio.png`](../bocetos/pantalla-escritorio.png) · [`pantalla-celular.png`](../bocetos/pantalla-celular.png)

## 6. GitHub: enlaces a commits, PR, issues y release

- **Ramas activas:** `master` (base inicial), `develop` (integración) y ramas `feature/*` por historia. *(El flujo `release/1.0.0` y la etiqueta `v1.0.0` corresponden a la Revisión 3).*
- **Issues de Semana 1 (todos cerrados):** [#1](https://github.com/ferchoesty-max/filacero/issues/1), [#2](https://github.com/ferchoesty-max/filacero/issues/2), [#3](https://github.com/ferchoesty-max/filacero/issues/3), [#4](https://github.com/ferchoesty-max/filacero/issues/4), [#5](https://github.com/ferchoesty-max/filacero/issues/5).
- **Pull Requests revisados e integrados en `develop`:**
  - [PR #16](https://github.com/ferchoesty-max/filacero/pull/16) (HU-01 · merge `4387692`, revisó `@Jose-UG`)
  - [PR #17](https://github.com/ferchoesty-max/filacero/pull/17) (HU-02 · merge `b8704a4`, revisó `@AnaMonLOp`)
  - [PR #18](https://github.com/ferchoesty-max/filacero/pull/18) (HU-03 · merge `c2bac9d`, revisó `@AnaMonLOp`)
  - [PR #19](https://github.com/ferchoesty-max/filacero/pull/19) (HU-04 · merge `8411713`, revisó `@DYeho`)
  - [PR #20](https://github.com/ferchoesty-max/filacero/pull/20) (HU-05 · merge `8641906`, revisó `@ferchoesty-max`)

## 7. Problemas conocidos, pendientes y riesgos

- **Pendiente para Semana 2 (HU-06 a HU-10):**
  - El botón **Llamar siguiente** del panel está visible como maqueta y se conectará con la máquina de estados en `HU-08` (por eso la pantalla pública muestra `Sin llamados por ahora` en flujo real, aunque `src/pantalla.php` incluye `?demo=mixto` temporal para previsualizar llamados).
  - La protección de concurrencia `BEGIN IMMEDIATE` y el bloqueo de servicios inactivos al emitir folios se completan en `HU-07`.
  - El CRUD de servicios (`HU-06`), sondeo automático de pantalla (`HU-09`) y suite automatizada `tests/smoke.php` (`HU-10`) entran en Semana 2.
- **Riesgos:** dependencia del CDN de Tailwind/Google Fonts si falla el internet del salón (la app sigue funcionando en HTML semántico; respaldar compartiendo datos del celular durante la demo).

## 8. Participación por integrante (tarea, commit y evidencia)

| Integrante | Usuario | Tarea principal (Semana 1) | Commits representativos | Evidencia |
|---|---|---|---|---|
| **Daniel Tolentino** | `@ferchoesty-max` | HU-01 Base, SQLite, helpers, vistas comunes y revisión de HU-05 | `637de24`, `4a84d33`, `2f1fa73`, `7ab8f9d` | [PR #16](https://github.com/ferchoesty-max/filacero/pull/16), `database/schema.sql`, `docs/modelo-datos.md` |
| **Carmen Pérez** | `@Jose-UG` | HU-02 Login, sesiones seguras, usuarios demo y revisión de HU-01 | `db189e2`, `c542726`, `9270058`, `70fbba6`, `cdbb600`, `f37a8bb` | [PR #17](https://github.com/ferchoesty-max/filacero/pull/17), `src/auth.php`, `docs/bocetos/login-*.png` |
| **Emilio Puente** | `@EP59` | HU-03 Kiosco POST+CSRF, `emitir_turno()`, ticket y reglas de numeración | `8bc03ec`, `04a3e84`, `77c675f`, `797146b` | [PR #18](https://github.com/ferchoesty-max/filacero/pull/18), `src/turnos.php`, `public/ticket.php` |
| **Ana Monroy** | `@AnaMonLOp` | HU-04 Panel del operador, `cola_pendiente()`, `--demo`, badge y diagrama de estados | `5ec746b`, `873d6d8`, `0bf960d`, `a27d609`, `6ea27dc`, `c6370c6` | [PR #19](https://github.com/ferchoesty-max/filacero/pull/19), `public/panel.php`, `docs/estados-y-numeracion.md` |
| **David Moreno** | `@DYeho` | HU-05 Pantalla pública, `turnos_en_ventanilla()`, `espera_por_servicio()` y conteo en kiosco | `43a67a0`, `28a2afe`, `7711904`, `383d67b`, `ad28c12`, `980e649` | [PR #20](https://github.com/ferchoesty-max/filacero/pull/20), `public/pantalla.php`, `src/pantalla.php` |

## 9. Guion de demostración (Revisión 1)

1. **GitHub y estructura (1 min — Daniel):** mostrar ramas `master`, `develop`, `feature/*`, los 5 Issues cerrados y los PRs revisados (`#16`–`#20`), además de `database/schema.sql` y `storage/` ignorada.
2. **Arranque limpio (30 s — Daniel):** correr `php scripts/init-db.php --demo` y `php -S 127.0.0.1:8000 -t public`.
3. **Kiosco y emisión real de folios (1 min — Emilio):** abrir `http://127.0.0.1:8000`, pedir dos turnos de distintos servicios, mostrar los folios consecutivos en `ticket.php` y recargar la página para comprobar persistencia. Probar `ticket.php?id=999` (404).
4. **Seguridad y login (1 min — Carmen):** intentar abrir `panel.php` sin sesión (redirige a `login.php`), probar contraseña inválida y luego iniciar sesión con `admin / Admin2026!`.
5. **Panel del operador y estados (1 min — Ana):** mostrar la cola ordenada con los folios recién emitidos junto a los demo y explicar el diagrama de estados en `docs/estados-y-numeracion.md`.
6. **Pantalla pública y responsividad (1 min — David):** abrir `pantalla.php` para mostrar el conteo actualizado por servicio (y `?demo=mixto` para mostrar cómo se ven los llamados en sala), más vista móvil/escritorio.
