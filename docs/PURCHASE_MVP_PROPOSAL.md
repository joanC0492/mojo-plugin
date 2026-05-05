# Purchase Feature — MVP Proposal

> Propuesta técnica para implementar el sistema de **compra de semanas entre propietarios** sobre el plugin `mojosharing`.
> Fecha: 2026-05-04.
> Basado en el análisis de [plugin-mojosharing-analysis.md](plugin-mojosharing-analysis.md).

---

## 1. ✅ Evaluación: ¿se puede construir el MVP con lo actual?

**Sí, con lo que hay alcanza para un MVP usable.** El plugin ya tiene casi todas las piezas: tabla de bookings con rangos arbitrarios de fechas, sistema de owners + sesiones, calendario por propiedad, plantillas de email, panel admin con menús propios, y una pantalla "Other Sharing Properties" en el dashboard que es exactamente el lugar donde encaja el flujo de compra.

### Validación previa (lo importante antes de codear)

| Suposición | Validación en código | Veredicto |
|---|---|---|
| "MS" significa "Propiedades" | Buscando `MS\d` en el plugin no aparece como campo, **solo como prefijo del nombre/código**: `cs_properties.name` ("MS2 Wave SL", "MS3-Majesty Palms SL") y posiblemente `cs_properties.code`. No hay agrupación por "MS group". | **"MS" = prefijo de nombre de propiedad**. Cada `MS*` es una propiedad distinta en `cs_properties`. No hace falta tabla nueva para esto. |
| Reservas en días dinámicos | `cs_booking.start_date` / `end_date` son strings de fecha libre. La UI ya usa DateRangePicker. La regla de "todo o deja 3 libres" está hardcoded en JS, pero la BD acepta cualquier rango. | **Soportado**. Solo hay que ajustar la regla en `BookingService` y la UI. |
| Pagos externos | No hay WooCommerce ni gateway. Solo `wp_mail` para cotizaciones. | **OK, sin impacto**. |
| Aprobación manual desde admin | Existe el patrón en exchange requests (`mojo_panel_change_request_status`). Reutilizable. | **OK, copiar el patrón**. |
| Owners viven en `cs_owners` (no en `wp_users`) | Confirmado: no hay `wp_insert_user` en el plugin. Los usuarios visibles en `/wp-admin/users.php` (Clara, Cristina) son **usuarios admin** del sitio, no copropietarios. | **Importante**: la notificación a "admins" debe ir a `wp_users` con role `administrator`, no a `cs_owners`. |
| Existe lógica de sharing | Sí: `cs_owner_property` (pivot owner↔property con `owner_position`), `cs_calendar` con turnos/rondas, `cs_booking` con `type ENUM('for rent','for personal use')`. | **OK**. La compra reutiliza `type='for rent'` como inventario disponible. |

---

## 2. 🧠 Propuesta técnica

### Decisión arquitectónica

**Tabla custom (NO Custom Post Type)**, por coherencia con el resto del plugin (ya usa `cs_*`). Un CPT introduciría doble paradigma de datos y rompería el patrón DTO→Repository→Service→View.

### Nuevas tablas

```sql
CREATE TABLE cs_purchase_requests (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  buyer_owner_id  INT NOT NULL,           -- comprador (cs_owners.id)
  seller_owner_id INT NOT NULL,           -- vendedor (cs_owners.id)
  property_id     INT NOT NULL,           -- cs_properties.id
  booking_id      INT NULL,               -- cs_booking.id que se quiere comprar (NULL si rango libre)
  start_date      DATE NOT NULL,
  end_date        DATE NOT NULL,
  nights          INT NOT NULL,           -- cacheado para reportes
  price_total     DECIMAL(10,2) NULL,     -- snapshot del precio
  currency        VARCHAR(3) DEFAULT 'EUR',
  status          ENUM('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
  buyer_message   TEXT NULL,
  admin_note      TEXT NULL,              -- notas internas del admin que la procesa
  processed_by    BIGINT UNSIGNED NULL,   -- wp_users.ID del admin que aprobó
  processed_at    DATETIME NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY (buyer_owner_id),
  KEY (seller_owner_id),
  KEY (property_id),
  KEY (status),
  CONSTRAINT fk_pr_buyer    FOREIGN KEY (buyer_owner_id)  REFERENCES cs_owners(id) ON DELETE CASCADE,
  CONSTRAINT fk_pr_seller   FOREIGN KEY (seller_owner_id) REFERENCES cs_owners(id) ON DELETE CASCADE,
  CONSTRAINT fk_pr_property FOREIGN KEY (property_id)     REFERENCES cs_properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cs_pricing (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  property_id   INT NULL,                 -- NULL = precio global por defecto
  season_type   ENUM('high','middle','low','14-day','any') DEFAULT 'any',
  price_per_night DECIMAL(10,2) NOT NULL,
  currency      VARCHAR(3) DEFAULT 'EUR',
  min_nights    INT DEFAULT 1,
  max_nights    INT NULL,
  is_active     TINYINT(1) DEFAULT 1,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY (property_id, season_type)
) ENGINE=InnoDB;
```

