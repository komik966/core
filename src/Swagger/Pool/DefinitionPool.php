<?php

declare(strict_types=1);

namespace Swagger\Pool;


class DefinitionPool
{
    private $definitions;

    public function __construct()
    {
        $this->definitions = new \ArrayObject();
    }

    public function getDefinition(string $key): ?\ArrayObject
    {
        if (!isset($this->definitions[$key])) {
            return null;
        }
        return $this->definitions[$key];
    }

    public function setDefinition(string $key, \ArrayObject $definition): void
    {
        $this->definitions[$key] = $definition;
    }
}
