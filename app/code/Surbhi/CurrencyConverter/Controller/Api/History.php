<?php

declare(strict_types=1);

namespace Surbhi\CurrencyConverter\Controller\Api;

/**
 * History controller class for showing history for exchange rate and chart.
 */
class History extends AbstractApi
{
    public function execute()
    {
        return $this->respond(fn() => $this->client->history(
            $this->code('base', 'USD'),
            $this->code('quote', 'EUR'),
            (int)$this->getRequest()->getParam('days', 30)
        ));
    }
}
