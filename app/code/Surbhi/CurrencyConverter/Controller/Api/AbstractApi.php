<?php

declare(strict_types=1);

namespace Surbhi\CurrencyConverter\Controller\Api;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Surbhi\CurrencyConverter\Model\FrankfurterClient;
use Throwable;

/**
 * AbstractApi controller class.
 */
abstract class AbstractApi extends Action
{
    /**
     * AbstractApi Constructor
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FrankfurterClient $client
     */
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        protected FrankfurterClient $client
    ) {
        parent::__construct($context);
    }

    /**
     * Function to get API response
     * @param callable $callback
     */
    protected function respond(callable $callback)
    {
        $result = $this->resultJsonFactory->create();
        try {
            return $result->setData(['success' => true, 'data' => $callback()]);
        } catch (Throwable $e) {
            return $result->setHttpResponseCode(400)->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Function for get currency code.
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function code(string $key, string $default): string
    {
        return strtoupper(trim((string)$this->getRequest()->getParam($key, $default)));
    }
}
