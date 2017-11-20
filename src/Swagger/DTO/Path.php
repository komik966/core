<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Swagger\DTO;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class Path
{
    private $resourceClass;
    private $resourceMetadata;
    private $mimeTypes;
    private $operationType;
    private $operations;

    public function __construct(
        string $resourceClass,
        ResourceMetadata $resourceMetadata,
        array $mimeTypes,
        string $operationType
    ) {
        $this->resourceClass = $resourceClass;
        $this->resourceMetadata = $resourceMetadata;
        $this->mimeTypes = $mimeTypes;
        $this->operationType = $operationType;
        $this->operations = OperationType::COLLECTION === $operationType ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations();
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getResourceShortName(): string
    {
        return $this->resourceMetadata->getShortName();
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

    public function getOperations(): ?array
    {
        return $this->operations;
    }
}
