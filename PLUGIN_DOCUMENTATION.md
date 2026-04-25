# Pickup in Store Plugin — Technical Documentation

## 1) What this plugin does

The plugin enforces a strict shipping-address rule for pickup orders:

- If the selected shipping method equals the configured **Pickup shipping method**, the order shipping address is replaced by the configured **store address**.
- Customer-entered shipping address is not kept for that case.

For all other shipping methods, nothing is changed.

---

## 2) Shopware compatibility

- Target: **Shopware 6.7**

---

## 3) Architecture

### Main files

- [PickupInStore/composer.json](PickupInStore/composer.json)
- [PickupInStore/src/PickupInStore.php](PickupInStore/src/PickupInStore.php)
- [PickupInStore/src/Resources/config/config.xml](PickupInStore/src/Resources/config/config.xml)
- [PickupInStore/src/Resources/config/services.xml](PickupInStore/src/Resources/config/services.xml)
- [PickupInStore/src/Subscriber/CheckoutOrderPlacedSubscriber.php](PickupInStore/src/Subscriber/CheckoutOrderPlacedSubscriber.php)

### Runtime flow

1. Customer places order.
2. Shopware dispatches `CheckoutOrderPlacedEvent`.
3. Subscriber checks if delivery shipping method matches configured pickup method.
4. If match: subscriber updates `order_address` record used by the delivery with configured store address.
5. Order now contains store address as shipping address for pickup delivery.

---

## 4) Configuration (Admin)

Open:

**Extensions → My extensions → Pickup in Store → Configure**

Set:

1. **Pickup shipping method** (required)
2. Store address (required):
   - Salutation
   - First name
   - Last name
   - Street
   - ZIP code
   - City
   - Country
3. Optional:
   - Company
   - Phone number
   - Additional line 1
   - Additional line 2

All values are stored using native Shopware `SystemConfig`.

---

## 5) How everything was configured

### Service wiring

In [PickupInStore/src/Resources/config/services.xml](PickupInStore/src/Resources/config/services.xml):

- Subscriber is registered with `kernel.event_subscriber` tag.
- Dependencies injected:
  - `Shopware\Core\System\SystemConfig\SystemConfigService`
  - `order.repository`
  - `order_address.repository`

### Plugin config schema

In [PickupInStore/src/Resources/config/config.xml](PickupInStore/src/Resources/config/config.xml):

- Uses native Shopware config schema.
- Uses entity selectors (`sw-entity-single-select`) where relational IDs are needed.
- Uses text input fields for standard address values.

### Business logic

In [PickupInStore/src/Subscriber/CheckoutOrderPlacedSubscriber.php](PickupInStore/src/Subscriber/CheckoutOrderPlacedSubscriber.php):

- Reads config under `PickupInStore.config.*`
- Validates required fields
- Loads order deliveries
- Updates matching delivery shipping order addresses

---

## 6) Installation and activation

Place the folder `PickupInStore` in Shopware custom plugins directory (usually `custom/plugins`).

Then in Shopware:

1. Refresh plugin list
2. Install plugin
3. Activate plugin
4. Open plugin configuration and fill required fields

---

## 7) Manual test checklist

### Test A — Pickup selected

1. Configure plugin pickup shipping method.
2. Add product to cart.
3. At checkout, choose pickup shipping method.
4. Enter any shipping address (different from store address).
5. Place order.
6. Verify order delivery address in admin = configured store address.

Expected: customer shipping address is overridden.

### Test B — Non-pickup selected

1. Choose another shipping method.
2. Place order.
3. Verify order shipping address remains customer-entered.

Expected: no override.

---

## 8) Packaging for submission

Create ZIP from the plugin root folder:

- Include `PickupInStore/` and all nested files.
- Ensure folder structure remains intact.

The ZIP can then be installed and reviewed during interview workflow testing.
