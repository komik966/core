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
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Psr\Container\ContainerInterface;
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
     * Gets Swagger parameters corresponding to enabled filters.
     *
     * @param string           $resourceClass
     * @param string           $operationName
     * @param ResourceMetadata $resourceMetadata
     * @param \ArrayObject     $definitions
     * @param array|null       $serializerContext
     *
     * @return array
     */
    public function normalize(string $resourceClass, string $operationName, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): array
    {
        if (null === $this->filterLocator) {
            return [];
        }

        $parameters = [];
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);
        foreach ($resourceFilters as $filterId) {
            if (!$filter = $this->getFilter($filterId)) {
                continue;
            }

            foreach ($filter->getDescription($resourceClass) as $name => $data) {
                $parameter = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => $data['required'],
                ];
                $parameter += $this->typeExtractor->getType($data['type'], false, null, null, $definitions, $serializerContext);

                if (isset($data['swagger'])) {
                    $parameter = $data['swagger'] + $parameter;
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }
}
