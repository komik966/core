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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Swagger\DTO\PostOperation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PostOperationNormalizer implements NormalizerInterface
{
    /**
     * @param PostOperation $object
     * @param null $format
     * @param array $context
     * @return \ArrayObject
     */
    public function normalize($object, $format = null, array $context = array()): \ArrayObject
    {
        $pathOperation = clone $object->getPathOperation();
        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $object->getMimeTypes();
        $pathOperation['produces'] ?? $pathOperation['produces'] = $object->getMimeTypes();
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Creates a %s resource.', $object->getResourceShortName());
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => lcfirst($object->getResourceShortName()),
            'in' => 'body',
            'description' => sprintf('The new %s resource', $object->getResourceShortName()),
            'schema' => ['$ref' => sprintf('#/definitions/%s', $this->definitionNormalizer($object->getResourceMetadata(), $object->getResourceClass(),
                $this->getSerializerContext($object->getOperationType(), true, $object->getResourceMetadata(), $object->getOperationName())
            ))],
        ]];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '201' => [
                'description' => sprintf('%s resource created', $object->getResourceShortName()),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->definitionNormalizer($object->getResourceMetadata(), $object->getResourceClass(),
                    $this->getSerializerContext($object->getOperationType(), false, $object->getResourceMetadata(), $object->getOperationName())
                ))],
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof PostOperation;
    }
}