> **MVP simplificado (recomendado para primer release):** solo `cs_purchase_requests` + un único registro en `cs_pricing` con `property_id=NULL` y `season_type='any'`. Estructura preparada para evolucionar sin migración.

### Patrón a seguir (idéntico al resto del plugin)

```
src/
├── dtos/
│   ├── CreatePurchaseRequestDto.php
│   └── UpdatePurchaseRequestDto.php
├── entities/
│   └── PurchaseRequestEntity.php
├── repositories/
│   ├── PurchaseRequestRepository.php
│   └── PricingRepository.php
└── services/
    ├── PurchaseRequestService.php   ← orquesta validación + creación + notificación
    └── PricingService.php           ← calcula precio por noche/temporada
```

### Vistas

```
src/views/
├── public/
│   └── purchase/
│       ├── available-weeks.php      ← shortcode [mojo_buy] o sub-vista del panel
│       └── my-requests.php          ← lista de solicitudes del comprador
└── private/
    └── purchase/
        ├── purchase_admin.php       ← menú "Purchase Requests" en wp-admin
        └── PurchaseRequestsTable.php (WP_List_Table)
```

---

## 3. 🔧 Estructura de implementación

### Hooks a registrar (en `newmojo.php` o un nuevo `purchase.php` requerido desde ahí)

```php
// Activación → crear tablas vía dbDelta (mejor que parsear script.sql)
register_activation_hook(__FILE__, 'cs_install_purchase_tables');

// Menú admin
add_action('admin_menu', 'purchase_admin_menu');

// Shortcodes
add_shortcode('mojo_buy', 'get_purchase_view');           // listado + form de compra
add_shortcode('mojo_my_purchases', 'get_my_purchases');   // historial del comprador

// AJAX (frontend, owner logueado vía $_SESSION)
add_action('wp_ajax_mojo_purchase_get_available',   'mojo_purchase_get_available');
add_action('wp_ajax_mojo_purchase_get_price',       'mojo_purchase_get_price');
add_action('wp_ajax_mojo_purchase_submit_request',  'mojo_purchase_submit_request');
add_action('wp_ajax_mojo_purchase_cancel_request',  'mojo_purchase_cancel_request');

// AJAX (admin, manage_options)
add_action('wp_ajax_mojo_purchase_change_status',   'mojo_purchase_change_status');

// Email/notificación al admin: usar wp_mail dirigido a get_users(['role'=>'administrator'])
//   y reutilizar cs_templates con un nuevo ID (ej. id=8 'purchase_new_request')
```

> ⚠️ **Importante**: NO registrar `wp_ajax_nopriv_*` en estos endpoints (es el error generalizado del plugin actual). Validar `$_SESSION['mojo_owner_id']` dentro de cada handler de comprador.

### Flujo de archivos por feature

| Acción | Handler | Service llamado |
|---|---|---|
| Comprador entra a "Other Sharing Properties" → click "Buy weeks" | `get_purchase_view()` shortcode | `PropertyService::getBySlug` + `BookingService::getRentableBookings($property_id)` |
| Comprador ve precio de un rango | AJAX `mojo_purchase_get_price` | `PricingService::quote($property_id, $start, $end)` |
| Comprador confirma | AJAX `mojo_purchase_submit_request` | `PurchaseRequestService::create()` → notifica admins |
| Admin abre wp-admin → "Purchase Requests" | `purchase_admin_page()` + `PurchaseRequestsTable` | `PurchaseRequestService::getAll(filter)` |
| Admin aprueba | AJAX `mojo_purchase_change_status` | `PurchaseRequestService::approve($id)` → opcional: transferir booking, email a comprador |

---

## 4. 📊 Flujo funcional detallado (MVP)

