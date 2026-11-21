<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\User\Model\User;
use StorvixLabs\AdminActionLog\Model\ActionLogger;
use StorvixLabs\AdminActionLog\Model\ActorResolver;
use StorvixLabs\AdminActionLog\Model\Config;

class AdminLoginSuccess implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ActionLogger $actionLogger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $user = $observer->getEvent()->getData('user');
        if (!$this->config->isLogLogins() || !$user instanceof User) {
            return;
        }

        $this->actionLogger->write(
            [
                'user_type' => ActorResolver::TYPE_ADMIN,
                'user_id' => (int)$user->getId(),
                'username' => (string)$user->getUserName(),
            ],
            Area::AREA_ADMINHTML,
            ActionLogger::ACTION_LOGIN,
            'Admin Login',
            (string)$user->getId(),
            (string)$user->getUserName()
        );
    }
}
