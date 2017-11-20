<?php

declare(strict_types=1);

namespace Swagger\DTO;


use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class PutOperation implements OperationInterface
{
    private $pathOperation;
    private $mimeTypes;
    private $operationType;
    private $resourceMetadata;
    private $resourceClass;
    private $resourceShortName;
    private $operationName;
    private $path;

    public function __construct(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, string $path)
    {
        $this->pathOperation = $pathOperation;
        $this->mimeTypes = $mimeTypes;
        $this->operationType = $operationType;
        $this->resourceMetadata = $resourceMetadata;
        $this->resourceClass = $resourceClass;
        $this->resourceShortName = $resourceShortName;
        $this->operationName = $operationName;
        $this->path = $path;
    }

    public function getMethod(): string
    {
        return 'PUT';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPathOperation(): \ArrayObject
    {
        return $this->pathOperation;
    }

    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function getResourceMetadata(): ResourceMetadata
    {
        return $this->resourceMetadata;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getResourceShortName(): string
    {
        return $this->resourceShortName;
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }
}
