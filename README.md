# Pickup in Store (Shopware 6.7)

This plugin adds a **Pickup in Store** shipping method.

If a customer chooses this shipping method, the order shipping address is replaced with your store address from plugin settings.

## Quick install

1. Put this plugin in your Shopware plugins folder.
2. Run:
   - `bin/console plugin:refresh`
   - `bin/console plugin:install --activate PickupInStore`
   - `bin/console cache:clear`

The plugin creates and activates the **Pickup in Store** shipping method automatically.

## Setup

In Shopware Admin:
**Extensions → My extensions → Pickup in Store → Configure**

Fill in your store address details.

Required fields:
- First name
- Last name
- Street
- ZIP / Postal code
- City
- Country ISO code (for example: `DE`, `US`, `GB`)

Optional fields:
- Company / Store name
- Additional address line
- Phone number

## Behavior

- For normal shipping methods: nothing changes.
- For **Pickup in Store**: shipping address is changed to your configured store address.
- If required settings are missing: the customer address is kept (safe fallback).

## Uninstall note

On uninstall (without keeping user data), the shipping method is **deactivated**, not deleted. This keeps old orders valid.

## Requirements

- Shopware 6.7.x
- PHP 8.2+
