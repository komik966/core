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
use Swagger\DTO\PutOperation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PutOperationNormalizer implements NormalizerInterface
{
    /**
     * @param PutOperation $object
     * @param null $format
     * @param array $context
     * @return mixed
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $pathOperation = clone $object;

        $pathOperation['consumes'] ?? $pathOperation['consumes'] = $object->getMimeTypes();
        $pathOperation['produces'] ?? $pathOperation['produces'] = $object->getMimeTypes();
        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Replaces the %s resource.', $object->getResourceShortName());
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [
            [
                'name' => 'id',
                'in' => 'path',
                'type' => 'string',
                'required' => true,
            ],
            [
                'name' => lcfirst($object->getResourceShortName()),
                'in' => 'body',
                'description' => sprintf('The updated %s resource', $object->getResourceShortName()),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->definitionNormalizer($object->getResourceMetadata(), $object->getResourceClass(),
                    $this->getSerializerContext($object->getOperationType(), true, $object->getResourceMetadata(), $object->getOperationName())
                ))],
            ],
        ];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource updated', $object->getResourceShortName()),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $this->definitionNormalizer($object->getResourceMetadata(), $object->getResourceClass(),
                    $this->getSerializerContext($object->getOperationType(), false, $object->getResourceMetadata(), $object->getOperationName())
                ))],
            ],
            '400' => ['description' => 'Invalid input'],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof PutOperation;
    }
}
