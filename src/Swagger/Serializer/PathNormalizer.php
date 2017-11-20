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

use ApiPlatform\Core\Swagger\DTO\Path;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class PathNormalizer implements NormalizerInterface
{
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Path $object
     * @param null $format
     * @param array $context
     * @return \ArrayObject
     */
    public function normalize($object, $format = null, array $context = array()): \ArrayObject
    {
        $result = new \ArrayObject();
        foreach ($object->getOperations() as $operationName => $operation) {
            $result[$operation->getPath()][strtolower($operation->getMethod())] = $this->serializer->normalize($operation);
        }
        return $result;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Path;
    }
}
