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

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Swagger\Extractor\TypeExtractor;
use Psr\Container\ContainerInterface;
use Swagger\DTO\FilterParameters;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FilterParametersNormalizer implements NormalizerInterface
{
    use FilterLocatorTrait;

    private $typeExtractor;

    /**
     * @param ContainerInterface|FilterCollection|null $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(TypeExtractor $typeExtractor, $filterLocator = null)
    {
        $this->setFilterLocator($filterLocator, true);
        $this->typeExtractor = $typeExtractor;
    }

    /**
     * @param FilterParameters $object
     * @param null $format
     * @param array $context
     * @return array
     */
    public function normalize($object, $format = null, array $context = array()): array
    {
        if (null === $this->filterLocator) {
            return [];
        }

        $parameters = [];
        $resourceFilters = $object->getResourceMetadata()->getCollectionOperationAttribute($object->getOperationName(), 'filters', [], true);
        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($object->getResourceClass()) as $name => $data) {
                $parameter = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $data['required'],
                ];
                $parameter += $this->typeExtractor->getType($data['type'], false, null, null, $object->getSerializerContext());

                if (isset($data['swagger'])) {
                    $parameter = $data['swagger'] + $parameter;
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof FilterParameters;
    }
}
