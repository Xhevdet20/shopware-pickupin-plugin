# Pickup in Store Plugin — Detailed Specification & Build Plan

## 1) Goal
Build a Shopware 6.7 plugin named **Pickup in Store** that guarantees:

- If the selected shipping method is the configured **Pickup in Store** method, the order shipping address is overwritten with the configured store address.
- Any customer-provided shipping address must be ignored for that case.

---

## 2) Functional Requirements

1. Plugin name/class: `PickupInStore`.
2. Compatibility: Shopware `6.7`.
3. Use Shopware best practices:
   - Event subscriber
   - Dependency Injection via `services.xml`
   - Native plugin configuration (`SystemConfig` + config form)
4. Configuration must be done in native admin plugin settings.
5. Clean and understandable source code.

---

## 3) Technical Design

### Event trigger
- Subscribe to `CheckoutOrderPlacedEvent`.

### Decision logic
1. Read configured pickup shipping method ID from plugin configuration.
2. Load the placed order and its deliveries.
3. For each delivery:
   - If `delivery.shippingMethodId` equals configured pickup method ID,
   - Update the referenced order shipping address (`order_address`) with configured store address fields.

### Data source
- Store address is read from `SystemConfig` keys under `PickupInStore.config.*`.

### Data target
- `order_address` entity, using `order_address.repository` update operation.

---

## 4) Configuration Fields

In plugin settings:

- `pickupShippingMethodId` (entity single select: `shipping_method`) ✅ required
- `storeSalutationId` (entity single select: `salutation`) ✅ required
- `storeFirstName` ✅ required
- `storeLastName` ✅ required
- `storeStreet` ✅ required
- `storeZipcode` ✅ required
- `storeCity` ✅ required
- `storeCountryId` (entity single select: `country`) ✅ required
- `storeCompany` optional
- `storePhoneNumber` optional
- `storeAdditionalAddressLine1` optional
- `storeAdditionalAddressLine2` optional

---

## 5) Non-Functional Requirements

- No storefront template changes required.
- No checkout UX changes required.
- Idempotent behavior for relevant deliveries.
- Safe fallback: if required config is missing, do nothing.

---

## 6) File Structure Plan

```text
PickupInStore/
  composer.json
  src/
    PickupInStore.php
    Subscriber/
      CheckoutOrderPlacedSubscriber.php
    Resources/
      config/
        services.xml
        config.xml
```

Additional docs in workspace root:

- `IMPLEMENTATION_LOG.md`
- `PLUGIN_DOCUMENTATION.md`

---

## 7) Step-by-Step Execution Plan

1. Create plugin skeleton (`composer.json`, plugin base class).
2. Add configuration form (`config.xml`) with all required fields.
3. Register services/subscriber (`services.xml`).
4. Implement subscriber logic to overwrite shipping address on order placement.
5. Write technical/user documentation and setup instructions.
6. Verify structure and consistency.

---

## 8) Acceptance Criteria

- When pickup shipping method is selected, order shipping address equals configured store address.
- When another shipping method is selected, shipping address remains customer-provided.
- Configuration is editable from admin plugin settings.
- Plugin code follows Shopware standards and is ready to zip and install.
