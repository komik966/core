<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Swagger\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class PathNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = array())
    {

    }

    /**
     * Updates the list of entries in the paths collection.
     *
     * @param \ArrayObject     $paths
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param ResourceMetadata $resourceMetadata
     * @param array            $mimeTypes
     * @param string           $operationType
     */
    private function addPaths(\ArrayObject $paths, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, string $operationType)
    {
        if (null === $operations = OperationType::COLLECTION === $operationType ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return;
        }

        foreach ($operations as $operationName => $operation) {
            $path = $this->getPath($resourceShortName, $operationName, $operation, $operationType);
            $method = OperationType::ITEM === $operationType ? $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);

            $paths[$path][strtolower($method)] = $this->getPathOperation($operationName, $operation, $method, $operationType, $resourceClass, $resourceMetadata, $mimeTypes);
        }
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     *
     * @param string $resourceShortName
     * @param string $operationName
     * @param array  $operation
     * @param string $operationType
     *
     * @return string
     */
    private function getPath(string $resourceShortName, string $operationName, array $operation, string $operationType): string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return $path;
    }

    /**
     * Gets a path Operation Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operation-object
     *
     * @param string           $operationName
     * @param array            $operation
     * @param string           $method
     * @param string           $operationType
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string[]         $mimeTypes
     *
     * @return \ArrayObject
     */
    private function getPathOperation(string $operationName, array $operation, string $method, string $operationType, string $resourceClass, ResourceMetadata $resourceMetadata, array $mimeTypes): \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation['swagger_context'] ?? []);
        $resourceShortName = $resourceMetadata->getShortName();
        $pathOperation['tags'] ?? $pathOperation['tags'] = [$resourceShortName];
        $pathOperation['operationId'] ?? $pathOperation['operationId'] = lcfirst($operationName).ucfirst($resourceShortName).ucfirst($operationType);

        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName);
            case 'POST':
                return $this->updatePostOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName);
            case 'PATCH':
                $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Updates the %s resource.', $resourceShortName);
            // no break
            case 'PUT':
                return $this->updatePutOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName);
            case 'DELETE':
                return $this->updateDeleteOperation($pathOperation, $resourceShortName);
        }

        return $pathOperation;
    }
}
