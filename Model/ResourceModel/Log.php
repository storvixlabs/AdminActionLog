<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use StorvixLabs\AdminActionLog\Model\ActionLogger;

class Log extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(ActionLogger::TABLE, 'log_id');
    }
}
