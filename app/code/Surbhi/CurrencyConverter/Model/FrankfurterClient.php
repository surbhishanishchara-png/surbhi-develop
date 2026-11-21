<?php

declare(strict_types=1);

namespace Surbhi\CurrencyConverter\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use RuntimeException;

/**
 * Class for Frankfurter API calls
 */
class FrankfurterClient
{
    /**
     * Constant for frankfurter API URL
     */
    private const BASE_URL = 'https://api.frankfurter.dev/v2';

    /**
     * Constant for cache tag
     */
    private const CACHE_TAG = 'SURBHI_FRANKFURTER_';

    /**
     * FrankfurterClient Constructor
     * @param CurlFactory $curlFactory
     * @param CacheInterface $cache
     * @param Json $json
     */
    public function __construct(
        private CurlFactory $curlFactory,
        private CacheInterface $cache,
        private Json $json
    ) {}

    /**
     * Function to get currency conversion endpoint call
     * @return array
     */
    public function currencies(): array
    {
        return $this->get('/currencies', 86400);
    }

    /**
     * Function to get currency conversion rate api call
     * @param string $base
     * @param string $quote
     * @return array
     */
    public function rate(string $base, string $quote): array
    {
        $this->validateCode($base);
        $this->validateCode($quote);
        if ($base === $quote) {
            return ['date' => date('Y-m-d'), 'base' => $base, 'quote' => $quote, 'rate' => 1];
        }
        return $this->get('/rate/' . strtolower($base) . '/' . strtolower($quote), 3600);
    }

    /**
     * Function to get currency conversion history api call for chart
     * @param string $base
     * @param string $quote
     * @param int $days
     * @return array
     */
    public function history(string $base, string $quote, int $days): array
    {
        $this->validateCode($base);
        $this->validateCode($quote);
        if ($base === $quote) {
            return [['date' => date('Y-m-d'), 'base' => $base, 'quote' => $quote, 'rate' => 1]];
        }
        $days = in_array($days, [30, 90, 180, 365], true) ? $days : 30;
        $end = new \DateTimeImmutable('today');
        $start = $end->modify('-' . $days . ' days');
        $path = '/rates?base=' . rawurlencode($base) . '&quotes=' . rawurlencode($quote)
            . '&from=' . $start->format('Y-m-d') . '&to=' . $end->format('Y-m-d');
        return $this->get($path, 86400);
    }

    /**
     * Function to validate currency code
     * @param string $code
     */
    private function validateCode(string $code): void
    {
        if (!preg_match('/^[A-Z0-9]{3,5}$/i', $code)) {
            throw new RuntimeException('Invalid currency code.');
        }
    }

    /**
     * Function for API call and cache the response.
     * @param string $path
     * @param int $time
     * @return array
     */
    private function get(string $path, int $time): array
    {
        $key = self::CACHE_TAG . sha1($path);
        $cached = $this->cache->load($key);
        if ($cached !== false) {
            return $this->json->unserialize($cached);
        }

        $client = $this->curlFactory->create();
        $client->setTimeout(12);
        $client->setHeaders(['Accept' => 'application/json', 'User-Agent' => 'Magento-Surbhi-CurrencyConverter/1.0']);
        $client->get(self::BASE_URL . $path);
        $status = (int)$client->getStatus();
        $body = $client->getBody();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Currency service returned HTTP ' . $status . '.');
        }
        $data = $this->json->unserialize($body);
        if (!is_array($data)) {
            throw new RuntimeException('Unexpected response from currency service.');
        }
        $this->cache->save($this->json->serialize($data), $key, [], $time);
        return $data;
    }
}
