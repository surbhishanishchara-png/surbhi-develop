<?php
declare(strict_types=1);
namespace Surbhi\CurrencyConverter\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Index controller class
 */
class Index extends Action
{
    /**
     * Index constructor
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        private PageFactory $pageFactory
    ){
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->pageFactory->create();
    }
}