```
┌──────────────────────────────────────────────────────────────────┐
│  1. Maria (owner de MS2) entra al dashboard                      │
│     → ve sección "Other Sharing Properties"                      │
│     → click en "VIEW MORE INFO" de "MS5 Stupa Hills SL"          │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  2. Página /property/ms5-stupa-hills/ (ya existe)                │
│     → añadir bloque NUEVO: "Available weeks for purchase"        │
│       (solo si $_SESSION['mojo_owner_id'] existe Y               │
│        el owner NO tiene share en esta propiedad)                │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  3. Lista de cs_booking WHERE type='for rent' AND end_date>=hoy  │
│     - Cada item: rango, noches, owner_position, precio estimado  │
│     - Botón "Request purchase"                                   │
└──────────────────────────────────────────────────────────────────┘
                              │ click "Request purchase"
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  4. Modal con DateRangePicker (puede acotar el rango original)   │
│     - Selecciona 1, 2, 3+ días dentro del rango disponible       │
│     - AJAX get_price → muestra total                             │
│     - Campo opcional "Message to admin"                          │
│     - Botón "Submit request"                                     │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  5. PurchaseRequestService::create()                             │
│     - INSERT cs_purchase_requests (status='pending')             │
│     - cs_log('Purchase request created', [...])                  │
│     - Email a TODOS los admins (wp_users role='administrator')   │
│       con plantilla cs_templates[id=8]                           │
│     - Email de confirmación al comprador                         │
│     - cs_notifications push para el comprador                    │
└──────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  6. Admin abre wp-admin → "Purchase Requests"                    │
│     - WP_List_Table con: comprador / vendedor / propiedad /      │
│       fechas / precio / estado / fecha solicitud                 │
│     - Filtros: pending | approved | rejected | all               │
│     - Acciones por fila: View / Approve / Reject                 │
└──────────────────────────────────────────────────────────────────┘
                              │ admin click "Approve"
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│  7. PurchaseRequestService::approve($id)                         │
│     - UPDATE status='approved', processed_by, processed_at       │
│     - **DECISIÓN DE NEGOCIO** (ver §5): qué hacer con cs_booking │
│       Opción A (MVP): NO tocar cs_booking (admin lo hace manual) │
│       Opción B: transferir owner_id del booking al comprador     │
│     - Email al comprador "approved"                              │
│     - cs_notifications push                                      │
└──────────────────────────────────────────────────────────────────┘
```

---

## 5. 🚀 Recomendaciones de mejora

### Para el MVP (no negociables)

1. **Usar `dbDelta()` en lugar de parsear `script.sql`** para las nuevas tablas. El método actual es frágil (ya hay un `;` mal puesto en `cs_templates`).
2. **Añadir nonces** (`wp_create_nonce` + `check_ajax_referer`) en los nuevos endpoints. No replicar el error del resto del plugin.
3. **NO usar `wp_ajax_nopriv_*`** en los nuevos endpoints. Validar sesión owner siempre.
4. **Separar pago de la solicitud**: la solicitud guarda `price_total` como snapshot pero el cobro es 100% offline. Añadir campo `payment_status` o `admin_note` para que el admin marque "paid" manualmente. Esto mantiene el MVP simple y deja la puerta abierta a integración futura con Stripe/PayPal.
5. **Idempotencia**: validar antes de crear que no exista otra solicitud `pending` del mismo comprador para el mismo rango y propiedad. Evita duplicados accidentales.
6. **Bloquear compras a fechas pasadas** y a rangos solapados con un `approved` previo del mismo booking.

### Para iterar después

7. **Tabla `cs_pricing` por temporada**: hoy un único precio plano; luego variable por high/middle/low.
8. **Conflicto compra ↔ exchange request**: si una semana está en una solicitud pending de exchange Y de purchase, decidir prioridad (recomendado: bloquear la segunda).
9. **Vista del comprador**: shortcode `[mojo_my_purchases]` con historial y estados.
10. **Hashear `cs_owners.password`**: oportunidad de hacerlo junto con esta feature.
11. **Renombrar el plugin internamente**: la cabecera dice `Plugin Name: Calendar System` y el directorio `mojosharing`. Confunde al instalar/desactivar.
12. **Versionar `THEME_VERSION` y la cabecera juntos**: hoy hay desfase (1.0 vs 7.0.8).

---

## Resumen ejecutivo (one-liner para reunión)

