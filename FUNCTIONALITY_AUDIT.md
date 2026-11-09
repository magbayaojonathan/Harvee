# Functionality audit

Static review, November 9, 2026. Deployment configuration is prepared; marketplace workflows still require end-to-end validation.

## Fixed

- Checkout omitted required order subtotal and shipping address fields. It now saves subtotal/total and snapshots the customer's default shipping recipient, address, phone and instructions. Customers must save a shipping address first.
- Checkout locks cart/product rows during the transaction and rejects nonpositive quantities to reduce concurrent overselling.
- Removed hardcoded hosting credentials and host-header-based credential selection. Production requires explicit credentials, returns 503 on database failures, and hides error details. Optional verified MySQL TLS is supported.
- Successful login regenerates the session ID.
- Added the missing password-help route with an availability message and disabled unimplemented social login buttons.
- Marked GCash and bank transfer unavailable; checkout uses cash on delivery.
- Added Docker/Render setup, database-aware health checks, persistent uploads/sessions, internal file restrictions and upload script execution restrictions.

## Missing or incomplete

1. Password recovery and email verification: password-help is informational; reset email/token redemption and verification delivery are not implemented.
2. Google/Facebook login: no OAuth handlers. Installing Firebase does not implement PHP authentication.
3. Public contact form: `contact.php` only shows a demo alert and resets the form; messages are not sent or stored. Customer-to-farmer messaging is separate.
4. Online payments: GCash, bank/card processing, payment webhooks, refunds and COD collection reconciliation are not implemented end to end.
5. Multi-farmer confirmation: any farmer owning an order item can confirm the whole order in `farmer/orders.php`. Decide whether to split checkout by farmer or require each farmer to confirm before driver assignment.
6. CSRF and abuse controls: state-changing forms need consistent CSRF tokens; authentication endpoints need rate limiting. Cookie hardening does not replace these controls.
7. Image upload validation: delivery/review uploads fall back to file extensions when MIME validation fails. Require verified image decoding instead. Apache script restrictions do not validate image content.
8. Admin operations: the database has an admin role, but no admin dashboard for moderation, disputes, account management or farmer verification.
9. Notifications: database tables alone do not provide email/SMS delivery, workers or retries.
10. Production assets: pages use external Tailwind/CDN scripts. Compile and host production CSS and review external availability requirements.

## Validation limits

PHP syntax was checked locally. Local MySQL was unreachable because the server was stopped. Docker is not installed, so container build and live Render deployment were not tested. Complete the smoke checklist in `DEPLOY_ONLINE.md` with a configured database before public launch.
