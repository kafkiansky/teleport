<?php

declare(strict_types=1);

namespace Teleport;

final class Token implements \Stringable
{
    /** @psalm-var non-empty-string */
    private readonly string $value;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $value)
    {
        if ('' === $value) {
            throw new \InvalidArgumentException('API token must not be empty string.');
        }

        if (false === (bool) \preg_match('/^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$/', $value)) {
            throw new \InvalidArgumentException('API token must match the "/^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$/" pattern.');
        }

        $this->value = $value;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
