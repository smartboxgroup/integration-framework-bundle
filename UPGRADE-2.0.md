# UPGRADE FROM 1.x to 2.0

## UsesGuzzleHttpClient Trait & HttpClientInterface Interface

* `HttpClientInterface` interface was added and `RestConfigurableProducer` implements it via the `UsesGuzzleHttpClient` trait. The interface adds an extra function `addHandler()` that allows users to add extra Handlers to the `ClientInterface`. By default a new handler is added (`Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Middleware`) while building the container fixing an bug in Guzzle that incorrectly splits multibyte charaters in two when truncating the response body. If you're already tweaking the Guzzle client used by the RestConfigurableProducer, you'll have to check if this new handler overrides it.
