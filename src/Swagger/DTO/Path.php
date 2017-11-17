<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Swagger\DTO;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class Path
{
    private $resourceClass;
    private $resourceShortName;
    private $resourceMetadata;
    private $mimeTypes;
    private $operationType;

    public function __construct(string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, string $operationType) {
        $this->resourceClass = $resourceClass;
        $this->resourceShortName = $resourceShortName;
        $this->resourceMetadata = $resourceMetadata;
        $this->mimeTypes = $mimeTypes;
        $this->operationType = $operationType;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getResourceShortName(): string
    {
        return $this->resourceShortName;
    }

    public function getResourceMetadata(): ResourceMetadata
    {
        return $this->resourceMetadata;
    }

    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }
}
