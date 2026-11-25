# Pruebas · FilaCero

La suite automática se ejecuta con:

```bash
php tests/smoke.php
```

`tests/smoke.php` crea una base SQLite temporal mediante la variable de entorno `FILACERO_DB_PATH` y la elimina al terminar, por lo que **nunca modifica** la base local de demostración (`storage/filacero.sqlite`).

## 1. Pruebas automáticas (`tests/smoke.php`)

Ejecución verificada sobre base temporal aislada (`7/7 pruebas OK`, código de salida `0`):

| ID | Caso | Tipo | Estado | Notas |
|---|---|---|---|---|
| `T-01` | Emitir turno genera `ASE-001` en `pendiente` | Positiva | Aprobada | Verifica folio `ASE-001`, consecutivo `1` y consulta `find_turno()` |
| `T-03` | Cada servicio tiene su propia serie | Positiva | Aprobada | Verifica `ASE-002`, `CON-001` y orden de llegada en `cola_pendiente()` |
| `T-06` | No emite turno de servicio inactivo o inexistente | Negativa | Aprobada | Rechaza servicio inexistente (`99999`) y servicio desactivado (`INS`) |
| `T-07` | Llamar siguiente respeta el orden de llegada | Positiva | Aprobada | Llama `ASE-001` y `ASE-002` en orden; impide doble turno activo por operador |
| `T-08` | Flujo `llamado` -> `en_atencion` -> `atendido` y bitácora | Positiva | Aprobada | Rechaza salto directo `llamado` -> `atendido` y valida 3 registros en `atenciones` |
| `T-11` | Operar un turno inexistente se rechaza | Negativa | Aprobada | `cambiar_estado(99999, ...)` lanza `ReglaNegocioException` |
| `T-15` | Prefijo repetido o inválido se rechaza | Negativa | Aprobada | Verifica `validate_servicio()` y restricciones `UNIQUE`/`CHECK` en SQLite |

## 2. Pruebas manuales (Semana 2)

Verificadas sobre servidor local PHP con base SQLite temporal sembrada (`scripts/init-db.php --demo`):

| ID | Acción | Resultado esperado | Resultado obtenido | Evidencia | Estado |
|---|---|---|---|---|---|
| `M-01` | Pedir turno en el kiosco (`POST /index.php`) y recargar `ticket.php` | El ticket conserva el mismo folio | Emitió `ASE-003` en `/ticket.php?id=7` y mantuvo el folio tras recargar | [`docs/bocetos/ticket-escritorio.png`](bocetos/ticket-escritorio.png) | Aprobada |
| `M-02` | Como `admin`, crear el servicio `TIT` ("Titulación") en `servicios.php` | Aparece en el kiosco | `TIT` creado vía `POST /servicios.php` y mostrado en `/index.php` | [`docs/bocetos/prueba-m02-kiosco-tit.png`](bocetos/prueba-m02-kiosco-tit.png) | Aprobada |
| `M-03` | Llamar -> iniciar atención -> marcar atendido desde `panel.php` | La pantalla pública (`pantalla.php`) cambia sola | `/pantalla-estado.php` reflejó `ASE-001` en `Ventanilla 1` al llamar y volvió a `Sin llamados por ahora` al marcar atendido | [`docs/bocetos/prueba-m03-pantalla-vivo.png`](bocetos/prueba-m03-pantalla-vivo.png) | Aprobada |
| `M-04` (Negativa) | Intentar salto de estado no permitido (`llamado` -> `atendido` vía `POST /turno-accion.php`) | Mensaje de error, el estado no cambia | Mostró `"Transición no permitida: no se puede pasar de 'llamado' a 'atendido'."` y `ASE-001` permaneció en `llamado` | Respuesta HTTP y `/pantalla-estado.php` verificados | Aprobada |
| `M-05` (Negativa) | Iniciar sesión en `login.php` con contraseña incorrecta *(bloqueo de 60 s tras 5 intentos en HU-11, Semana 3)* | Muestra `Usuario o contraseña incorrectos` y bloquea acceso a `panel.php` | Mostró el error genérico y `/panel.php` redirigió al login | [`docs/bocetos/login-error.png`](bocetos/login-error.png) | Aprobada |
