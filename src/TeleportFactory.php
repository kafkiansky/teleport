<?php

declare(strict_types=1);

namespace Teleport;

use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClientBuilder;
use CuyZ\Valinor\MapperBuilder;
use League\Uri\Http;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class TeleportFactory
{
    private const DEFAULT_HTTP_URI = 'https://api.telegram.org';

    private ?LoggerInterface $logger = null;

    private ?UriInterface $uri = null;

    private ?DelegateHttpClient $httpClient = null;

    public function __construct(
        private readonly Token $token,
    ) {
    }

    public function withLogger(LoggerInterface $logger): TeleportFactory
    {
        $factory = clone $this;
        $factory->logger = $logger;

        return $factory;
    }

    public function withUri(UriInterface $uri): TeleportFactory
    {
        $factory = clone $this;
        $factory->uri = $uri;

        return $factory;
    }

    public function withHttpClient(DelegateHttpClient $httpClient): TeleportFactory
    {
        $factory = clone $this;
        $factory->httpClient = $httpClient;

        return $factory;
    }

    public function create(): Teleport
    {
        return new Internal\DefaultTeleport(
            $this->logger ?: new NullLogger(),
            (new MapperBuilder())->mapper(),
            new Internal\Requester(
                $this->token,
                $this->uri ?: Http::createFromString(self::DEFAULT_HTTP_URI),
                $this->httpClient ?: HttpClientBuilder::buildDefault(),
            ),
        );
    }
}
