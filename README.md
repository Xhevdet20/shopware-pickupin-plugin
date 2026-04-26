# Pickup in Store – Shopware 6.7 Plugin

## Overview

This plugin adds a **Pickup in Store** shipping method to Shopware 6.7.
When a customer selects this shipping method during checkout, the order's shipping address is **automatically replaced** with the store's address configured in the plugin settings. Any address entered by the customer is ignored for pickup orders.

---

## Installation

1. Copy the plugin folder into `custom/plugins/shopware-pickupin-plugin`.
2. Run `bin/console plugin:refresh`.
3. Run `bin/console plugin:install --activate PickupInStore`.
4. Clear the cache: `bin/console cache:clear`.

On install the plugin automatically creates the **Pickup in Store** shipping method and activates it.

---

## Configuration

Open the Shopware Administration → **Extensions → My extensions → Pickup in Store → Configure**.

| Field | Description |
|---|---|
| Company / Store Name | Name of your store (shown as company in the shipping address) |
| Contact First Name | First name for the shipping address |
| Contact Last Name | Last name for the shipping address |
| Street and House Number | Street address of the store |
| Additional Address Line | Optional second address line |
| ZIP / Postal Code | Postal code |
| City | City |
| Country ISO Code | Two-letter ISO code, e.g. `DE`, `US`, `GB` |
| Phone Number | Optional store phone number |

> **Important:** All mandatory fields (first name, last name, street, ZIP, city, country ISO) must be filled before the address override takes effect. If any required field is missing, the customer's original address is kept to avoid invalid orders.

---

## How it works

```
Customer places order
        │
        ▼
CheckoutOrderPlacedEvent fired
        │
        ▼
OrderPlacedSubscriber::onOrderPlaced()
        │
        ├─ Delivery uses "pickup_in_store" shipping method?
        │        NO  → nothing happens
        │        YES ↓
        ├─ Plugin fully configured?
        │        NO  → nothing happens (safety guard)
        │        YES ↓
        └─ StoreAddressService::overwriteDeliveryAddress()
                 │
                 └─ Updates order_address record in DB
                    with the configured store address
```

### Key components

| File | Responsibility |
|---|---|
| `src/PickupInStore.php` | Plugin lifecycle: creates / deactivates the shipping method |
| `src/Service/StoreAddressService.php` | Reads system config, resolves country by ISO, updates `order_address` |
| `src/Subscriber/OrderPlacedSubscriber.php` | Listens to `CheckoutOrderPlacedEvent`, triggers the override |
| `src/Resources/config/config.xml` | Admin UI configuration form |
| `src/Resources/config/services.xml` | Symfony DI service definitions |

---

## Uninstalling

When uninstalling **without** keeping user data, the shipping method is **deactivated** (not deleted) to preserve the integrity of historical orders that used it.

---

## Requirements

- Shopware **6.7.x**
- PHP **8.2+**

---

For full details, see:
- ../PLUGIN_SPEC_AND_PLAN.md
- ../PLUGIN_DOCUMENTATION.md
- ../IMPLEMENTATION_LOG.md
