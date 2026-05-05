# MojoSharing Plugin - Technical Analysis

> Versión analizada: **7.0.8** (constante `THEME_VERSION` en `newmojo.php`).
> Fecha del análisis: 2026-05-04.
> Repositorio: `wp-content/plugins/mojosharing`.

---

## Overview

MojoSharing (registrado internamente como **"Calendar System"**, autor *WordpressOngoing*) es un plugin a medida de WordPress que implementa un sistema de **propiedad fraccionada (timeshare / co-ownership)**: una propiedad se divide en *N* "shares" (5 u 8) y cada copropietario recibe turnos rotativos para reservar fechas dentro de un calendario anual. Adicionalmente permite poner fechas en alquiler, intercambiarlas con otros copropietarios, y exponer una ficha pública de la propiedad para captar interesados.

El plugin **no usa Custom Post Types**: define su propio esquema con tablas `cs_*` (ver `script.sql`) y se apoya en una arquitectura por capas inspirada en DDD ligero (DTO → Repository → Service → View).

Cabecera del plugin ([newmojo.php](newmojo.php#L1-L8)):

```
Plugin Name: Calendar System
Description: A plugin to enable booking for property co-owners
Version: 1.0
Author: WordpressOngoing
```

---

## Key Features

- **Gestión de propiedades** desde el admin de WP (`Properties` menu): alta, edición, galería, key features, asignación de owners por posición.
- **Gestión de owners** (copropietarios) como entidades propias en la tabla `cs_owners` (no son `wp_users`).
- **Calendario anual por propiedad** (`cs_calendar`) con concepto de *rounds* y *turns* y un orden de prioridad serializado en JSON (`owners_priority`).
- **Reserva de fechas** por turnos, con validación de mínimo de noches y reglas de "todo o deja 3 libres".
- **Marcado de fechas para alquiler** (rental booking) y publicación pública de la propiedad.
- **Solicitudes de intercambio** (exchange requests) entre copropietarios.
- **Operaciones de la propiedad** (mantenimientos, eventos fijos / temporales) con timeline.
- **Notificaciones** internas + plantillas de email editables con TinyMCE.
- **Generación de PDF** del calendario anual usando `dompdf` (carpeta `libs/dompdf`).
- **Cron interno** (`cs_update_calendar_turns_event`) que avanza automáticamente turnos/rondas.
- **Vista pública** de la propiedad via URL "bonita" `/property/{slug}/`.
- **Panel privado** para owners autenticados via sesión PHP.

---

## Architecture

### Estructura de carpetas

```
newmojo.php                Bootstrap del plugin (constantes, requires, enqueues, hooks globales)
script.sql                 Esquema de tablas cs_* (ejecutado en activación)
libs/dompdf/               Librería de generación de PDF
assets/                    CSS / JS / fuentes / imágenes / librerías de terceros
src/
  bases/BaseDto.php        Base para DTOs (mapping a tipos %d/%s de wpdb)
  dtos/                    Data Transfer Objects (Create*Dto, Update*Dto)
  entities/                Modelos de dominio con getters (PropertyEntity, BookingEntity, ...)
  repositories/            Acceso a datos vía $wpdb (CRUD + queries específicas)
  services/                Lógica de negocio + sanitización + orquestación
  views/
    auto/cron.php          Eventos automáticos (rotación de turnos)
    mail/parts.php         Templates de email
    private/               Pantallas de WP-Admin + endpoints AJAX privados
      calendar/            calendarAjax.php (todos los endpoints del panel)
      owners/              CRUD de owners
      properties/          CRUD de propiedades
      propertyoperation/   CRUD de operaciones por propiedad
      settings/            Menú admin, rewrite rules, seasons, plantillas
    public/                Shortcodes y templates de frontend
      login.php            [mojo_login], [mojo_button], AJAX login/logout
      dashboard.php        [mojo_dashboard]
      panel.php            [mojo_panel] (la pantalla de la captura 1)
      notifications.php    [mojo_notifications]
      components.php       Header, helpers, mojo_send_quote, PDF download
      templates/
        single-property.php   Vista pública (la pantalla de la captura 2)
        calendar.php          Render del FullCalendar embebido en panel
        property-shares.php   Listado de owners por share
        operations-timeline.php
        contacts.php
        requests.php
```

### Patrón general (capas)

```
HTTP request
   │
   ▼
View / Shortcode / AJAX handler   ← lee $_GET / $_POST / $_SESSION
   │
   ▼
Service                           ← sanitiza, aplica reglas de negocio
   │
   ▼
DTO (BaseDto → getDataValues + getDataTypes)
   │
   ▼
Repository                        ← $wpdb->insert / prepare / get_results
   │
   ▼
Entity                            ← objeto tipado devuelto a la View
```

`BaseDto` ([src/bases/BaseDto.php](src/bases/BaseDto.php)) expone:
- `getDataValues()`: array filtrado de propiedades no nulas.
- `getDataTypes()`: mapea cada propiedad a `%d` o `%s` para `$wpdb->insert/update`.

Esto permite que los repositorios sean **muy uniformes** (`insert($dto)`, `update($id, $dto)`).

### Arranque (`newmojo.php`)

- Define constantes de URL/path: `MEDIA`, `FONTS`, `CSS`, `JS`, `LIBRARIES`, `MAIL`, `LIBS`.
- `THEME_VERSION = 7.0.8` y `IS_LOCALHOST` (booleano).
- Único `require_once` explícito: [`CalendarService.php`](src/services/CalendarService.php) ([newmojo.php:36](newmojo.php#L36)). El resto se carga vía `require_once` puntuales dentro de cada vista/handler. **No hay autoloader (PSR-4) ni `composer.json`**.
- `register_activation_hook` → `my_plugin_execute_sql_script()`: si no existe `cs_properties`, parte `script.sql` por `;` y ejecuta cada sentencia con `$wpdb->query`. Después llama a `flush_rewrite_rules()` y registra rewrite rules ([newmojo.php:119-161](newmojo.php#L119-L161)).
- Hooks globales: `init` → crear páginas (`create_pages`), arrancar sesión PHP (`mojo_start_session`), `wp_enqueue_scripts`, `admin_enqueue_scripts`, `wp_footer` (inserta inputs ocultos con `admin-ajax`, `home_url`, `MEDIA`).

### Logging

`cs_log($msg, $context=[])` ([newmojo.php:41-53](newmojo.php#L41-L53)) escribe en `error_log` con prefijo `[CalendarSystem]`. Existe también un endpoint AJAX `mojo_panel_jslog` para que el JS pueda enviar errores al log de PHP.

---

## Data Flow

### Caso A — Owner entra al panel: `/panel/?property_id=55&property_share=1`

1. WordPress resuelve `/panel/` como página estándar (creada en `create_pages` durante `init`). El contenido es el shortcode `[mojo_panel]`.
2. `get_panel()` ([src/views/public/panel.php:15+](src/views/public/panel.php)) se ejecuta:
   1. `session_start()` ya fue llamado en `mojo_start_session` (hook `init`).
   2. Lee `$owner_id = intval($_SESSION['mojo_owner_id'])`.
   3. Lee parámetros con `filter_input(INPUT_GET, ..., FILTER_VALIDATE_INT)`:
      - `property_id`
      - `property_share`
      - `period` (año, vía `absint($_GET['period'])`).
   4. **Control de acceso**: si NO es `current_user_can('administrator')` y alguno de los tres valores es `<= 0`, hace `wp_redirect(home_url('dashboard'))`.
3. Carga datos vía servicios:
   - `PropertyService::getProperty($property_id)` → `PropertyEntity`.
   - `OwnerService::getOwnersByProperty($property_id)` → array reindexado por `owner_position`.
   - `PropertyOperationService::getAllProperties($property_id)`.
   - `CalendarService::getCalendarByProperty($property_id, $year)` → `CalendarEntity` con `round`, `turn`, `owners_priority` (JSON).
4. Renderiza pestañas: **Calendar / Property Operation / Share Owners / Exchange Requests**, incluyendo `templates/calendar.php` (FullCalendar) y otras parciales.
5. El JS (`assets/js/public/calendar.js`, cargado como `type="module"`) gestiona la UI; cada acción (book, rent, exchange, delete) llama por `admin-ajax.php` a un handler de [calendarAjax.php](src/views/private/calendar/calendarAjax.php).

### Caso B — Visitante público: `/property/ms1-stupa-hills/`

1. La rewrite rule `^property/([^/]+)/?$ → index.php?cs_property_slug=$matches[1]` ([rewrite_rules.php:11](src/views/private/settings/rewrite_rules.php#L11)) inyecta `cs_property_slug` como `query_var`.
2. En `template_redirect` ([rewrite_rules.php:48](src/views/private/settings/rewrite_rules.php#L48)) `load_custom_property_template()`:
   - Lee `get_query_var('cs_property_slug')`.
   - Si existe: instancia `PropertyService`, llama `getBySlug($slug)` y hace `include single-property.php; exit;`.
   - Si no existe propiedad con ese slug: redirige a 404.
3. `single-property.php` recibe la entidad en `$cpd` y renderiza header, galería (Splide), descripción, key features, lista de shares, timeline de operaciones, contactos y formulario de quote.
4. El submit del quote llama por AJAX a `mojo_send_quote` ([components.php:319-320](src/views/public/components.php#L319-L320)).

### Caso C — Cron de rotación

`init` registra `setup_cron_event()` y el evento `cs_update_calendar_turns_event` que llama a `update_turns_and_rounds()` ([cron.php:198-199](src/views/auto/cron.php#L198-L199)). Esto avanza `cs_calendar.turn` / `cs_calendar.round` para las propiedades cuyo turno ha expirado.

---

## Key Parameters

### `property_id`

- **Origen**: query string (`?property_id=55`) en la URL del panel.
- **Lectura**: `filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT) ?: 0` — [panel.php:24](src/views/public/panel.php#L24).
- **Uso**:
  - Carga la `PropertyEntity` (`PropertyService::getProperty`).
  - Carga owners asociados (`OwnerService::getOwnersByProperty`).
  - Carga el calendario del año actual (`CalendarService::getCalendarByProperty`).
  - Carga operaciones (`PropertyOperationService::getAllProperties`).
- **Validación**: solo de tipo (entero > 0). **No se valida que el owner logueado tenga realmente un share en esta propiedad**.

### `property_share`

- **Origen**: query string (`?property_share=1`).
- **Lectura**: `filter_input(INPUT_GET, 'property_share', FILTER_VALIDATE_INT) ?: 0`.
- **Significado**: posición (1..N) del share dentro de la propiedad. Identifica desde qué share opera el owner cuando posee varios.
- **Uso**:
  - Se inyecta en formularios y data-attributes para que las operaciones AJAX (`book_period`, `confirm_reservation`, `rent_period`, `exchange_dates`) sepan qué `owner_position` están operando.
  - Se muestra en el título del panel: `MS2 Wave SL (Share <?= $property_share ?>)` ([panel.php:236](src/views/public/panel.php#L236)).
- **Validación**: solo entera. **No se valida que el owner sea realmente dueño de ese share concreto** (`cs_owner_property` no se cruza).

### `cs_property_slug` (interno)

- **Origen**: rewrite rule + `query_vars` filter.
- **Uso**: routing de la vista pública vía `template_redirect`.

### `period` / `year`

- **Origen**: `$_GET['period']`.
- **Lectura**: `absint($_GET['period'])` con fallback al año actual ([panel.php:52](src/views/public/panel.php#L52)).
- **Uso**: selecciona el calendario anual a mostrar.

---

## Hooks & Integrations

### Custom Post Types / Taxonomías

**Ninguno.** El plugin opera enteramente con tablas `cs_*`.

### Shortcodes

| Shortcode | Callback | Archivo |
|---|---|---|
| `[mojo_panel]` | `get_panel()` | [src/views/public/panel.php](src/views/public/panel.php#L342) |
| `[mojo_login]` | `get_owners_login()` | [src/views/public/login.php](src/views/public/login.php#L40) |
| `[mojo_button]` | `get_button_login()` | [src/views/public/login.php](src/views/public/login.php#L62) |
| `[mojo_dashboard]` | `get_dashboard()` | [src/views/public/dashboard.php](src/views/public/dashboard.php#L275) |
| `[mojo_notifications]` | `get_notifications()` | [src/views/public/notifications.php](src/views/public/notifications.php#L36) |

### `add_action`

| Hook | Archivo |
|---|---|
| `init` → `create_pages`, `mojo_start_session` | [newmojo.php](newmojo.php#L300) |
| `init` → `custom_properties_rewrite_rules` | [rewrite_rules.php](src/views/private/settings/rewrite_rules.php#L11) |
| `init` → `setup_cron_event` | [cron.php](src/views/auto/cron.php#L198) |
| `template_redirect` → `load_custom_property_template` | [rewrite_rules.php](src/views/private/settings/rewrite_rules.php#L48) |
| `cs_update_calendar_turns_event` → `update_turns_and_rounds` | [cron.php](src/views/auto/cron.php#L199) |
| `wp_enqueue_scripts`, `admin_enqueue_scripts`, `wp_footer` | [newmojo.php](newmojo.php#L167) |
| `admin_menu` → `general_admin_menu`, `property_admin_menu`, `owner_admin_menu` | settings / properties / owners admin |
| `admin_init` → `custom_notifications_editor_buttons` | [src/views/private/settings/notifications/both.php](src/views/private/settings/notifications/both.php#L3) |
| `admin_notices` → `cs_recommend_wp_crontrol` | [newmojo.php](newmojo.php#L99) |

**Endpoints AJAX** (todos registran `wp_ajax_` y `wp_ajax_nopriv_` salvo donde se indique):

| Acción | Origen |
|---|---|
| `mojo_panel_jslog` | [newmojo.php:60-61](newmojo.php#L60) |
| `mojo_login_owner`, `mojo_logout` | [login.php](src/views/public/login.php#L65) |
| `mojo_send_quote`, `load_more_notifications`, `mojo_download_booking_calendar` | [components.php](src/views/public/components.php#L319) |
| `mojo_is_allowed` | [calendarHelpers.php](src/views/private/calendar/calendarHelpers.php#L250) |
| `mojo_panel_book_period`, `mojo_panel_confirm_reservation`, `mojo_panel_delete_booked_date` | [calendarAjax.php](src/views/private/calendar/calendarAjax.php) |
| `mojo_panel_exchange_dates`, `mojo_panel_save_exchange_request_pre_validation`, `mojo_panel_save_exchange_request`, `mojo_panel_change_request_status` | [calendarAjax.php](src/views/private/calendar/calendarAjax.php) |
| `mojo_panel_rent_period`, `mojo_panel_request_period`, `mojo_panel_remove_period` | [calendarAjax.php](src/views/private/calendar/calendarAjax.php) |
| `save_or_update_season`, `remove_season` (solo `wp_ajax_`) | [seasons.php](src/views/private/settings/seasons.php#L102) |
| `save_property_operation`, `delete_property_operation` (solo `wp_ajax_`) | [propertyoperation.php](src/views/private/propertyoperation/propertyoperation.php#L37) |

### `add_filter`

| Hook | Archivo |
|---|---|
| `script_loader_tag` → `add_module_to_my_script` (convierte ciertos `<script>` a `type="module"`) | [newmojo.php:255](newmojo.php#L255) |
| `query_vars` → `custom_properties_query_vars` (registra `cs_property_slug`) | [rewrite_rules.php:19](src/views/private/settings/rewrite_rules.php#L19) |
| `set-screen-option` (paginación admin tables) | properties_admin / owners_admin |
| `mce_external_plugins`, `mce_buttons` (botón de placeholders en TinyMCE) | [both.php:8-9](src/views/private/settings/notifications/both.php#L8) |

### REST API

**Sin endpoints**. No hay llamadas a `register_rest_route`.

### Rewrite rules

Solo una regla custom:

```php
add_rewrite_rule('^property/([^/]+)/?$', 'index.php?cs_property_slug=$matches[1]', 'top');
```

`/panel/`, `/dashboard/`, `/login/`, `/notifications/` son **páginas WP normales** creadas programáticamente en `create_pages()` con el shortcode correspondiente como contenido.

---

## Rendering Logic

### Panel (privado) — `[mojo_panel]`

1. `get_panel()` valida sesión + parámetros.
2. Carga `PropertyEntity`, owners, calendario, operaciones.
3. Renderiza HTML directamente con `echo` y `<?php ?>`, escapando con `esc_html` / `esc_attr` / `esc_url` / `esc_js`.
4. Incluye `templates/calendar.php` que monta el `<div id="calendar">`. El JS (`assets/js/public/calendar.js`) instancia FullCalendar con eventos pintados desde un array PHP serializado a JS.
5. Las pestañas (Calendar / Property Operation / Share Owners / Exchange Requests) se conmutan client-side con `assets/js/public/tabs.js`.

### Dashboard — `[mojo_dashboard]`

- `get_dashboard()` exige sesión; carga `OwnerService::getPropertiesInRelation($owner_id)` y renderiza tarjetas con info de cada propiedad/share más una bandera `is_your_turn` calculada por `CalendarService::validateIfIsYourTurn`.

### Vista pública — `single-property.php`

- Disparada por `template_redirect`, fuera de la jerarquía estándar de plantillas de WP.
- Recibe `$cpd` (PropertyEntity) y construye:
  - Hero con galería (Splide).
  - Descripción HTML (`echo` directo, asume `wp_kses_post` previo en alta).
  - Key features (decodifica JSON guardado en `cs_properties.key_features`).
  - Botón "Rental Calendar {YEAR}" → genera PDF vía `mojo_download_booking_calendar` (dompdf).
  - Formulario "Request for quote" con DateRangePicker → AJAX `mojo_send_quote`.

### Emails

- Plantillas en `cs_templates` (`subject`, `body`, flags `email_enabled`, `push_enabled`).
- Wrapping HTML en [src/views/mail/parts.php](src/views/mail/parts.php) con header/footer y reemplazo de placeholders `{owner_name}`, `{property}`, `{dates}`, etc.

---

## Database Schema (resumen `script.sql`)

| Tabla | Columnas clave | Notas |
|---|---|---|
| `cs_properties` | `id`, `name`, `description`, `thumbnail`, `code`, `share_qty`, `slug`, `gallery`, `key_features` (JSON), `is_active`, `show_shares`, `rental_booking_page` | Catálogo de propiedades |
| `cs_owners` | `id`, `name`, `email`, `password`, `phone`, `visible_info`, `is_active` | ⚠️ `password` en texto plano |
| `cs_owner_property` | `property_id`, `owner_id`, `owner_position` | Pivot N:M con la posición del share. Cascade delete |
| `cs_calendar` | `id`, `property_id`, `year`, `owners_priority` (JSON), `round`, `turn`, `status`, `colors_order` | Una fila por propiedad/año |
| `cs_booking` | `id`, `calendar_id`, `start_date`, `end_date`, `owner_id`, `owner_position`, `in_round`, `type` | Reservas (uso propio o rental). Cascade delete con calendar |
| `cs_property_operation` | `property_id`, `operation_date`, `title`, `description`, `type` (fixed/temporary) | Mantenimientos / eventos |
| `cs_seasons` | `date`, `type` (high/middle/low/14-day), `year` | Calendario de temporadas para pricing |
| `cs_comments` | `calendar_id`, `title`, `description`, `date` | Notas internas en el calendario |
| `cs_notifications` | `owner_id`, `notification`, `datetime` | Bandeja de notificaciones |
| `cs_templates` | `subject`, `body`, `email_enabled`, `message`, `push_enabled` | Plantillas email/push |
| `cs_contacts` | Datos administrativos, redes sociales, footer, color PDF | Singleton de contacto/branding |

### Modelo conceptual (rounds & turns)

```
cs_calendar.owners_priority = [3, 1, 5, 2, 4, ...]   ← orden de turno
cs_calendar.round           = 0..(qty_rounds-1)      ← ciclo del año
cs_calendar.turn            = 0..(qty_shares-1)      ← posición dentro del ciclo
qty_rounds = (share_qty == 8) ? 5 : 6
```

`get_current_share_of_current_owner()` ([calendarHelpers.php:55-75](src/views/private/calendar/calendarHelpers.php#L55)) calcula qué share puede reservar en un round/turn dado.

---

## Improvements

### Seguridad (prioridad alta)

1. **Falta de nonces / CSRF**: ningún handler AJAX usa `check_ajax_referer()` ni `wp_verify_nonce()`. Todos los endpoints `wp_ajax_nopriv_*` que mutan datos (book, confirm, rent, exchange, change_request_status, save/delete season, save/delete property_operation) son vulnerables a CSRF. → Añadir `wp_create_nonce('mojo_panel')` en el frontend y `check_ajax_referer('mojo_panel', '_wpnonce')` en cada handler.
2. **Contraseñas en texto plano** en `cs_owners.password` ([script.sql](script.sql#L80)). Migrar a `wp_hash_password()` + `wp_check_password()` (o `password_hash`/`password_verify`).
3. **Login sin sanitización ni rate-limit**: [login.php:72-78](src/views/public/login.php#L72) lee `$_POST['email']` y `$_POST['password']` crudos. Al menos: `sanitize_email`, `is_email`, intento limitado por IP, y comparación con hash.
4. **Falta de comprobación de propiedad** sobre `property_id` / `property_share`: el panel sólo valida que sean enteros > 0 y que haya sesión. Un owner válido puede acceder a `?property_id=99&property_share=3` aunque no sea suyo. → Verificar contra `cs_owner_property` que `(owner_id, property_id, owner_position)` exista.
5. **Endpoints `wp_ajax_nopriv_*`** registrados para acciones del panel (book, exchange, etc.) sin que sean realmente públicas. Sustituir por `wp_ajax_*` y forzar autenticación de owner desde sesión, **comprobada en el handler** (no asumida).
6. **JSON sin validar** desde `$_POST` en [calendarAjax.php:57](src/views/private/calendar/calendarAjax.php#L57) (`stripslashes` + `json_decode`). Validar estructura y tipos del array decodificado.
7. **Sesiones PHP sin endurecer**: no hay rotación de session id tras login (`session_regenerate_id(true)`), ni timeout, ni `Secure`/`HttpOnly`/`SameSite` documentados.

### Arquitectura

8. **Sin autoloader**: introducir `composer.json` con PSR-4 sobre `src/` y eliminar los `require_once` repartidos por las vistas.
9. **Lógica de negocio dentro de vistas/shortcodes**: `get_panel()`, `get_dashboard()` ([panel.php:64-100](src/views/public/panel.php#L64), [dashboard.php](src/views/public/dashboard.php)) hacen orquestación de servicios, reindexado de arrays y preparación de datos. → Extraer un Controller / Presenter por pantalla y dejar la vista solo con render.
10. **Acoplamiento a superglobales**: `$_GET` / `$_POST` / `$_SESSION` se leen directamente en docenas de archivos. → Encapsular en una clase `Request` (o usar `WP_REST_Request`) y un `SessionAuth` para owners.
11. **Routing público fuera de la jerarquía**: `single-property.php` se carga con `include + exit` desde `template_redirect`. → Migrar a un CPT `cs_property` con `single-cs_property.php` o registrar una `WP_REST_Route` para datos + página WP para el chrome.
12. **Mezcla de `wp_ajax_` y `wp_ajax_nopriv_`** sin diferenciar la lógica para invitados vs autenticados.
13. **`script.sql` parseado por `;`**: frágil ante triggers o procedures. Usar `dbDelta()` por tabla.
14. **Ausencia de tests** y sin `composer.json` / linter / CI.
15. **Versionado inconsistente**: cabecera dice `Version: 1.0` pero `THEME_VERSION = 7.0.8`. Unificar.

### Frontend

16. JS cargado con `?r=' . time()` ([newmojo.php:177](newmojo.php#L177)) rompe caché en cada request — usar `THEME_VERSION` o un hash de build.
17. Hardcoded de selectores y data en HTML inline (`<input type="hidden" id="mojo-uri">`) → migrar a `wp_localize_script`/`wp_add_inline_script`.

### Documentación / mantenimiento

18. Añadir `readme.txt` estándar de WordPress y un `CHANGELOG.md`.
19. Documentar el flujo de `rounds`/`turns` con diagramas (es la pieza menos obvia del dominio).
20. Incluir scripts de migración para hashear contraseñas existentes una vez se introduzca `wp_hash_password`.

---

## Diagrama de flujo (texto)

```
+----------------------+        +------------------------+
| Browser (Owner)      |        | Browser (Visitor)      |
|  /panel/?property_id |        |  /property/{slug}/     |
+----------+-----------+        +-----------+------------+
           |                                |
           | shortcode [mojo_panel]         | rewrite rule
           v                                v
+----------+-----------+        +-----------+------------+
| get_panel()          |        | template_redirect      |
| - session check      |        | load_custom_property_  |
| - filter_input ints  |        | template()             |
+----------+-----------+        +-----------+------------+
           |                                |
           v                                v
+----------+-----------+        +-----------+------------+
| Services             |        | PropertyService        |
|  PropertyService     |        |  ::getBySlug($slug)    |
|  OwnerService        |        +-----------+------------+
|  CalendarService     |                    |
|  PropertyOperation   |                    v
+----------+-----------+        +-----------+------------+
           |                    | single-property.php    |
           v                    | (echo HTML + Splide +  |
+----------+-----------+        |  quote form -> AJAX)   |
| Repositories ($wpdb) |        +------------------------+
+----------+-----------+
           |
           v
+----------+-----------+
| MySQL  cs_*          |
+----------------------+

Frontend del panel  --AJAX-->  admin-ajax.php  -->  calendarAjax.php
                                                      (book / rent / exchange / delete)
```
