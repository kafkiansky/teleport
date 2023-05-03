<?php

declare(strict_types=1);

namespace Teleport;

/**
 * @param non-empty-string $envName
 *
 * @throws \InvalidArgumentException
 */
function fromEnv(
    string $envName = 'TELEPORT_API_TOKEN',
): TeleportFactory {
    $token = getenv($envName);

    if (false === \is_string($token)) {
        throw new \InvalidArgumentException("The environment '$envName' is not a string.");
    }

    return factory(
        new Token($token),
    );
}

function factory(
    Token $token,
): TeleportFactory {
    return new TeleportFactory(
        $token,
    );
}
