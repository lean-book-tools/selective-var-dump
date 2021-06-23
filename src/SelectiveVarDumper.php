<?php

declare(strict_types=1);

namespace SelectiveVarDump;

use ReflectionObject;
use ReflectionProperty;

/**
 * @see SelectiveVarDumperTest
 */
final class SelectiveVarDumper
{
    public function __construct(
        private VarDumperConfig $varDumperConfig
    ) {
    }

    public function dump(mixed $value): string
    {
        return $this->dumpValue($value, 0);
    }

    /**
     * @param array<mixed> $array
     */
    private static function isNumericallyIndexedArray(
        array $array
    ): bool {
        return count(
            array_filter(array_keys($array), fn ($key) => ! is_int(
                $key
            ))
        ) === 0;
    }

    private function dumpObject(
        object $value,
        int $currentIndentationLevel
    ): string {
        $result = 'object(' . $this->abbreviateClassNameIfNecessary(
            $value::class
        ) . ')';

        $allProperties = $this->resolvePropertyReflections($value);

        if (count($allProperties) > 0) {
            $result .= ' {' . "\n";

            foreach ($allProperties as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();
                $reflectionProperty->setAccessible(true);
                $propertyValue = $reflectionProperty->getValue(
                    $value
                );

                if ($this->shouldPropertyBeSkipped(
                    $propertyName
                )) {
                    continue;
                }

                $result .= self::indentation(
                    $currentIndentationLevel + 1
                );
                $result .= '[' . $this->dumpValue(
                    $propertyName,
                    $currentIndentationLevel
                ) . ']';
                $result .= ' => ';
                $result .= $this->dumpValue(
                    $propertyValue,
                    $currentIndentationLevel + 1
                );
                $result .= "\n";
            }

            $result .= self::indentation($currentIndentationLevel);
            $result .= '}';
        }

        return $result;
    }

    private function dumpValue(
        mixed $value,
        int $currentIndentationLevel
    ): string {
        if (is_array($value)) {
            return $this->dumpArray($value, $currentIndentationLevel);
        }

        if (is_object($value)) {
            return $this->dumpObject(
                $value,
                $currentIndentationLevel
            );
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if ($value === null) {
            return 'null';
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        throw new UnsupportedValueException(
            'Value type not supported: ' . var_export($value, true)
        );
    }

    private static function indentation(int $indentationLevel): string
    {
        return str_repeat('  ', $indentationLevel);
    }

    /**
     * @param array<mixed> $array
     */
    private function dumpArray(
        array $array,
        int $currentIndentationLevel
    ): string {
        $array = $this->cleanUpArray($array);

        $result = 'array(' . count($array) . ')';

        if (count($array) > 0) {
            $result .= ' {' . "\n";

            foreach ($array as $key => $value) {
                $result .= self::indentation(
                    $currentIndentationLevel + 1
                );
                $result .= '[' . $this->dumpValue(
                    $key,
                    $currentIndentationLevel
                ) . ']';
                $result .= ' => ';
                $result .= $this->dumpValue(
                    $value,
                    $currentIndentationLevel + 1
                );
                $result .= "\n";
            }

            $result .= self::indentation(
                $currentIndentationLevel
            ) . '}';
        }

        return $result;
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     */
    private function cleanUpArray(array $array): array
    {
        $filteredValues = array_filter(
            $array,
            function ($value): bool {
                if (! is_object($value)) {
                    return true;
                }

                return ! in_array(
                    $value::class,
                    $this->varDumperConfig->skipObjectsOfType(),
                    true
                );
            }
        );

        if (self::isNumericallyIndexedArray($filteredValues)) {
            // Reset the indexes
            return array_values($filteredValues);
        }

        return $filteredValues;
    }

    private function shouldPropertyBeSkipped(
        string $propertyName
    ): bool {
        if (count(
            $this->varDumperConfig->includeProperties()
        ) > 0 && ! in_array(
            $propertyName,
            $this->varDumperConfig->includeProperties(),
            true
        )) {
            return true;
        }

        return in_array(
            $propertyName,
            $this->varDumperConfig->skipProperties(),
            true
        );
    }

    private function abbreviateClassNameIfNecessary(
        string $className
    ): string {
        $parts = explode('\\', $className);
        if (count($parts) > 4) {
            return $parts[0] . '\\...\\' . $parts[count($parts) - 1];
        }

        return $className;
    }

    /**
     * @return array<string, ReflectionProperty>
     */
    private function resolvePropertyReflections(object $value): array
    {
        $allProperties = [];

        $reflectionObject = new ReflectionObject($value);
        foreach ($reflectionObject->getProperties() as $property) {
            $allProperties[$property->getName()] = $property;
        }

        $child = $reflectionObject;
        while ($parentClass = $child->getParentClass()) {
            foreach ($parentClass->getProperties() as $property) {
                $allProperties[$property->getName()] = $property;
            }
            $child = $parentClass;
        }

        return $allProperties;
    }
}
