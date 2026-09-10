# Order notifications

Final order outcomes are delivered through a durable outbox and Kafka. The outbox stores one immutable payload for each persisted order outcome (`success` or `failure`) and tracks publication separately from delivery. This prevents a Kafka redelivery from sending a channel that already succeeded.

The payload contains the order number, status, total and currency, promo-code data when available, products, shipping, payment status and customer contact data. It is created from the persisted order snapshot, so notifications do not depend on the checkout session or cart state.

Three independent deliveries are supported:

- Telegram uses `ORDER_NOTIFICATIONS_TELEGRAM_BOT_TOKEN` and `ORDER_NOTIFICATIONS_TELEGRAM_CHAT_ID`.
- SalesDrive uses `SALESDRIVE_ORDER_ENDPOINT` and `SALESDRIVE_API_TOKEN`. The endpoint is intentionally a configuration placeholder until the CRM API contract is provided.
- Email is sent only when the order contains a customer email. `ORDER_NOTIFICATIONS_MAILER` defaults to the existing `log` mailer.

The Kafka topic and consumer group are configured with `ORDER_NOTIFICATIONS_KAFKA_TOPIC` and `ORDER_NOTIFICATIONS_KAFKA_CONSUMER_GROUP`. The long-lived consumer can be started with:

```bash
php artisan kafka:consume \
  --topics=order-notifications \
  --consumer='App\\Kafka\\Consumers\\OrderNotificationConsumer' \
  --groupId=order-notifications
```

If a publisher worker is interrupted, pending or failed outbox events can be queued again:

```bash
php artisan orders:notifications:republish --limit=100
```

Missing provider configuration is logged as a critical error and does not terminate the consumer. Provider failures are recorded per channel in `order_notification_deliveries`; successful channels remain marked as sent when Kafka retries the event.
