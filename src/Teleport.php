<?php

declare(strict_types=1);

namespace Teleport;

use Amp\Cancellation;
use Amp\NullCancellation;

interface Teleport extends UpdatesListener
{
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
    ): object;
}
