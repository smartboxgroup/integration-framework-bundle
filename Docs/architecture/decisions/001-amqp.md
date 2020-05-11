# 1. Support to AMQP protocol with the php-amqp-lib

Date: 2020-04-21

## Status

Accepted

## Context

There was a need to consume the messages **in a non-blocking way** on our ESB. The former driver -PHP's native driver- didn't support such functionality. 

The former driver also ignored signals, due to its inability to acknowledge them **while waiting for a message**.

The possible solutions were:

  - To find a way to implement a non-blocking `consume()` function with the native driver.
  - To use an AMQP library that offers support for such functionalities, like php-amqp-lib.

### Native Driver

*Pros*: 
- Faster than any other library, due to it's a native extension written in C.
- Already implemented in the application.

*Cons*: 
- Does not offer support to consume messages in a non-blocking way.  
- Cannot listen to posix signals while consuming.

### PHP-AMQP-LIB

*Pros*: 
- It's RabbitMQ's recommended library.
- Offers the possibility to consume messages in a non-blocking way.
- Signals can be dispatched to the worker even if it's consuming messages.
- Offers other options like: heartbeats, channels, and multiple hosts.

*Cons*:
- Difficult to implement correctly, without bypassing our own interfaces.
- Different approach demands more time to learn and implement it.

## Decision

We decided to use the PHP-AMQP-LIB. 

An initial investigation was done to force the previous driver to listen to signals while consuming, but proved to be impossible. The `consume` function of PHP's native driver blocks the execution and nothing else can be done **until a message arrives to the consumer**. 

We decided that it was worth to adapt the library into the framework, which greatly impacted the structure of the consumers and queue drivers. Because these classes were designed with a sync protocol in mind (STOMP, in this case) they didn't fit the asynchronous nature of AMQP, prompting for a significant redesign **of all the interfaces and abstractions involved** - which ultimately drove us to break backwards compatibility and think in the 2.0 version of the framework.   

## Consequences

* The driver will consume from RabbitMQ without blocking PHP's execution.
* Posix signals will be delivered and acknowledged by the workers no matter what driver is being used.  
* php-amqp-lib will be a development dependency of this bundle.
* Reponse times will be lower when using the AMQP driver
* `QueueDriverInterface` will be broken in two, to accomodate the different needs of sync and async drivers.

For the full list of changes, see the `UPGRADE-2.0.md` document.

## Metadata
Authors: @bruno.souza, @andres.rey, @david.camprubi

People involved: @arthur.thevenet

## Measurements
* Benchmark available at: `Docs/architecture/benchmarks`.
