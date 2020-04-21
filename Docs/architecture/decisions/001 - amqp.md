# 1. Support to AMQP protocol with the php-amqp-lib

Date: 2020-04-21

## Status

Accepted

## Context

There was a need to consume the messages in a non-blocking way for our application. In the former scenario of the application
we didn't have an AMQP driver that works in this way. 

We also needed a support for posix signals by the AMQP driver to be able to kill the workers in a gracefully way, if we had some necessity.

The possible solutions were:

  -  To discover a way to implement the non-blocking and posix signals by the native driver;
  -  To use an AMQP lib that offers the support to these functionalities, like the php-amqp-lib;

**Native Driver**

*Pros*: 
- Works a bit faster than using the lib;
- Already implemented in the application;

*Cons*: 
- Did not offer support to consume messages in a non-blocking way;  
- Does not support posix signals;

**PHP-AMQP-LIB**

*Pros*: 
- Is the standard lib to work with RabbitMQ in the documentation of the tool;
- Offer the possibility to consume messages in a non-blocking way;
- Offer the possibility to support the posix signals;
- Offer options like: heartbeat, multiple channels and multiple hosts;

*Cons*:
- Difficult to implement due to the great refactory needed in our application;
- Different approach demands more time to learn and implement;

## Decision

We decided to use PHP-AMQP-LIB. 

After spent some time studying a way to try to implement consume of messages in a non-blocking way with the native 
driver, we realize that it was not viable. 

We decided that was faster and better to use a lib that already offer the functionalities that we needed and don't
reinvent the wheel. So, we spent a lot of time studying and learning how to implement the lib and to adapt this in our 
application. This decision impacted a lot the application and became necessary a great refactory to do the adaptation of
the lib and brought a series of changes giving the origin to the version 2.0 of the bundle.

To see all the changes related to the application and more details, see the UPGRADE-2.0.md document.

## Consequences

The consumer has the approach to work in non-blocking way.

The AMQP driver is listening and attending property the posix signal. 

The php-amqp-lib is a dependency of this bundle if you have plans to use AMQP.

The exception handler and serialization features was refactored to adapt the work with the new lib.

The queue drivers has more options now which can be defined by parameters.

The AMQP driver shows a performance gain in an average about 13% in relation to Stomp driver.

The QueueDriverInterface has the main methods to work with both drivers. There are 2 new interfaces to work with sync flows (SyncQueueDriverInterface) and async flows (AsyncQueueDriverInterface).

## Metadata
Author: @bruno.souza, @andres.rey, @david.camprubi

People involved: @arthur.thevenet

## Measurements
* Benchmark available at: 