<?php

declare(strict_types=1);

namespace Surbhi\CurrencyConverter\Block;

use Magento\Framework\View\Element\Template;

/**
 * Block class for currency converter
 */
class Converter extends Template
{
    /**
     * Function to get API Endpoints.
     * @return array
     */
    public function getApiUrls(): array
    {
        return [
            'currencies' => $this->getUrl('currencyconverter/api/currencies'),
            'rate' => $this->getUrl('currencyconverter/api/rate'),
            'history' => $this->getUrl('currencyconverter/api/history'),
        ];
    }
}
