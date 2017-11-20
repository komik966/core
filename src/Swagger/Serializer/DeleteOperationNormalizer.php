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

use Swagger\DTO\DeleteOperation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DeleteOperationNormalizer implements NormalizerInterface
{
    /**
     * @param DeleteOperation $object
     * @param null $format
     * @param array $context
     * @return \ArrayObject
     */
    public function normalize($object, $format = null, array $context = array()): \ArrayObject
    {
        $pathOperation = clone $object->getPathOperation();

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Removes the %s resource.', $object->getResourceShortName());
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '204' => ['description' => sprintf('%s resource deleted', $object->getResourceShortName())],
            '404' => ['description' => 'Resource not found'],
        ];

        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'type' => 'string',
            'required' => true,
        ]];

        return $pathOperation;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof DeleteOperation;
    }
}
