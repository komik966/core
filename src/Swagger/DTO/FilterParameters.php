<?php

declare(strict_types=1);

namespace Swagger\DTO;


use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class FilterParameters
{
    private $resourceClass;
    private $operationName;
    private $resourceMetadata;
    private $serializerContext;

    public function __construct(
        string $resourceClass,
        string $operationName,
        ResourceMetadata $resourceMetadata,
        array $serializerContext = null
    ) {
        $this->resourceClass = $resourceClass;
        $this->operationName = $operationName;
        $this->resourceMetadata = $resourceMetadata;
        $this->serializerContext = $serializerContext;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getOperationName(): string
    {
        return $this->operationName;
    }

    public function getResourceMetadata(): ResourceMetadata
    {
        return $this->resourceMetadata;
    }

    public function getSerializerContext(): array
    {
        return $this->serializerContext;
    }
}
