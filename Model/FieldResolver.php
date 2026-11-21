<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Tells real, persisted fields apart from transient flags that controllers put on models
 * (form keys, "use default" markers, save_rewrites_history, ...), so diffs only show real changes.
 */
class FieldResolver
{
    /** @var array<string, array<string, bool>> */
    private array $tableColumns = [];

    public function isPersisted(AbstractModel $object, string $field): bool
    {
        $resource = $object->getResource();

        if ($resource instanceof AbstractEntity) {
            return (bool)$resource->getAttribute($field);
        }

        if ($resource instanceof AbstractDb) {
            $table = $resource->getMainTable();
            if (!isset($this->tableColumns[$table])) {
                $columns = $resource->getConnection()->describeTable($table);
                $this->tableColumns[$table] = array_fill_keys(array_keys($columns), true);
            }

            return isset($this->tableColumns[$table][$field]);
        }

        return true;
    }
}
