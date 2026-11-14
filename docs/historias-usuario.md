# Historias de usuario · FilaCero

Cada historia corresponde a un Issue de GitHub con su etiqueta de semana (`semana-1`, `semana-2` o `semana-3`) y su responsable asignado.

| ID | Historia | Responsable | Semana |
|---|---|---|---|
| HU-01 | Como **equipo**, queremos la base del proyecto (estructura, base de datos y diseño común) para construir todos sobre lo mismo | Daniel | 1 |
| HU-02 | Como **operador**, quiero iniciar sesión para que solo el personal entre al panel | Carmen | 1 |
| HU-03 | Como **visitante**, quiero elegir un servicio y recibir un folio para saber mi lugar | Emilio | 1 |
| HU-04 | Como **operador**, quiero ver la cola de turnos pendientes para saber a quién sigue | Ana | 1 |
| HU-05 | Como **visitante en sala**, quiero ver el turno actual y a qué ventanilla pasar | David | 1 |
| HU-06 | Como **admin**, quiero administrar el catálogo de servicios para abrir o cerrar trámites | Carmen | 2 |
| HU-07 | Como **visitante**, quiero que mi folio sea único y que no me den turno de un servicio cerrado | Emilio | 2 |
| HU-08 | Como **operador**, quiero llamar al siguiente, iniciar la atención y marcarla como atendida | Ana | 2 |
| HU-09 | Como **visitante en sala**, quiero que la pantalla se actualice sola | David | 2 |
| HU-10 | Como **equipo**, queremos pruebas automáticas y manuales para comprobar las reglas | Daniel | 2 |
| HU-11 | Como **admin**, quiero que las acciones estén protegidas por rol y que el login se bloquee tras intentos fallidos | Carmen | 3 |
| HU-12 | Como **visitante**, quiero ver cuántas personas hay antes que yo y si ya me llamaron | Emilio | 3 |
| HU-13 | Como **operador**, quiero cancelar con motivo y filtrar la cola por servicio | Ana | 3 |
| HU-14 | Como **coordinador**, quiero métricas del día para saber cuánto se espera | David | 3 |
| HU-15 | Como **equipo**, queremos la versión 1.0.0 documentada e instalable | Daniel | 3 |

## Criterios de aceptación (Dado / Cuando / Entonces)

### HU-01 · Base del proyecto
- Dado un repositorio recién clonado, cuando ejecuto `php scripts/init-db.php`, entonces se crea `storage/filacero.sqlite` con las 4 tablas y los 4 servicios iniciales.
- Cuando ejecuto `php -S 127.0.0.1:8000 -t public`, entonces las páginas comparten encabezado y pie y se ven bien en celular y escritorio.
- El archivo `.sqlite` permanece fuera de `public/` e ignorado por Git.

### HU-02 · Login de operador
- Con un usuario demo y la contraseña correcta, llego al panel y veo mi nombre y ventanilla.
- Con contraseña incorrecta veo "Usuario o contraseña incorrectos", sin indicar cuál campo falló.
- Si abro `panel.php` sin sesión, me redirige al login.
- Cerrar sesión solo funciona por POST con token CSRF.

### HU-03 · Pedir turno
- En el kiosco, cuando elijo "Asesoría Académica", se guarda un turno `ASE-001` en estado `pendiente` y veo mi ticket.
- Si otra persona pide el mismo servicio, recibe `ASE-002`.
- Si recargo la página, el turno sigue guardado.

### HU-04 · Cola en el panel
- Con turnos pendientes, al entrar al panel los veo en orden de llegada con folio, servicio y hora.
- Si no hay turnos pendientes, veo "No hay turnos pendientes".

### HU-05 · Pantalla pública
- Con un turno llamado, en `pantalla.php` veo el folio en grande y la ventanilla.
- Veo cuántas personas esperan por servicio. Si nadie ha sido llamado, veo "Sin llamados por ahora".

### HU-06 · Catálogo de servicios
- Como admin, cuando creo "Titulación" con prefijo `TIT`, aparece en el kiosco.
- Con un prefijo repetido o inválido, veo el error junto al campo y no se guarda.
- Si desactivo un servicio, el kiosco deja de mostrarlo y no se emiten turnos de él.
- Un operador sin rol admin que abre `servicios.php` recibe 403.

### HU-07 · Folio seguro
- 30 turnos seguidos del mismo servicio dan 30 folios distintos y consecutivos.
- Si se intenta insertar un folio repetido en la misma serie, la base lo rechaza.
- Pedir turno de un servicio inactivo o inexistente muestra un error claro.
- Al día siguiente la serie vuelve a empezar en `001`.

### HU-08 · Atender turnos
- Al pulsar "Llamar siguiente", el turno más antiguo pasa a `llamado` con mi ventanilla.
- Desde `llamado` puedo "Iniciar atención"; desde `en_atencion`, "Marcar atendido".
- Un salto no permitido (por ejemplo de `llamado` directo a `atendido`, o regresar un `atendido`) muestra un error y no cambia nada.
- Operar un turno inexistente muestra "El turno no existe".
- Cada cambio queda registrado en la tabla `atenciones`.

### HU-09 · Pantalla en vivo
- Con la pantalla abierta, cuando un operador llama un turno, aparece en 5 s o menos sin recargar.
- Si el servidor no responde, se conserva lo último y aparece "Sin conexión, reintentando…".
- Con el sonido activado, suena una campana cuando cambia el turno actual.

### HU-10 · Pruebas
- `php tests/smoke.php` corre sobre una base temporal (`FILACERO_DB_PATH`) y no toca la de la demo.
- `docs/pruebas.md` tiene al menos 5 pruebas manuales con resultado (al menos 2 negativas).

### HU-11 · Permisos y cuenta
- Un operador no puede atender ni cerrar el turno de otro.
- Tras 5 contraseñas incorrectas, el login se bloquea 60 s.
- Todo formulario POST sin token CSRF válido responde 403.

### HU-12 · Mi posición en la fila
- En el ticket veo "Personas antes que tú: N" y, cuando me llaman, "Pasa a Ventanilla X".
- Un ticket inexistente muestra 404 con un enlace para pedir turno.

### HU-13 · Cancelar y filtrar
- Puedo cancelar con motivo (máx. 120 caracteres) y el motivo se ve en métricas.
- Puedo llamar al siguiente de un servicio específico.
- Con la cola vacía, "Llamar siguiente" muestra un aviso y no falla.

### HU-14 · Métricas del día
- Veo emitidos, en espera, atendidos, cancelados, espera promedio y atención promedio.
- Puedo elegir otra fecha; con una fecha inválida veo un aviso y se muestran las de hoy.
- Un día sin turnos muestra un estado vacío.

### HU-15 · Versión 1.0.0
- `README.md` final con instalación paso a paso y usuarios demo.
- Flujo `release/1.0.0` hacia PR a `master`, etiqueta `v1.0.0` y PR de `master` hacia `develop`.
- `docs/incidencias.md` registra los defectos encontrados y cómo se corrigieron.
