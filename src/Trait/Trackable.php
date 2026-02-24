<?php

namespace Remyb98\ObjectTracker\Trait;

use DateTimeInterface;
use ReflectionClass;
use ReflectionProperty;
use Remyb98\ObjectTracker\Attribute\Track;
use UnitEnum;

trait Trackable
{
    private array $originalData = [];

    public function snapshot(): void
    {
        $this->originalData = $this->getTrackedValues();
    }

    public function getChanges(): array
    {
        $changes = [];
        $currentState = $this->getTrackedValues();

        foreach ($currentState as $prop => $value) {
            $before = $this->originalData[$prop] ?? null;
            if ($before !== $value) {
                $changes[$prop] = [
                    'before' => $before,
                    'after' => $value,
                ];
            }
        }

        return $changes;
    }

    public function commit(): void
    {
        $this->snapshot();
    }

    private function getTrackedValues(): array
    {
        $reflection = new ReflectionClass($this);
        $data = [];

        do {
            foreach ($reflection->getProperties() as $property) {
                $trackAttribute = $this->getTrackAttribute($property);
                if ($trackAttribute) {
                    $alias = $trackAttribute->alias ?? $property->getName();
                    $data[$alias] = $this->formatValue(
                        $property->getValue($this),
                        $trackAttribute
                    );
                }
            }
        } while ($reflection = $reflection->getParentClass());

        return $data;
    }

    private function getTrackAttribute(ReflectionProperty $property): ?Track
    {
        $attribute = $property->getAttributes(Track::class);
        return count($attribute) > 0 ? $attribute[0]->newInstance() : null;
    }

    private function formatValue(mixed $value, Track $attribute): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_object($value)) {
            if ($attribute->display) {
                $reflection = new ReflectionClass($value);
                if ($reflection->hasProperty($attribute->display)) {
                    return $reflection->getProperty($attribute->display)->getValue($value);
                }
            }

            if (method_exists($value, '__toString')) {
                return $value->__toString();
            }

            return sprintf('[%s]', get_class($value));
        }

        return $value;
    }
}
