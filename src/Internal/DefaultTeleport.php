<?php

declare(strict_types=1);

namespace Teleport\Internal;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\Http\Client\HttpException;
use Amp\NullCancellation;
use Amp\SignalCancellation;
use Amp\Sync\Channel;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Modifier\CamelCaseKeys;
use CuyZ\Valinor\Mapper\TreeMapper;
use Psr\Log\LoggerInterface;
use Teleport\ListenOptions;
use Teleport\Request;
use Teleport\TelegramRequestFailed;
use Teleport\TelegramUnexpectedResponseReceived;
use Teleport\Teleport;
use Amp\Sync;
use Teleport\Update;
use function Amp\async;

/**
 * @internal
 */
final class DefaultTeleport implements Teleport
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TreeMapper $mapper,
        private readonly Requester $requester,
    ) {
    }

    /**
     * @template TReturn of object
     *
     * @param Request<TReturn> $request
     *
     * @throws TelegramRequestFailed
     * @throws TelegramUnexpectedResponseReceived
     *
     * @return TReturn
     */
    public function request(
        Request $request,
        Cancellation $cancellation = new NullCancellation(),
    ): object {
        /** @var TReturn */
        return $this->invokeRequest(
            $request->returnType(),
            $request->endpoint(),
            $request,
            $cancellation,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function listen(
        ListenOptions $options = new ListenOptions(
            offset: ListenOptions::DEFAULT_OFFSET,
            limit: ListenOptions::DEFAULT_LIMIT,
            timeout: ListenOptions::DEFAULT_TIMEOUT,
        ),
        Cancellation $cancellation = new SignalCancellation(
            [\SIGINT, \SIGTERM],
        ),
    ): Channel {
        [$inner, $outer] = Sync\createChannelPair();

        async(function () use ($inner, $options, $cancellation): void {
            while (false === $cancellation->isRequested()) {
                /** @var list<Update> $updates */
                $updates = $this->invokeRequest(
                    'list<'.Update::class.'>',
                    'getUpdates',
                    $options,
                    $cancellation,
                );

                foreach ($updates as $update) {
                    $inner->send(
                        $update,
                    );

                    $options = $options->withOffset(
                        $update->updateId + 1,
                    );
                }
            }

            $inner->close();
        });

        return $outer;
    }

    /**
     * @param class-string|non-empty-string $signature
     * @param non-empty-string $path
     *
     * @throws TelegramUnexpectedResponseReceived
     * @throws TelegramRequestFailed
     *
     * @return mixed
     */
    private function invokeRequest(
        string $signature,
        string $path,
        \JsonSerializable $payload,
        Cancellation $cancellation,
    ): mixed {
        $this->logger->debug('An request to the endpoint "{endpoint}" with the payload "{payload}" will be executed.', [
            'endpoint' => $path,
            'payload' => $payload,
        ]);

        try {
            $response = $this->requester->request(
                $path,
                $payload,
                $cancellation,
            );

            $contents = $response->getBody()->buffer();
        } catch (BufferException|HttpException|StreamException $e) {
            $message = \sprintf('Telegram request error: "%s".', $e->getMessage());

            throw new TelegramRequestFailed(
                $message,
                (int) $e->getCode(),
                $e,
            );
        }

        if ('' === $contents) {
            $message = \sprintf('The response for method "%s" is empty.', $path);

            throw new TelegramUnexpectedResponseReceived(
                $message,
            );
        }

        try {
            /**
             * @var array{
             *     ok?:bool,
             *     result?:array<non-empty-string, mixed>,
             *     description?:string,
             * } $payload
             */
            $payload = \json_decode(
                $contents,
                true,
                flags: \JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            $message = \sprintf('The response for method "%s" contains invalid json contents: "%s".', $path, $e->getMessage());

            throw new TelegramUnexpectedResponseReceived(
                $message,
                previous: $e,
            );
        }

        if (false === ($payload['ok'] ?? false)) {
            $message = \sprintf('The response for method "%s" was not succeeded.', $path);

            throw (new TelegramUnexpectedResponseReceived(
                $message,
            ))->withResponse($payload['description'] ?? $payload['result'] ?? '');
        }

        $this->logger->debug('A request for an endpoint "{endpoint}" with the payload "{payload}" returned the response "{response}".', [
            'endpoint' => $path,
            'payload' => $payload,
            'response' => $payload['result'] ?? [],
        ]);

        try {
            return $this->mapper->map(
                $signature,
                new CamelCaseKeys($payload['result'] ?? []),
            );
        } catch (MappingError $e) {
            $message = \sprintf('The response for method "%s" cannot be mapped to return type "%s": "%s".', $path, $signature, $e->getMessage());

            throw new TelegramUnexpectedResponseReceived(
                $message,
                previous: $e,
            );
        }
    }
}
