<?php

declare(strict_types=1);

namespace Swagger\DTO;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class Definition
{
    private $resourceMetadata;
    private $resourceClass;
    private $serializerContext;

    public function __construct(ResourceMetadata $resourceMetadata, string $resourceClass, array $serializerContext = null)
    {
        $this->resourceMetadata = $resourceMetadata;
        $this->resourceClass = $resourceClass;
        $this->serializerContext = $serializerContext;
    }

    public function getResourceMetadata(): ResourceMetadata
    {
        return $this->resourceMetadata;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getSerializerContext(): ?array
    {
        return $this->serializerContext;
    }
}
