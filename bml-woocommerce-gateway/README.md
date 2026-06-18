# BML Connect Gateway for WooCommerce

A WooCommerce payment gateway for [BML Connect](https://docs.merchants.bankofmaldives.com.mv)
(Bank of Maldives' hosted payment gateway). Customers are redirected to BML's
hosted payment page; once they pay, the order is verified server-side and marked
paid.

> Independent integration. Not affiliated with or endorsed by Bank of Maldives.

## Features

- Classic (shortcode) checkout **and** WooCommerce Cart/Checkout Blocks.
- MVR and USD currencies (amounts converted to minor units — laari/cents).
- Separate sandbox and production credentials.
- Server-side transaction verification on both the browser return and the
  server-to-server webhook callback (the order is never completed on the
  redirect alone).
- HPOS (High-Performance Order Storage) compatible.
- Optional debug logging.

## How it works

1. **Checkout** — `WC_Gateway_BML::process_payment()` creates a transaction via
   `POST /transactions` (`includes/class-bml-client.php`), stores the returned
   transaction id on the order, sets the order to *pending*, and redirects the
   customer to the BML hosted page (`transaction['url']`).
2. **Return / webhook** — BML redirects the browser back to, and also calls,
   `…/?wc-api=wc_gateway_bml&order_id=…&order_key=…`. The handler validates the
   order key, re-fetches the transaction with `GET /transactions/{id}`, and acts
   on its `state`:
   - `CONFIRMED` → `payment_complete()` + empty cart.
   - `CANCELLED` / `EXPIRED` / `FAILED` → order marked *failed*.
   - anything else (`QR_CODE_GENERATED`, `RESERVED`, `PROCESSING`) → left *pending*.
   The handler is idempotent and returns a plain `200` JSON body to webhook
   callers while redirecting real browsers.

## Installation

Copy the `bml-woocommerce-gateway` folder into `wp-content/plugins/`, activate it,
then configure at **WooCommerce → Settings → Payments → BML Connect**.

Credentials come from the [BML Merchant Portal](https://dashboard.merchants.bankofmaldives.com.mv)
(separate sets for sandbox and production).

## Sandbox testing checklist

1. Set the store currency to MVR or USD.
2. Enable the gateway, tick **Sandbox mode**, enter the sandbox API key and App ID, save.
3. Place a test order → you should be redirected to the BML sandbox page.
4. Complete the sandbox payment → you return to the order-received page, the order
   auto-completes, and `_bml_transaction_id` is saved in the order meta.
5. Cancel a payment → order is marked *failed* and you return with a notice.
6. Repeat steps 3–5 using a page built with the **Checkout block**.
7. Enable **Debug log** to inspect requests/responses under
   **WooCommerce → Status → Logs** (source `bml-connect`).

## Environments

| Mode       | Base URL                                                       |
| ---------- | -------------------------------------------------------------- |
| Sandbox    | `https://api.uat.merchants.bankofmaldives.com.mv/public/`      |
| Production | `https://api.merchants.bankofmaldives.com.mv/public/`          |

## File layout

```
bml-woocommerce-gateway.php          Bootstrap, registration, HPOS/Blocks declarations
includes/class-bml-client.php        BML Connect HTTP client (wp_remote_*)
includes/class-wc-gateway-bml.php    Gateway: settings, process_payment, callback
includes/class-bml-blocks-support.php  Blocks checkout integration
assets/js/blocks.js                  Blocks payment method registration
```
