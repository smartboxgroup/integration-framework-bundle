# UPGRADE FROM 1.x to 2.0

## UsesGuzzleHttpClient Trait & HttpClientInterface Interface

* `HttpClientInterface` interface was added and `RestConfigurableProducer` implements it via the `UsesGuzzleHttpClient` trait. The interface adds an extra function `addHandler()` that allows users to add extra Handlers to the `ClientInterface`. By default a new handler is added (`Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Middleware`) while building the container fixing an bug in Guzzle that incorrectly splits multibyte charaters in two when truncating the response body. If you're already tweaking the Guzzle client used by the RestConfigurableProducer, you'll have to check if this new handler overrides it.


## AMQP Support

The AMQP driver was revamped **to use the [php-amqplib](https://github.com/php-amqplib/php-amqplib) library** instead of the PHP's built-in AMQP functions. The reason behind this was to take advantage of the non-blocking `consume()` function of this library, which the native driver doesn't support. Naturally, now the php-amqplib is a [dev-dependency](https://getcomposer.org/doc/04-schema.md#require-dev) of this bundle **and a hard dependency** of your project if you plan to use AMQP.

### Parameters

* `timeout` was renamed to `read_timeout`.
* `prefetch_count` was added. STOMP defaults to 1 (and currently only supports that value) and AMQP to 10.
* `hearbeat` was added and defaults to 60 seconds.
* `connections` was removed. Use `hostname` instead.
* `default_queue_consumer` was added. Set your default queue consumer with this key. Useful to switch from STOMP to AMQP if you're already using this bundle.

### Interfaces and classes

* To accommodate the async nature of AMQP, `QueueDriverInterface` was heavily modified and two new interfaces extend it: `SyncQueueDriverInterface` and `AsyncQueueDriverInterface`. STOMP uses the former and AMQP the latter. Functions strictly related to STOMP or synchronous drivers were moved to the `SyncQueueDriverInterface` (i.e.: `subscribe()`, `receive()`). 
 
* Signatures for the `QueueDriverInterface` vary slightly from the original implementation:
    * `format` (i.e.: `json`) was removed from the `configure()` function and it was replaced by `vhost` (string).

* `PurgableQueueDriverInterface` was removed.
* `AmqpQueueHandler` and `QueueManager` were removed.
* `QueueMessage` now includes an extra property called `messageId`. This is used to keep track of which message needs to be `ack`-ed in the broker. `QueueMessageInterface` was updated as well.

### Smoke tests

* `QueueDriverConnectionSmokeTest` will now insert a message into the `isalive` queue with a queue TTL of 1 second (via the `x-message-ttl` header). Depending on your DLX configuration, you might need to exclude this queue from the exchange to prevent polluting the queue where your DLX inserts dead messages.

### Other requirements

* PHP's `ext-sockets` is now a requirement of this bundle, due to `php-amqplib` requiring it.

## Exception handler

* The original `ExceptionHandler` for poisoned messages was renamed to `DecodingExceptionHandler` and gives the consumer a chance to get a fixed message. The default behaviour remains rethrowing the exception, but if you have a custom handler, now you can return a new `QueueMessageInterface` and continue the processing of the message.
* Instead of using `__invoke()`, DecodingExceptionHandlers must implement the `handle()` function and return a `QueueMessage` or throw an exception. 

## Drivers

* `QueueDriverInterface` was modified to decouple the serialization responsibility. Now the driver only accepts a string, instead of a `QueueMessage`. To achieve this, the `send()` function signature was changed to accept a `destination`, `string` (serialized payload), and `headers` 
