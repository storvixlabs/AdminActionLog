<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use StorvixLabs\AdminActionLog\Model\LogReader;
use StorvixLabs\AdminActionLog\Model\Source\Action;

class LogView implements ArgumentInterface
{
    private ?array $entry = null;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly LogReader $logReader,
        private readonly TimezoneInterface $timezone,
        private readonly UrlInterface $urlBuilder,
        private readonly Action $actionSource
    ) {
    }

    public function getEntry(): ?array
    {
        if ($this->entry === null) {
            $this->entry = $this->logReader->get((int)$this->request->getParam('id')) ?? [];
        }

        return $this->entry ?: null;
    }

    /** @return array<int, array{field:string, old:string, new:string}> */
    public function getChanges(): array
    {
        $raw = $this->getEntry()['changes'] ?? null;
        $changes = $raw ? json_decode((string)$raw, true) : [];

        return is_array($changes) ? $changes : [];
    }

    public function getFormattedDate(): string
    {
        $date = $this->getEntry()['created_at'] ?? null;

        return $date ? $this->timezone->formatDateTime(new \DateTime($date, new \DateTimeZone('UTC')), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM) : '';
    }

    public function getActionLabel(): string
    {
        $action = (string)($this->getEntry()['action'] ?? '');
        foreach ($this->actionSource->toOptionArray() as $option) {
            if ($option['value'] === $action) {
                return (string)$option['label'];
            }
        }

        return $action;
    }

    public function getBackUrl(): string
    {
        return $this->urlBuilder->getUrl('storvixlabs_actionlog/log/index');
    }
}
