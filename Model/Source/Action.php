<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use StorvixLabs\AdminActionLog\Model\ActionLogger;

class Action implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => ActionLogger::ACTION_CREATE, 'label' => __('Create')],
            ['value' => ActionLogger::ACTION_UPDATE, 'label' => __('Update')],
            ['value' => ActionLogger::ACTION_DELETE, 'label' => __('Delete')],
            ['value' => ActionLogger::ACTION_MASS_UPDATE, 'label' => __('Mass Update')],
            ['value' => ActionLogger::ACTION_LOGIN, 'label' => __('Login')],
            ['value' => ActionLogger::ACTION_LOGIN_FAILED, 'label' => __('Failed Login')],
        ];
    }
}
