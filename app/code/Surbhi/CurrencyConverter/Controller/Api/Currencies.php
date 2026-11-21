<?php

declare(strict_types=1);

namespace Surbhi\CurrencyConverter\Controller\Api;

/**
 * Currencies controller class
 */
class Currencies extends AbstractApi
{
    public function execute()
    {
        return $this->respond(fn() => $this->client->currencies());
    }
}
