<?php

declare(strict_types=1);

namespace SelectiveVarDump;

final class VarDumperConfig
{
    /**
     * @param array<string> $includeProperties
     * @param array<string> $skipProperties
     * @param array<class-string> $skipObjectsOfType
     */
    public function __construct(
        private array $includeProperties = [],
        private array $skipProperties = [],
        private array $skipObjectsOfType = []
    ) {
    }

    /**
     * @return array<string>
     */
    public function includeProperties(): array
    {
        return $this->includeProperties;
    }

    /**
     * @return array<string>
     */
    public function skipProperties(): array
    {
        return $this->skipProperties;
    }

    /**
     * @return array<class-string>
     */
    public function skipObjectsOfType(): array
    {
        return $this->skipObjectsOfType;
    }
}
