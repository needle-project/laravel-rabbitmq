# Release Notes for 0.5.x

### [0.5.1](https://github.com/needle-project/laravel-rabbitmq/compare/0.5.0...feature/0.5.1) - 2024-10
* Re-throw exception in BaseConsumerCommand - fixes the inability to report outside CLI when the issue is before a message processor
  * When it cannot bind to an exchange, an error is reported in CLI but when the process is run by a supervisor that suppress the logs the error is never reported. By taking this approach, it will fall back on Laravel's logging setup

### [0.5.0](https://github.com/needle-project/laravel-rabbitmq/compare/0.4.3...feature/0.5.0) - 2024-03
* Add automatically `delivery_mode` to 2 (persistent) for durable entities
* Add additional arguments for entities in order to support `quorum` type
* Add `ticket` argument
* Add properties for AMQP Messages when publishing
* Fixed `wait` for consumers to align with consumer timeout
