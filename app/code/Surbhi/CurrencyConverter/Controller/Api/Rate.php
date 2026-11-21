<?php

declare(strict_types=1);

namespace Surbhi\CurrencyConverter\Controller\Api;

/**
 * Controller class for Convertion rate.
 */
class Rate extends AbstractApi
{
    public function execute()
    {
        return $this->respond(
            fn() => $this->client->rate(
                $this->code('base', 'USD'), $this->code('quote', 'EUR')
            )
        );
    }
}
