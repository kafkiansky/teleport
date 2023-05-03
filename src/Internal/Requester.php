<?php

declare(strict_types=1);

namespace Teleport\Internal;

use Amp\Cancellation;
use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Response;
use Psr\Http\Message\UriInterface;
use Teleport\TelegramRequestFailed;
use Teleport\Token;
use Amp\Http\Client\Request;

/**
 * @internal
 */
final class Requester
{
    public function __construct(
        private readonly Token $token,
        private readonly UriInterface $uri,
        private readonly DelegateHttpClient $httpClient,
    ) {
    }

    /**
     * @param non-empty-string $path
     *
     * @throws TelegramRequestFailed
     */
    public function request(
        string $path,
        \JsonSerializable $payload,
        Cancellation $cancellation,
    ): Response {
        $contents = json_encode($payload);

        if (false === $contents) {
            $message = \sprintf(
                'Telegram request failed while trying serialize body: "%s".',
                \json_last_error_msg(),
            );

            throw new TelegramRequestFailed(
                $message,
            );
        }

        $httpRequest = new Request(
            $this->uri
                ->withPath(sprintf('/bot%s/%s', (string) $this->token, $path)),
            'POST',
            BufferedContent::fromString(
                $contents,
                'application/json',
            ),
        );

        $httpRequest->addHeader('accept', 'application/json');

        try {
            return $this->httpClient->request(
                $httpRequest,
                $cancellation,
            );
        } catch (\Throwable $e) {
            $message = \sprintf(
                'Telegram request with uri "%s" was failed due to the error "%s".',
                $path,
                $e->getMessage(),
            );

            throw new TelegramRequestFailed(
                $message,
                previous: $e,
            );
        }
    }
}
