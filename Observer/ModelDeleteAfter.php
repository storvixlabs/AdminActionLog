<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;
use StorvixLabs\AdminActionLog\Model\Tracker;

class ModelDeleteAfter implements ObserverInterface
{
    public function __construct(
        private readonly Tracker $tracker
    ) {
    }

    public function execute(Observer $observer): void
    {
        $object = $observer->getEvent()->getData('object');
        if ($object instanceof AbstractModel) {
            $this->tracker->afterDelete($object);
        }
    }
}
