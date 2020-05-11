# 2. Deprecate the response fallback on the RestConfigurableProducer class

Date: 2020-05-08

## Status

Accepted

## Context

Currently the RestConfigurableProducer will pass the payload received from the target system to the serializer. If this one fails, it will catch the exception, fallback to the original response, **and continue the execution**.

This was done to allow certain APIs to return a non-deserializable response while still being able to continue the execution, either **by ignoring the payload** (i.e.: when the response code is enough) or to evaluate it to something else.

This makes the evaluator vulnerable, as it has no guarantee that what it will expect is something **that can be evaluated**.

Due to the fallback is present by default, one endpoint **could randomly fail** if the target system decides to send back a response in a format that is not expected by the application.

## Decision

We decided to deprecate the response fallback in this version and remove it **in the next major version**. 

A new parameter was introduced in the `RestConfigurableProducer` (`response_format`) that will define the expected response format, and this format is passed to the deserialize function to instantiate the appropriate visitor. For example, a PlainTextVisitor could be implemented to process non-JSON responses while still producing **a evaluable object**. By default, if `response_format` is not setup, `encoding` will be passed.

## Consequences

In order to introduce this change in a backwards compatible way, a new boolean option was introduced in the `RestConfigurableProtocol` (`response_fallback`) that will prevent the fallback. Setting it to `false` **will throw an exception** when the payload cannot be deserialized.

## Metadata
Authors: @andres.rey

People involved: @david.camprubi
