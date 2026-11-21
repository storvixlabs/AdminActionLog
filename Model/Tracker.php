<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * Turns model save/delete events into log entries.
 * Diffs are computed before save (original data is still intact) and written after save,
 * inside the same transaction — so a rolled-back save leaves no log entry.
 */
class Tracker
{
    /** @var array<int, array{action:string, changes:array}> keyed by spl_object_id */
    private array $pending = [];

    public function __construct(
        private readonly ActionLogger $actionLogger,
        private readonly EntityRegistry $registry,
        private readonly DiffBuilder $diffBuilder,
        private readonly ValueNormalizer $normalizer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function beforeSave(AbstractModel $object): void
    {
        try {
            $entity = $this->registry->match($object);
            if ($entity === null || $entity['code'] === EntityRegistry::CODE_CONFIG) {
                return;
            }
            if ($this->actionLogger->getContext() === null) {
                return;
            }

            $isNew = $object->isObjectNew() || !$object->getId();
            $changes = $isNew ? [] : $this->diffBuilder->build($object, $entity);
            if (!$isNew && !$changes) {
                return;
            }

            $this->pending[spl_object_id($object)] = [
                'action' => $isNew ? ActionLogger::ACTION_CREATE : ActionLogger::ACTION_UPDATE,
                'changes' => $changes,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('StorvixLabs Admin Action Log: beforeSave failed', ['exception' => $e]);
        }
    }

    public function afterSave(AbstractModel $object): void
    {
        try {
            $entity = $this->registry->match($object);
            if ($entity === null) {
                return;
            }
            $context = $this->actionLogger->getContext();
            if ($context === null) {
                unset($this->pending[spl_object_id($object)]);
                return;
            }

            if ($entity['code'] === EntityRegistry::CODE_CONFIG && $object instanceof Value) {
                $this->logConfigSave($object, $entity, $context);
                return;
            }

            $key = spl_object_id($object);
            if (!isset($this->pending[$key])) {
                return;
            }
            $pending = $this->pending[$key];
            unset($this->pending[$key]);

            $this->actionLogger->write(
                $context['actor'],
                $context['area'],
                $pending['action'],
                $entity['label'],
                (string)$object->getId(),
                $this->getTitle($object, $entity),
                $pending['changes']
            );
        } catch (\Throwable $e) {
            $this->logger->error('StorvixLabs Admin Action Log: afterSave failed', ['exception' => $e]);
        }
    }

    public function afterDelete(AbstractModel $object): void
    {
        try {
            $entity = $this->registry->match($object);
            if ($entity === null) {
                return;
            }
            $context = $this->actionLogger->getContext();
            if ($context === null) {
                return;
            }

            if ($entity['code'] === EntityRegistry::CODE_CONFIG && $object instanceof Value) {
                // "Use Default" / "Use Website" in Stores > Configuration deletes the scoped value.
                $old = $this->isSensitiveConfig($object)
                    ? ValueNormalizer::MASK
                    : $this->normalizer->truncate((string)$this->normalizer->normalize($object->getOrigData('value') ?? $object->getValue()));
                $this->actionLogger->write(
                    $context['actor'],
                    $context['area'],
                    ActionLogger::ACTION_DELETE,
                    $entity['label'],
                    $this->getConfigScope($object),
                    (string)$object->getPath(),
                    [['field' => (string)$object->getPath(), 'old' => $old, 'new' => '(inherit from parent scope)']]
                );
                return;
            }

            $this->actionLogger->write(
                $context['actor'],
                $context['area'],
                ActionLogger::ACTION_DELETE,
                $entity['label'],
                (string)($object->getOrigData($object->getIdFieldName()) ?? $object->getId()),
                $this->getTitle($object, $entity)
            );
        } catch (\Throwable $e) {
            $this->logger->error('StorvixLabs Admin Action Log: afterDelete failed', ['exception' => $e]);
        }
    }

    private function logConfigSave(Value $object, array $entity, array $context): void
    {
        $value = $object->getValue();

        if ($this->isEncrypted($object) && ($value === null || preg_match('/^\*+$/', (string)$value))) {
            return; // Obscured field submitted unchanged.
        }

        $new = $this->normalizer->normalize($value);
        $old = (string)$object->getOldValue();
        if ($new === null || $this->normalizer->equals($old, $new)) {
            return;
        }

        $path = (string)$object->getPath();
        $sensitive = $this->isSensitiveConfig($object);

        $this->actionLogger->write(
            $context['actor'],
            $context['area'],
            ActionLogger::ACTION_UPDATE,
            $entity['label'],
            $this->getConfigScope($object),
            $path,
            [[
                'field' => $path,
                'old' => $sensitive ? ValueNormalizer::MASK : $this->normalizer->truncate($old),
                'new' => $sensitive ? ValueNormalizer::MASK . ' (changed)' : $this->normalizer->truncate($new),
            ]]
        );
    }

    private function isEncrypted(Value $object): bool
    {
        return $object instanceof Encrypted;
    }

    private function isSensitiveConfig(Value $object): bool
    {
        return $this->isEncrypted($object) || $this->normalizer->isSensitive((string)$object->getPath());
    }

    private function getConfigScope(Value $object): string
    {
        return ($object->getScope() ?: 'default') . ':' . (int)$object->getScopeId();
    }

    private function getTitle(AbstractModel $object, array $entity): ?string
    {
        $field = $entity['title_field'];
        if ($field === '') {
            return null;
        }
        $value = $object->getData($field) ?? $object->getOrigData($field);
        if ($value === null || $value === '') {
            return null;
        }
        $title = is_scalar($value) ? (string)$value : null;

        $name = $object->getData('name');
        if ($title !== null && $field !== 'name' && is_string($name) && $name !== '') {
            $title = $name . ' (' . $title . ')';
        }

        return $title;
    }
}
