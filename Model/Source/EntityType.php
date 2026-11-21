<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use StorvixLabs\AdminActionLog\Model\EntityRegistry;

class EntityType implements OptionSourceInterface
{
    public function __construct(
        private readonly EntityRegistry $registry
    ) {
    }

    public function toOptionArray(): array
    {
        $labels = array_unique(array_merge($this->registry->getLabels(), ['Admin Login']));
        sort($labels);

        return array_map(static fn(string $label): array => ['value' => $label, 'label' => __($label)], $labels);
    }
}
