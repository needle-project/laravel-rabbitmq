# Release Notes for 0.5.x

### [0.5.0](https://github.com/needle-project/laravel-rabbitmq/compare/0.4.3...feature/0.5.0) - 2024-03 - *!NOT RELEASED*
* Add automatically `delivery_mode` to 2 (persistent) for durable entities
* Add additional arguments for entities in order to support `quorum` type
* Add `ticket` argument
* Add properties for AMQP Messages when publishing
* Fixed `wait` for consumers to align with consumer timeout
