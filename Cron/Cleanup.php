<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Cron;

use Magento\Framework\App\ResourceConnection;
use StorvixLabs\AdminActionLog\Model\ActionLogger;
use StorvixLabs\AdminActionLog\Model\Config;

/**
 * Deletes entries older than the retention period, in small batches to avoid long table locks.
 */
class Cleanup
{
    private const BATCH = 5000;
    private const MAX_BATCHES = 200;

    public function __construct(
        private readonly Config $config,
        private readonly ResourceConnection $resource
    ) {
    }

    public function execute(): void
    {
        $days = $this->config->getRetentionDays();
        if ($days <= 0) {
            return;
        }

        $connection = $this->resource->getConnection();
        $table = $connection->quoteIdentifier($this->resource->getTableName(ActionLogger::TABLE));
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * 86400);

        for ($i = 0; $i < self::MAX_BATCHES; $i++) {
            $deleted = $connection->query(
                sprintf('DELETE FROM %s WHERE created_at < ? ORDER BY log_id LIMIT %d', $table, self::BATCH),
                [$cutoff]
            )->rowCount();
            if ($deleted < self::BATCH) {
                break;
            }
        }
    }
}
