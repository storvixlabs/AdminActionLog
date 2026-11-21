<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ViewAction extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = (string)$this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!empty($item['log_id'])) {
                $item[$name]['view'] = [
                    'href' => $this->urlBuilder->getUrl('storvixlabs_actionlog/log/view', ['id' => $item['log_id']]),
                    'label' => __('View'),
                ];
            }
        }
        unset($item);

        return $dataSource;
    }
}
