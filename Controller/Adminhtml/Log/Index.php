<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'StorvixLabs_AdminActionLog::log';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('StorvixLabs_AdminActionLog::log_grid');
        $page->getConfig()->getTitle()->prepend(__('Admin Action Log'));

        return $page;
    }
}
