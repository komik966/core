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

final class SubresourceNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = array())
    {

        foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $subresourceOperation) {
            $operationName = 'get';
            $subResourceMetadata = $this->resourceMetadataFactory->create($subresourceOperation['resource_class']);
            $serializerContext = $this->getSerializerContext(OperationType::SUBRESOURCE, false, $subResourceMetadata, $operationName);
            $responseDefinitionKey = $this->getDefinition($subResourceMetadata, $subresourceOperation['resource_class'], $serializerContext);

            $pathOperation = new \ArrayObject([]);
            $pathOperation['tags'] = $subresourceOperation['shortNames'];
            $pathOperation['operationId'] = $operationId;
            $pathOperation['produces'] = $mimeTypes;
            $pathOperation['summary'] = sprintf('Retrieves %s%s resource%s.', $subresourceOperation['collection'] ? 'the collection of ' : 'a ', $subresourceOperation['shortNames'][0], $subresourceOperation['collection'] ? 's' : '');
            $pathOperation['responses'] = [
                '200' => $subresourceOperation['collection'] ? [
                    'description' => sprintf('%s collection response', $subresourceOperation['shortNames'][0]),
                    'schema' => ['type' => 'array', 'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)]],
                ] : [
                    'description' => sprintf('%s resource response', $subresourceOperation['shortNames'][0]),
                    'schema' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                ],
                '404' => ['description' => 'Resource not found'],
            ];

            // Avoid duplicates parameters when there is a filter on a subresource identifier
            $parametersMemory = [];
            $pathOperation['parameters'] = [];

            foreach ($subresourceOperation['identifiers'] as list($identifier, , $hasIdentifier)) {
                if (true === $hasIdentifier) {
                    $pathOperation['parameters'][] = ['name' => $identifier, 'in' => 'path', 'required' => true, 'type' => 'string'];
                    $parametersMemory[] = $identifier;
                }
            }

            if ($parameters = $this->getFiltersParameters($resourceClass, $operationName, $subResourceMetadata, $serializerContext)) {
                foreach ($parameters as $parameter) {
                    if (!in_array($parameter['name'], $parametersMemory, true)) {
                        $pathOperation['parameters'][] = $parameter;
                    }
                }
            }

            $paths[$this->getPath($subresourceOperation['shortNames'][0], $subresourceOperation['route_name'], $subresourceOperation, OperationType::SUBRESOURCE)] = new \ArrayObject(['get' => $pathOperation]);
        }

    }

}
