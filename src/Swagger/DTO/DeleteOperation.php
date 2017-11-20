<?php

declare(strict_types=1);

namespace Swagger\DTO;

class DeleteOperation implements OperationInterface
{
    private $pathOperation;
    private $resourceShortName;
    private $path;

    public function __construct(\ArrayObject $pathOperation, string $resourceShortName, string $path)
    {
        $this->pathOperation = $pathOperation;
        $this->resourceShortName = $resourceShortName;
        $this->path = $path;
    }

    public function getPathOperation(): \ArrayObject
    {
        return $this->pathOperation;
    }

    public function getResourceShortName(): string
    {
        return $this->resourceShortName;
    }

    public function getMethod(): string
    {
        return 'DELETE';
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
