# Glossary — Variables, funciones y conceptos del plugin

> Diccionario vivo. Cada vez que encontremos una variable, función, hook o concepto cuyo significado no sea obvio, lo añadimos aquí con: **qué es**, **dónde se calcula**, **dónde se usa** y **cuándo es `true`/`false`** (si es booleano).

Mantener orden alfabético dentro de cada sección.

---

## 1. Variables del Panel (`[mojo_panel]`)

Estas tres viven en [src/views/public/panel.php](src/views/public/panel.php) (función `get_panel()`) y se pasan al template [src/views/public/templates/calendar.php](src/views/public/templates/calendar.php). Controlan **qué puede hacer el owner logueado en este momento**.

### `$is_turn_of_the_owner` *(bool)*

- **Qué responde**: ¿es **ahora mismo** el turno del owner logueado en este calendario?
- **Cálculo** ([panel.php:122](src/views/public/panel.php#L122)):
  ```php
  $is_turn_of_the_owner = $serviceC->validateIfIsYourTurn($calendar_id, $owner_id, $owner_position);
  ```
- **Implementación** ([CalendarService::validateIfIsYourTurn](src/services/CalendarService.php#L140)):
  ```php
  $turn          = $calendar->getTurn();                      // entero, posición actual
  $owners_order  = json_decode($calendar->getOwnersPriority()); // ej. [3, 1, 5, 2, 4, ...]
  return isset($owners_order[$turn]) && $owners_order[$turn] == $id_owner;
  ```
  Es decir: mira el array `owners_priority` del calendario en la posición `turn`. Si el owner_id que está ahí coincide con el owner logueado → `true`.
- **`true` cuando**: el JSON `cs_calendar.owners_priority[ cs_calendar.turn ]` es igual a `$_SESSION['mojo_owner_id']`.
- **`false` cuando**: el calendario no existe, no tiene `owners_priority`, o el turno actual pertenece a otro owner.
- **Para qué se usa**: habilita o no las acciones de **booking** (Book / Confirm Dates) y, junto con `$is_share_of_the_owner` y `status='open'`, decide si se muestra el aside con `SELECTED NIGHTS` y `BOOKED DATES`.

### `$is_share_of_the_owner` *(bool)*

- **Qué responde**: el owner logueado puede tener **varios** shares (Maria tiene el 1 y el 2 en MS2). Esta variable dice: el share **concreto** que está mirando ahora (`property_share` de la URL) ¿coincide con el share que le toca a este owner en el turno actual?
- **Cálculo** ([panel.php:124-125](src/views/public/panel.php#L124-L125)):
  ```php
  $current_share         = get_current_share_of_current_owner($qty_shares, $owners_by_position, $order_owners_4_calendar, $round, $turn);
  $is_share_of_the_owner = $current_share == $property_share;
  ```
- **Implementación** ([calendarHelpers.php:48](src/views/private/calendar/calendarHelpers.php#L48)): `get_current_share_of_current_owner()` recorre las rondas/turnos siguiendo el patrón "ida y vuelta" (las rondas pares van en orden inverso, `range($qty_shares, 1)`) y devuelve **qué número de share** le toca consumir al owner del turno actual.
- **`true` cuando**: el `property_share` de la URL es exactamente el share que le toca al owner en este round/turn.
- **`false` cuando**:
  - Maria entra con `?property_share=1` pero ahora le toca consumir su share `2` (o viceversa).
  - El owner del turno actual no es el logueado (en cuyo caso normalmente también `is_turn_of_the_owner=false`).
- **Para qué se usa**: evita que un owner con varios shares opere desde el share equivocado. Es la pareja inseparable de `is_turn_of_the_owner` (si tienes turno pero estás mirando el share que no toca, no puedes reservar).

### `$calendar->getStatus()` *(string: `'open'` | `'close'`)*

- **Qué responde**: ¿el calendario admite operaciones (booking/exchange) o está cerrado?
- **Origen**: columna `cs_calendar.status` ([script.sql:57](script.sql#L57)) — `varchar(255) DEFAULT NULL`.
- **Valores observados en código**:
  - `'open'` — operativo.
  - `'close'` — cerrado (lo asigna [`CalendarRepository::reset`](src/repositories/CalendarRepository.php#L102) cuando se reinicia el calendario para un nuevo año).
- **Quién lo cambia**: `CalendarRepository::reset()` lo pone a `'close'`. La apertura (`'open'`) se hace desde el admin (gestión de calendarios en `wp-admin`). El cron `cs_update_calendar_turns_event` ([cron.php](src/views/auto/cron.php)) solo procesa los que ya están `'open'`.
- **Para qué se usa**: gate global. Si el calendario está cerrado, ni reservas, ni intercambios, ni acciones de turno son posibles.

---

## 2. Combinaciones que aparecen en `calendar.php`

### `($calendar->getStatus() == 'open' && $is_turn_of_the_owner && $is_share_of_the_owner)`

> **"Soy el dueño activo en este momento del calendario abierto, mirando mi share correcto."**

Aparece en:
- [calendar.php:31](src/views/public/templates/calendar.php#L31) — muestra `SELECTED NIGHTS` + `BOOKED DATES`.
- [calendar.php:111](src/views/public/templates/calendar.php#L111) — muestra botón `Book`.
- [calendar.php:117](src/views/public/templates/calendar.php#L117) — muestra botón `Confirm Dates`.
- [panel.php:300](src/views/public/panel.php#L300) — control adicional por días seleccionados.

### `(!$is_turn_of_the_owner || !$is_share_of_the_owner || $calendar->getStatus() != 'open')`

> **"NO es mi turno O estoy en el share equivocado O el calendario está cerrado."**
> = exactamente la negación de la combinación de arriba.

Aparece en:
- [calendar.php:125](src/views/public/templates/calendar.php#L125) — muestra `mojo_panel--actions` (botonera `Up for Rental` / `Cancel Rental` / `Request for personal use` / `Exchange dates` / `Cancel Exchange`).

### Conclusión clave para Purchase

La botonera `mojo_panel--actions` **solo aparece cuando NO es tu turno** o cuando estás viendo un share que no es el activo. Es el "modo espectador / acción sobre el pasado". Por eso:

- ✅ `Up for Rental` y `Exchange dates` viven ahí: son acciones sobre **bookings ya confirmados** que se pueden ejecutar en cualquier momento, incluso si no estás reservando.
- ❓ `Buy Rental Dates` también encaja semánticamente ahí: comprar a otro share es una acción que NO depende de tu turno propio. **PERO** esta condición además exige que el calendario sea de un share *no activo* — lo cual no siempre será el caso de un comprador. Hay que decidir si se quiere visible siempre o solo cuando "no es tu momento".

---

## 3. Otros nombres que ya hemos visto

(Vacío de momento — añadir aquí variables/funciones cuando aparezcan.)

<!-- Plantilla:
### `$nombre` *(tipo)*
- **Qué responde**:
- **Cálculo** ([archivo:linea](archivo#Llinea)):
- **`true` / valores cuando**:
- **Para qué se usa**:
-->
