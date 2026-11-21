<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use StorvixLabs\AdminActionLog\Model\LogReader;

class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'StorvixLabs_AdminActionLog::log';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly LogReader $logReader
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('id');
        if (!$this->logReader->get($id)) {
            $this->messageManager->addErrorMessage(__('This log entry no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $page = $this->pageFactory->create();
        $page->setActiveMenu('StorvixLabs_AdminActionLog::log_grid');
        $page->getConfig()->getTitle()->prepend(__('Log Entry #%1', $id));

        return $page;
    }
}
