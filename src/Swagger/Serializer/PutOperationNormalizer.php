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

namespace Swagger\Serializer;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PutOperationNormalizer implements NormalizerInterface
{
    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param string           $operationType
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     *
     * @return \ArrayObject
     */
    public function normalize(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName)
    {
        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $mimeTypes;
        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [
            [
                'name' => 'id',
                'in' => 'path',
                'type' => 'string',
                'required' => true,
            ],
            [
                'name' => lcfirst($resourceShortName),
                'in' => 'body',
                'description' => sprintf('The updated %s resource', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->definitionNormalizer($resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, true, $resourceMetadata, $operationName)
                ))],
            ],
        ];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource updated', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->definitionNormalizer($resourceMetadata, $resourceClass,
                    $this->getSerializerContext($operationType, false, $resourceMetadata, $operationName)
                ))],
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }
}
