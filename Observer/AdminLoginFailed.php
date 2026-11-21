<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use StorvixLabs\AdminActionLog\Model\ActionLogger;
use StorvixLabs\AdminActionLog\Model\ActorResolver;
use StorvixLabs\AdminActionLog\Model\Config;

/**
 * Records the attempted username and IP. The password is never available here and never stored.
 */
class AdminLoginFailed implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ActionLogger $actionLogger
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isLogLogins()) {
            return;
        }
        $username = (string)$observer->getEvent()->getData('user_name');

        $this->actionLogger->write(
            ['user_type' => ActorResolver::TYPE_ADMIN, 'user_id' => null, 'username' => $username],
            Area::AREA_ADMINHTML,
            ActionLogger::ACTION_LOGIN_FAILED,
            'Admin Login',
            null,
            $username
        );
    }
}
