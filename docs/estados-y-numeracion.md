# Estados y reglas de numeración · FilaCero

## 1. Reglas de numeración de folios

### 1.1 Formato del folio

Cada turno recibe un folio con el formato `PREFIJO-NNN`:

- `PREFIJO` es el prefijo del servicio (2 a 4 letras, único en la tabla `servicios`).
- `NNN` es el consecutivo con tres dígitos y ceros a la izquierda.

Se arma en `emitir_turno()` (`src/turnos.php`) con:

```php
$folio = sprintf('%s-%03d', $servicio['prefijo'], $consecutivo); // ASE-001
```

### 1.2 Serie = servicio + día

- Cada servicio lleva **su propia serie**: Asesoría y Constancias cuentan por separado.
- Cada día la serie **vuelve a empezar en 001**.

| Momento | Servicio | Folio |
|---|---|---|
| Lunes, 1.er turno | Asesoría (`ASE`) | `ASE-001` |
| Lunes, 2.º turno | Asesoría (`ASE`) | `ASE-002` |
| Lunes, 1.er turno | Constancias (`CON`) | `CON-001` |
| Martes, 1.er turno | Asesoría (`ASE`) | `ASE-001` |

### 1.3 Cálculo del consecutivo

El siguiente número se calcula con el mayor consecutivo de la serie más uno.
Si la serie todavía no tiene turnos, `COALESCE` regresa 0 y el primero es 1.

```sql
SELECT COALESCE(MAX(consecutivo), 0) + 1
  FROM turnos
 WHERE servicio_id = :servicio_id AND fecha = :fecha
```

La fecha y la hora se generan en PHP con la zona horaria `America/Mexico_City`.
El turno se guarda en estado `pendiente` junto con `fecha`, `consecutivo`, `folio` y `creado_en`.

`emitir_turno($servicioId, $momento)` acepta un `$momento` opcional (`'Y-m-d H:i:s'`).
Si no se manda, se usa la hora actual. Sirve para probar el reinicio de la serie al día siguiente.

### 1.4 Defensas contra folios duplicados

| Defensa | Dónde | Estado |
|---|---|---|
| `UNIQUE(servicio_id, fecha, consecutivo)` y `UNIQUE(fecha, folio)`: la base rechaza cualquier duplicado | `database/schema.sql` | Implementada (HU-01) |
| Lectura del `MAX` e `INSERT` dentro de una transacción `BEGIN IMMEDIATE` con `with_write_lock()`, para que dos personas que piden turno al mismo tiempo no lean el mismo número | `src/db.php` · `src/turnos.php` | Implementada (HU-07) |
| Si el folio calculado ya existe ese día (por ejemplo, porque un servicio tomó el prefijo que antes tenía otro), se muestra "No se pudo generar el folio … porque ya se usó hoy" y no se guarda nada | `src/turnos.php` | Implementada (HU-07) |

Con `BEGIN IMMEDIATE`, la segunda petición espera (hasta 3 s, `busy_timeout`) a que la primera haga `COMMIT`.
Si algo falla dentro del bloque, se hace `ROLLBACK` y no queda ningún turno a medias.

**Evidencia:** 5 procesos emitiendo 20 turnos cada uno al mismo tiempo del mismo servicio generaron 100 folios distintos, consecutivos del 1 al 100, sin errores.

### 1.5 Servicios que no emiten turnos

Los errores que ve el visitante se lanzan con `ReglaNegocioException` y el kiosco los muestra en rojo.

| Caso | Resultado | Estado |
|---|---|---|
| Servicio inexistente | Mensaje "El servicio no existe." en el kiosco | Implementada (HU-03 · HU-07) |
| Servicio inactivo (por ejemplo, desactivado con el kiosco ya abierto) | Mensaje "Ese servicio no está disponible en este momento." y no se guarda el turno | Implementada (HU-07) |

## 2. Diagrama de estados de un turno

Un turno pasa por distintos estados desde que el visitante lo solicita en el kiosco hasta que concluye su atención o es cancelado:

```mermaid
stateDiagram-v2
    [*] --> pendiente: visitante toma turno
    pendiente --> llamado: "Llamar siguiente" (el más antiguo)
    pendiente --> cancelado
    llamado --> en_atencion: "Iniciar atención"
    llamado --> cancelado: no se presentó
    en_atencion --> atendido: "Marcar atendido"
    en_atencion --> cancelado
    atendido --> [*]
    cancelado --> [*]
```

### 2.1 Reglas de transición

- "Llamar siguiente" solo toma turnos en estado `pendiente` (el más antiguo registrado), nunca un turno `cancelado` o `atendido`. 
- Si la cola está vacía, el sistema muestra un aviso informativo sin generar error.
- Un operador no puede llamar otro turno si ya tiene uno en estado `llamado` o `en_atencion`.
- Un operador no puede modificar el turno asignado a otro operador (el administrador solo puede cancelarlo).
- Si el turno no existe, el sistema responde "El turno no existe".
- `atendido` y `cancelado` son estados finales; no admiten cambios posteriores.
- Cada cambio de estado queda registrado en la bitácora (`atenciones`) indicando quién lo realizó, cuándo y la transición efectuada.