> Sí, podemos lanzar un MVP en pocas iteraciones. Reutilizamos `cs_owners`, `cs_properties` y `cs_booking`. Añadimos **una tabla nueva** (`cs_purchase_requests`) y opcionalmente otra para precios. El comprador usa la sección "Other Sharing Properties" del dashboard, ve las semanas marcadas `for rent`, manda solicitud, y el admin aprueba desde un nuevo menú en wp-admin (mismo patrón que las exchange requests). Pago totalmente offline en esta primera fase. La pieza más sensible a definir con el cliente es **qué pasa en `cs_booking` cuando una compra se aprueba** (¿se transfiere el `owner_id` al comprador, o lo hace el admin manualmente desde el panel del calendario existente?).

---

## Pregunta abierta clave para el cliente

| # | Pregunta | Por qué importa |
|---|---|---|
| 1 | Al aprobar una compra, ¿el sistema debe **transferir automáticamente** el `cs_booking` al comprador, o el admin lo hace manual desde el calendario? | Define si necesitamos lógica adicional en `PurchaseRequestService::approve()`. |
| 2 | ¿La lista de precios es **única global**, **por propiedad**, o **por temporada**? | Define si en MVP solo se necesita 1 fila en `cs_pricing` o ya implementar el matriz por season. |
| 3 | ¿El comprador puede ver semanas `for personal use` o **solo** las `for rent`? | Define el filtro de `BookingService::getRentableBookings()`. |
| 4 | ¿Qué hacemos si dos compradores piden la **misma semana** al mismo tiempo? | Define si bloqueamos el booking al primer `pending` o solo al `approved`. |
| 5 | ¿Hay deadline para el primer release? | Define si entregamos MVP plano o ya con `cs_pricing` y vista de "mis compras". |

---

## 6. 🎨 UI / UX — Dónde colocar el botón "Buy weeks"

### Regla de negocio (instrucciones del cliente)

Resumen literal de lo recibido:

- Compra **de semanas entre propietarios**, sean del mismo MS o de otro.
- Ejemplo: Maria (MS2) puede comprar una semana de Nicolai (MS5). Ve sus rentals, elige semana, ve precio según tarifa.
- Existe una **lista de precios mínimos** mantenida por las admins.
- El propietario envía una **solicitud**.
- ⚠️ **La solicitud NO va al vendedor**, va a las administradoras. Ellas gestionan la operación.

### Contexto técnico observado

Hay dos pantallas según la relación del owner logueado con la propiedad. Maria puede llegar a la sección de compra desde **ambos puntos del dashboard**: "My Shared Properties" y "Other Sharing Properties".

| Caso | URL | Vista renderizada | ¿Maria es co-owner? |
|---|---|---|---|
| **A — Propiedad propia** (imagen 1, MS2) | `/panel/?property_id=55&property_share=1` | `[mojo_panel]` → `get_panel()` con tabs `Calendar / Property Operation / Share Owners / Exchange Requests` | **Sí** (shares 1 y 2) |
| **B — Propiedad ajena** (imagen 2, MS1) | `/property/ms1-stupa-hills/` | `single-property.php` vía `template_redirect` con galería + descripción + quote form | No |

> **Aclaración importante (corrige versión anterior de este documento):** una propiedad tiene varios owners distintos en sus shares. En MS2 conviven Maria (1, 2), Raymond (3), Preben (4), Jesper (5), Matthias (6), Jesper & Mette (7), Thomas (8). Por tanto **la compra entre copropietarios sí aplica también dentro de una propiedad propia**, siempre que el vendedor ≠ comprador.

### Filtro real de inventario (único en todo el sistema)

El filtro NO es "propiedad mía vs ajena". Es a nivel de **booking**:

```
Bookings comprables para el owner X en la propiedad P:
  cs_booking.calendar_id  ∈ calendarios de P
  AND cs_booking.type     = 'for rent'
  AND cs_booking.end_date >= TODAY
  AND cs_booking.owner_id <> X.id          ← clave: nunca mis propios rentals
  AND NOT EXISTS (
        cs_purchase_requests pr
        WHERE pr.booking_id = cs_booking.id
        AND   pr.status IN ('pending','approved')
      )                                     ← evita doble venta
```

Con eso un mismo `BookingService::getRentableBookings($property_id, $exclude_owner_id)` cubre los dos casos.

### Decisión de diseño

