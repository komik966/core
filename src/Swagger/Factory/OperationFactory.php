<?php

declare(strict_types=1);

namespace Swagger\Factory;


use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Swagger\DTO\OperationInterface;

class OperationFactory
{
    private $operationPathResolver;
    private $operationMethodResolver;

    public function __construct(OperationPathResolverInterface $operationPathResolver, OperationMethodResolverInterface $operationMethodResolver)
    {
        $this->operationPathResolver = $operationPathResolver;
        $this->operationMethodResolver = $operationMethodResolver;
    }


    public function create($resourceShortName, $operation, $operationType, $operationName, $resourceClass): OperationInterface
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        $method = OperationType::ITEM === $operationType ? $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);

    }
}
