<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const PREFIX = 'storvixlabs_actionlog/general/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PREFIX . 'enabled');
    }

    public function isLogLogins(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::PREFIX . 'log_logins');
    }

    public function isLogApi(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::PREFIX . 'log_api');
    }

    public function getRetentionDays(): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::PREFIX . 'retention_days'));
    }
}
