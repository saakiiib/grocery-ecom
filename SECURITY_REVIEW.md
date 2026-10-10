# Backend security and checkout review

Review date: 8 October 2026

## Scope

This is a static review of the Laravel source supplied in the ZIP, with focused inspection of authentication, account access, basket pricing, checkout, payment confirmation, and product descriptions. The archive did not include a production `.env`, database, uploaded product files, or installed Composer dependencies. This is not a live penetration test or a verification of the deployed server.

## Issues found and changes made

| Finding | Change |
| --- | --- |
| New shopper and administrator passwords could be exactly six digits. The seeder also created a predictable `admin@gmail.com` / `123456` administrator. | New and changed passwords now require at least 8 characters (up to 72). Existing six-digit passwords still work for sign-in, so existing users are not locked out. The seeder no longer creates a default account; it creates an administrator only when private `INITIAL_ADMIN_EMAIL` and `INITIAL_ADMIN_PASSWORD` values are provided. |
| Successful payment confirmation checked gateway status without comparing the captured amount and currency to the order. | Stripe confirmation now checks the payment-intent reference, order metadata, GBP currency, succeeded status, and exact amount. PayPal confirmation and its webhook now check completed status, GBP currency, and exact amount; the synchronous capture also checks the order reference. |
| Coupon use and loyalty point balances were validated in ways that could become stale during simultaneous checkouts. | Checkout locks the coupon before checking uses and locks/rechecks loyalty ledger rows before redeeming points. Checkout placement is also rate limited. |
| Product descriptions were rendered as raw HTML, creating a stored-XSS risk if imported or edited content contained unsafe markup. | Product descriptions are converted to plain text before being returned by the API and are escaped in the web product page. |
| Disabled users could retain access through an already active web session because the role middleware checked role but not account status. | User/admin middleware now requires an active account and logs inactive sessions out. |
| A stale out-of-stock item showed a £0 line and did not clearly explain why checkout would fail. | Basket and checkout now label unavailable items, explain the next step, and prevent checkout until the shopper removes them. |

## Existing protections observed

- Basket prices and promotion totals are recalculated from database records on the server.
- Checkout validates addresses, delivery dates, payment methods, and required consent; delivery eligibility is checked again when placing an order.
- Account order queries are scoped to the signed-in user; guest order access is tied to the current session.
- Web checkout uses Laravel CSRF protection; the webhook routes are exempted and verify provider signatures/transmissions.
- Login, registration, coupon checks, payment confirmation, and selected public forms already have request throttles.

## Remaining checks before production

- Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, and secure session cookies in the actual deployment environment. The ZIP has no production `.env`, so these settings could not be verified.
- Review live payment keys, webhook IDs/secrets, database grants, backups, server file permissions, mail settings, and the deployed domain/TLS configuration.
- Run the Laravel test suite, PHP syntax checks, Composer dependency audit, and browser/mobile checkout tests in an environment with PHP, Composer, dependencies, and a test database.
- The password policy applies to new and changed passwords only; existing six-digit passwords remain valid until users change them.
- The data ZIP did not include a database or product image uploads, so product image/description completeness and source/licensing could not be verified.
