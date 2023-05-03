<?php

declare(strict_types=1);

namespace Teleport;

final class ListenOptions implements \JsonSerializable
{
    public const DEFAULT_OFFSET = 0;

    public const DEFAULT_LIMIT = 100;

    public const DEFAULT_TIMEOUT = 10;

    /**
     * @param int<min, max> $offset
     * @param int<1, 100> $limit
     * @param int<0, max> $timeout
     * @param list<non-empty-string> $allowedUpdates
     */
    public function __construct(
        public readonly int $offset = self::DEFAULT_OFFSET,
        public readonly int $limit = self::DEFAULT_LIMIT,
        public readonly int $timeout = self::DEFAULT_TIMEOUT,
        public readonly array $allowedUpdates = [],
    ) {
    }

    /**
     * @param int<min, max> $offset
     */
    public function withOffset(int $offset): ListenOptions
    {
        return new self(
            $offset,
            $this->limit,
            $this->timeout,
            $this->allowedUpdates,
        );
    }

    /**
     * @return array{
     *     offset: int,
     *     limit: int<1, 100>,
     *     timeout: int<0, max>,
     *     allowed_updates: list<non-empty-string>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'offset' => $this->offset,
            'limit' => $this->limit,
            'timeout' => $this->timeout,
            'allowed_updates' => $this->allowedUpdates,
        ];
    }
}
