# Pruebas · FilaCero

La suite automática se ejecuta con:

```bash
php tests/smoke.php
```

`tests/smoke.php` crea una base SQLite temporal mediante la variable de entorno `FILACERO_DB_PATH` y la elimina al terminar, por lo que **nunca modifica** la base local de demostración (`storage/filacero.sqlite`).

## 1. Pruebas automáticas (`tests/smoke.php`)

| ID | Caso | Tipo | Estado | Notas |
|---|---|---|---|---|
| `T-01` | Emitir turno genera `ASE-001` en `pendiente` | Positiva | Aprobada | Ejecutada en `tests/smoke.php` |
| `T-03` | Cada servicio tiene su propia serie | Positiva | Aprobada | Ejecutada en `tests/smoke.php` |
| `T-06` | No emite turno de servicio inactivo o inexistente | Negativa | Parcial (inexistente aprobada) | Caso servicio inactivo se activa al integrar HU-07 |
| `T-07` | Llamar siguiente respeta el orden de llegada | Positiva | Pendiente (HU-08) | Requiere `llamar_siguiente()` en `src/atencion.php` |
| `T-08` | Flujo `llamado` -> `en_atencion` -> `atendido` y bitácora | Positiva | Pendiente (HU-08) | Requiere `cambiar_estado()` y bitácora `atenciones` |
| `T-11` | Operar un turno inexistente se rechaza | Negativa | Pendiente (HU-08) | Requiere `cambiar_estado()` para turno inexistente |
| `T-15` | Prefijo repetido o inválido se rechaza | Negativa | Parcial (restricciones BD aprobadas) | Se amplía con `validate_servicio()` al integrar HU-06 |

## 2. Pruebas manuales (Semana 2)

| ID | Acción | Resultado esperado | Resultado obtenido | Evidencia | Estado |
|---|---|---|---|---|---|
| `M-01` | Pedir turno en el kiosco (`http://127.0.0.1:8000`) y recargar `ticket.php` | El ticket conserva el mismo folio | Folio persistido en SQLite y conservado al recargar | [`docs/bocetos/ticket-escritorio.png`](bocetos/ticket-escritorio.png) | Aprobada |
| `M-02` | Como `admin`, crear el servicio `TIT` ("Titulación") en `servicios.php` | Aparece en el kiosco | Pendiente de integración de `HU-06` (`feature/hu06-servicios`) | Pendiente captura | Pendiente |
| `M-03` | Llamar -> iniciar atención -> marcar atendido desde `panel.php` | La pantalla pública (`pantalla.php`) cambia sola | Pendiente de integración de `HU-08` y `HU-09` | Pendiente captura | Pendiente |
| `M-04` (Negativa) | Intentar un salto de estado no permitido (ej. `llamado` a `atendido` o regresar `atendido`) | Mensaje de error, el estado no cambia | Pendiente de integración de `HU-08` (`src/atencion.php`) | Pendiente captura | Pendiente |
| `M-05` (Negativa) | Iniciar sesión en `login.php` con contraseña incorrecta *(bloqueo de 60 s tras 5 intentos en HU-11, Semana 3)* | Muestra `Usuario o contraseña incorrectos` sin revelar qué campo falló | Se mostró el mensaje genérico de error sin iniciar sesión | [`docs/bocetos/login-error.png`](bocetos/login-error.png) | Aprobada (S2) |
