<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use StorvixLabs\AdminActionLog\Model\ActorResolver;

class UserType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => ActorResolver::TYPE_ADMIN, 'label' => __('Admin User')],
            ['value' => ActorResolver::TYPE_INTEGRATION, 'label' => __('Integration')],
        ];
    }
}
