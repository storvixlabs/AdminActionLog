<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Framework\App\ResourceConnection;

class LogReader
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function get(int $logId): ?array
    {
        if ($logId <= 0) {
            return null;
        }
        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName(ActionLogger::TABLE))
                ->where('log_id = ?', $logId)
        );

        return $row ?: null;
    }
}
