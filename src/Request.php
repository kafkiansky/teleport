<?php

declare(strict_types=1);

namespace Teleport;

/**
 * @template TReturn
 */
interface Request extends \JsonSerializable
{
    /**
     * @return non-empty-string
     */
    public function endpoint(): string;

    /**
     * @return class-string<TReturn>
     */
    public function returnType(): string;
}
