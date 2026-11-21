<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

/**
 * Tracked entity definitions, configured in di.xml.
 * Each entry: class, label, title_field, optional ignore[] of field names.
 */
class EntityRegistry
{
    public const CODE_CONFIG = 'config';

    /**
     * @param array<string, array{class:string, label:string, title_field?:string, ignore?:array<string,string>}> $entities
     */
    public function __construct(
        private readonly array $entities = []
    ) {
    }

    /**
     * @return array{code:string, class:string, label:string, title_field:string, ignore:string[]}|null
     */
    public function match(object $object): ?array
    {
        foreach ($this->entities as $code => $entity) {
            $class = $entity['class'] ?? '';
            // instanceof with a string never autoloads, so classes from disabled modules are safe here.
            if ($class !== '' && $object instanceof $class) {
                return [
                    'code' => (string)$code,
                    'class' => $class,
                    'label' => (string)($entity['label'] ?? $code),
                    'title_field' => (string)($entity['title_field'] ?? ''),
                    'ignore' => array_values($entity['ignore'] ?? []),
                ];
            }
        }

        return null;
    }

    /** @return string[] Entity type labels, for grid filters */
    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->entities as $code => $entity) {
            $labels[] = (string)($entity['label'] ?? $code);
        }

        return $labels;
    }
}
