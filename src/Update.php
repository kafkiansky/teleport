<?php

declare(strict_types=1);

namespace Teleport;

final class Update
{
    public function __construct(
        public readonly int $updateId,
    ) {
    }
}
