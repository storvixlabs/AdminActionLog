<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Plugin;

use Magento\Catalog\Model\Product\Action;
use Psr\Log\LoggerInterface;
use StorvixLabs\AdminActionLog\Model\ActionLogger;
use StorvixLabs\AdminActionLog\Model\ValueNormalizer;

/**
 * Catalog > Products > Actions > "Update attributes" writes straight to the database,
 * so no model save events fire. Log the new values and the affected products here.
 */
class ProductMassUpdatePlugin
{
    private const MAX_IDS_LISTED = 100;

    public function __construct(
        private readonly ActionLogger $actionLogger,
        private readonly ValueNormalizer $normalizer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterUpdateAttributes(Action $subject, $result, $productIds, $attrData, $storeId)
    {
        try {
            $context = $this->actionLogger->getContext();
            if ($context === null) {
                return $result;
            }

            $productIds = array_map('intval', (array)$productIds);
            $changes = [];
            foreach ((array)$attrData as $code => $value) {
                $new = $this->normalizer->normalize($value);
                if ($new === null) {
                    continue;
                }
                $changes[] = [
                    'field' => (string)$code,
                    'old' => '(not recorded for mass updates)',
                    'new' => $this->normalizer->isSensitive((string)$code)
                        ? ValueNormalizer::MASK
                        : $this->normalizer->truncate($new),
                ];
            }

            $listed = array_slice($productIds, 0, self::MAX_IDS_LISTED);
            $changes[] = [
                'field' => 'product_ids',
                'old' => '',
                'new' => implode(', ', $listed)
                    . (count($productIds) > self::MAX_IDS_LISTED ? sprintf(' ... (+%d more)', count($productIds) - self::MAX_IDS_LISTED) : ''),
            ];

            $this->actionLogger->write(
                $context['actor'],
                $context['area'],
                ActionLogger::ACTION_MASS_UPDATE,
                'Product',
                null,
                sprintf('%d product(s), store %d', count($productIds), (int)$storeId),
                $changes
            );
        } catch (\Throwable $e) {
            $this->logger->error('StorvixLabs Admin Action Log: mass update log failed', ['exception' => $e]);
        }

        return $result;
    }
}
