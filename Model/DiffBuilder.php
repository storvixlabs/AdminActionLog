<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Builds a [field, old, new] list from a model's original and current data.
 */
class DiffBuilder
{
    private const GLOBAL_IGNORE = [
        'updated_at', 'created_at', 'update_time', 'creation_time', 'form_key', 'row_id',
    ];

    public function __construct(
        private readonly FieldResolver $fieldResolver,
        private readonly ValueNormalizer $normalizer
    ) {
    }

    /**
     * @param array{ignore:string[]} $entity
     * @return array<int, array{field:string, old:string, new:string}>
     */
    public function build(AbstractModel $object, array $entity): array
    {
        $original = $object->getOrigData();
        if (!is_array($original) || !$original) {
            $original = $this->loadOriginal($object);
        }
        if (!$original) {
            return [];
        }

        $current = $object->getData();
        $ignore = array_flip(array_merge(self::GLOBAL_IGNORE, $entity['ignore']));
        $changes = [];

        foreach ($current as $field => $newValue) {
            if (!is_string($field) || $field === '' || $field[0] === '_' || isset($ignore[$field])) {
                continue;
            }

            $new = $this->normalizer->normalize($newValue);
            $old = $this->normalizer->normalize($original[$field] ?? null);
            if ($new === null || $old === null || $this->normalizer->equals($old, $new)) {
                continue;
            }
            if (!$this->fieldResolver->isPersisted($object, $field)) {
                continue;
            }

            if ($this->normalizer->isSensitive($field)) {
                $changes[] = ['field' => $field, 'old' => ValueNormalizer::MASK, 'new' => ValueNormalizer::MASK . ' (changed)'];
                continue;
            }

            $changes[] = [
                'field' => $field,
                'old' => $this->normalizer->truncate($old),
                'new' => $this->normalizer->truncate($new),
            ];
        }

        return $changes;
    }

    /**
     * Some repositories (e.g. customers) save a freshly built model with no original data.
     * Load the stored record into a clone so we have something to compare with.
     */
    private function loadOriginal(AbstractModel $object): array
    {
        if (!$object->getId()) {
            return [];
        }

        try {
            $fresh = clone $object;
            $fresh->unsetData();
            $object->getResource()->load($fresh, $object->getId());

            return $fresh->getId() ? (array)$fresh->getData() : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