1. **NO añadir el botón dentro de `mojo_panel--actions`** ([calendar.php:127](src/views/public/templates/calendar.php#L127)). Esa botonera es contextual al booking propio seleccionado y se controla con la máquina de estados `j` / `k` / `l` de [calendar.js](assets/js/public/calendar.js). Meter ahí un botón de "Buy" que depende del booking pintado de OTRO owner mezcla dos contextos y rompe el toggle existente.
2. **Sí añadir un bloque dedicado en el aside del panel** (Caso A), paralelo a "BOOKED DATES", listando los rentals de los **otros** shares.
3. **Sí añadir el mismo bloque en `single-property.php`** (Caso B), ahora sin guard de "no co-owner": el filtro es siempre `owner_id != yo`.
4. **El destinatario del request es siempre el admin** (`get_users(['role'=>'administrator'])`), nunca el vendedor. La pantalla del comprador deja eso explícito ("Your request will be reviewed by Mojo Sharing admins").

### Propuesta concreta de UI

#### Caso A (panel propio, MS2) — **bloque nuevo en el aside**

Se inserta dentro del `<div class="mojo_panel--aside-top">` de [calendar.php](src/views/public/templates/calendar.php), después del bloque `BOOKED DATES` y antes de `box_body_request`. La botonera `mojo_panel--actions` queda intacta.

```
┌──────────────── ASIDE del panel ────────────────┐
│ SEASONS  (Low / Middle / High)                   │  ya existe
│ COLOR CODES FOR OWNERS (1..8)                    │  ya existe
│ SELECTED NIGHTS                                  │  ya existe
│ BOOKED DATES (las MIAS)                          │  ya existe
│                                                  │
│ ✚ NUEVO BLOQUE                                   │
│ ┌────────────────────────────────────────────┐   │
│ │ FOR SALE BY OTHER SHARES                   │   │
│ │ • Share #5 · 12-19 Jul · 7n · €1,400 [BUY] │   │
│ │ • Share #3 · 03-10 Ago · 7n · €1,400 [BUY] │   │
│ │ Reviewed by Mojo Sharing admins            │   │
│ └────────────────────────────────────────────┘   │
│                                                  │
│ your selected dates                              │  ya existe
│ ── separador ──                                  │
│ [ Up for Rental ] [ Exchange dates ] ...         │  ya existe (intacto)
└──────────────────────────────────────────────────┘
```

#### Caso B (vista pública, MS1) — **mismo bloque** dentro de `single-property.php`

Mismo componente, sin guard de pertenencia. Se inserta entre KEY FEATURES y el quote form.

```
┌──────────────────────────────────────────────────────────────────┐
│ [RENTAL CALENDAR 2026]                       [< BACK TO PROPS]   │  ya existe
├──────────────────────────────────────────────────────────────────┤
│  K/S MS1-Stupa Hills              [galería Splide]               │  ya existe
│                                   KEY FEATURES                   │
├──────────────────────────────────────────────────────────────────┤
│  ✚ NUEVO BLOQUE — "Available weeks for purchase"                 │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ Share #3 · 12 Jul → 19 Jul · 7n  · €1,400      [ BUY WEEK]│  │
│  │ Share #5 · 03 Ago → 10 Ago · 7n  · €1,400      [ BUY WEEK]│  │
│  └────────────────────────────────────────────────────────────┘  │
│  Your request will be reviewed by Mojo Sharing admins.           │
├──────────────────────────────────────────────────────────────────┤
│  Fill out the form to receive more information on the booking!   │  ya existe
│  [ REQUEST FOR QUOTE ]                                           │
└──────────────────────────────────────────────────────────────────┘
```

#### Modal de compra (compartido por A y B)

```
┌─────────────────────────────────────────────┐
│  Buy week — MS2 Wave SL · Share #5  [ × ]   │
├─────────────────────────────────────────────┤
│  Range            12 Jul → 19 Jul 2026      │
│  Nights           7                         │
│  Price per night  €200  (min. price list)   │
│  Total            €1,400                    │
│                                             │
│  Optional message to admin                  │
│  ┌─────────────────────────────────────┐    │
│  │                                     │    │
│  └─────────────────────────────────────┘    │
│                                             │
│  ℹ Your request goes to Mojo Sharing        │
│    admins, not to the seller. Payment is    │
│    handled offline.                         │
│                                             │
│         [ Cancel ]   [ Submit request ]     │
└─────────────────────────────────────────────┘
```

### Snippets de implementación sugeridos

**1. Servicio único** (un solo método cubre ambos casos):

```php
// src/services/BookingService.php
public function getRentableBookings(int $property_id, ?int $exclude_owner_id = null): array
{
    return $this->bookingRepository->findForSale($property_id, $exclude_owner_id);
}
```

**2. Inclusión del componente** en ambos templates:

```php
// Caso A — calendar.php (dentro del aside, después de BOOKED DATES)
<?php
$available = $serviceB->getRentableBookings($property_id, intval($_SESSION['mojo_owner_id']));
if (!empty($available)) {
    include __DIR__ . '/purchase-available.php';
}
?>

// Caso B — single-property.php (después de KEY FEATURES)
<?php
$owner_id = isset($_SESSION['mojo_owner_id']) ? intval($_SESSION['mojo_owner_id']) : 0;
if ($owner_id > 0) {
    $bookingService = new BookingService();
    $available = $bookingService->getRentableBookings($cpd->getId(), $owner_id);
    if (!empty($available)) {
        include __DIR__ . '/purchase-available.php';
    }
}
?>
```

> El `if ($owner_id > 0)` en B es solo porque la página es pública y un visitante anónimo no puede comprar. En A el owner ya está garantizado por `get_panel()`.

**3. Componente único** `src/views/public/templates/purchase-available.php`:

```php
<section class="mojo_purchase-block">
  <h4 class="mojo_purchase-title">FOR SALE BY OTHER SHARES</h4>
  <ul class="mojo_purchase-list">
    <?php foreach ($available as $b): ?>
      <li class="mojo_purchase-item"
          data-booking-id="<?php echo intval($b['id']); ?>"
          data-start="<?php echo esc_attr($b['start_date']); ?>"
          data-end="<?php echo esc_attr($b['end_date']); ?>"
          data-price="<?php echo esc_attr($b['price_total']); ?>"
          data-share="<?php echo intval($b['owner_position']); ?>">
        <div class="mojo_purchase-item-info">
          <span class="mojo_purchase-share">Share #<?php echo intval($b['owner_position']); ?></span>
          <span class="mojo_purchase-range">
            <?php echo esc_html(format_range($b['start_date'], $b['end_date'])); ?>
          </span>
          <span class="mojo_purchase-meta">
            <?php echo intval($b['nights']); ?>n · €<?php echo number_format($b['price_total'], 0); ?>
          </span>
        </div>
        <button type="button"
                class="mojo_panel-submit mojo_purchase-buy"
                data-action="open-purchase-modal">
          Buy
        </button>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="mojo_purchase-note">
    Your request will be reviewed by Mojo Sharing admins. Payment is handled offline.
  </p>
</section>
```

Reutiliza la clase `mojo_panel-submit` para herencia del estilo verde corporativo de [`assets/css/panel/style.css`](assets/css/panel/style.css). Solo añadir un modificador `.mojo_purchase-buy` para overrides puntuales (tamaño compacto en el aside).

### Resumen de impacto en archivos

| Archivo | Cambio | Tipo |
|---|---|---|
| [src/views/public/templates/calendar.php](src/views/public/templates/calendar.php) | +include condicional del bloque en el aside | Adición |
| [src/views/public/templates/single-property.php](src/views/public/templates/single-property.php) | +include condicional entre KEY FEATURES y quote form | Adición |
| `src/views/public/templates/purchase-available.php` | Nuevo componente | Nuevo |
| [src/services/BookingService.php](src/services/BookingService.php) | +`getRentableBookings($property_id, $exclude_owner_id)` | Adición |
| [src/repositories/BookingRepository.php](src/repositories/BookingRepository.php) | +`findForSale($property_id, $exclude_owner_id)` | Adición |
| `mojo_panel--actions` en calendar.php | **Sin cambios** | — |
| Lógica `j/k/l` en [calendar.js](assets/js/public/calendar.js) | **Sin cambios** | — |

### Resumen visual de la decisión

| Pantalla | Cambio UI | Riesgo |
|---|---|---|
| Panel propio (imagen 1, MS2) | +bloque en aside listando rentals de otros shares | Bajo (composición, no toca botonera de turnos) |
| Vista pública (imagen 2, MS1) | +bloque entre KEY FEATURES y quote form | Bajo |
| Dashboard "My Shared Properties" / "Other Sharing Properties" | +badge opcional "X weeks for sale" en cada card | Bajo |
