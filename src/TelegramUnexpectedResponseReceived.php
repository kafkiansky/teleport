<?php

declare(strict_types=1);

namespace Teleport;

final class TelegramUnexpectedResponseReceived extends \Exception
{
    public ?string $response = null;

    public function withResponse(string $response): self
    {
        $this->response = $response;

        return $this;
    }
}
