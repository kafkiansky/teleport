<?php

declare(strict_types=1);

namespace Teleport;

use Amp\Cancellation;
use Amp\SignalCancellation;
use Amp\Sync\Channel;

interface UpdatesListener
{
    /**
     * @return Channel<Update, Update>
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
    ): Channel;
}
