# Order notifications

Final order outcomes are delivered through a durable outbox and a queued Laravel job. The outbox stores one immutable payload for each persisted order outcome (`success` or `failure`) and tracks processing separately from delivery. This keeps notification delivery asynchronous without requiring an external broker.

The payload contains the order number, status, total and currency, promo-code data when available, products, shipping, payment status and customer contact data. It is created from the persisted order snapshot, so notifications do not depend on the checkout session or cart state.

Three independent deliveries are supported:

- Telegram uses `ORDER_NOTIFICATIONS_TELEGRAM_BOT_TOKEN` and `ORDER_NOTIFICATIONS_TELEGRAM_CHAT_ID`.
- SalesDrive uses `SALESDRIVE_ORDER_ENDPOINT` and `SALESDRIVE_API_TOKEN`. The endpoint is intentionally a configuration placeholder until the CRM API contract is provided.
- Email is sent only when the order contains a customer email. `ORDER_NOTIFICATIONS_MAILER` defaults to the existing `log` mailer.

Laravel Queue/Horizon processes `ProcessOrderNotificationEventJob`.

If a worker is interrupted, pending or failed outbox events can be queued again:

```bash
php artisan orders:notifications:republish --limit=100
```

Missing provider configuration is logged as a critical error and does not terminate processing for other channels. Provider failures are recorded per channel in `order_notification_deliveries`; successful channels remain marked as sent when the job is retried.
