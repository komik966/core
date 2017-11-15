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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GetOperationNormalizer implements NormalizerInterface
{
    private $contextExtractor;
    private $definitionNormalizer;
    private $filterParametersNormalizer;
    private $paginationEnabled;
    private $clientItemsPerPage;
    private $paginationPageParameterName;
    private $itemsPerPageParameterName;

    public function __construct(ContextExtractor $contextExtractor, DefinitionNormalizer $definitionNormalizer, FilterParametersNormalizer $filterParametersNormalizer,$paginationEnabled = true, $clientItemsPerPage = false, $paginationPageParameterName = 'page', $itemsPerPageParameterName = 'itemsPerPage')
    {
        $this->contextExtractor = $contextExtractor;
        $this->definitionNormalizer = $definitionNormalizer;
        $this->filterParametersNormalizer = $filterParametersNormalizer;
        $this->paginationEnabled = $paginationEnabled;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->paginationPageParameterName = $paginationPageParameterName;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
    }
    /**
     * @param \ArrayObject     $pathOperation
     * @param array            $mimeTypes
     * @param string           $operationType
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param string           $operationName
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    public function normalize(\ArrayObject $pathOperation, array $mimeTypes, string $operationType, ResourceMetadata $resourceMetadata, string $resourceClass, string $resourceShortName, string $operationName, \ArrayObject $definitions)
    {
        $serializerContext = $this->contextExtractor->getSerializerContext($operationType, false, $resourceMetadata, $operationName);
        $responseDefinitionKey = $this->definitionNormalizer->normalize($definitions, $resourceMetadata, $resourceClass, $serializerContext);

        $pathOperation['produces'] ?? $pathOperation['produces'] = $mimeTypes;

        if (OperationType::COLLECTION === $operationType) {
            $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $resourceShortName);
            $pathOperation['responses'] ?? $pathOperation['responses'] = [
                '200' => [
                    'description' => sprintf('%s collection response', $resourceShortName),
                    'schema' => [
                        'type' => 'array',
                        'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                    ],
                ],
            ];

            if (!isset($pathOperation['parameters']) && $parameters = $this->filterParametersNormalizer->normalize($resourceClass, $operationName, $resourceMetadata, $definitions, $serializerContext)) {
                $pathOperation['parameters'] = $parameters;
            }

            if ($this->paginationEnabled && $resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_enabled', true, true)) {
                $pathOperation['parameters'][] = $this->getPaginationParameters();

                if ($resourceMetadata->getCollectionOperationAttribute($operationName, 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
                    $pathOperation['parameters'][] = $this->getItemsParPageParameters();
                }
            }

            return $pathOperation;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $resourceShortName);
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'string',
        ]];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource response', $resourceShortName),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
            ],
            '404' => ['description' => 'Resource not found'],
        ];

        return $pathOperation;
    }
    /**
     * Returns pagination parameters for the "get" collection operation.
     *
     * @return array
     */
    private function getPaginationParameters(): array
    {
        return [
            'name' => $this->paginationPageParameterName,
            'in' => 'query',
            'required' => false,
            'type' => 'integer',
            'description' => 'The collection page number',
        ];
    }

    /**
     * Returns items per page parameters for the "get" collection operation.
     *
     * @return array
     */
    private function getItemsParPageParameters(): array
    {
        return [
            'name' => $this->itemsPerPageParameterName,
            'in' => 'query',
            'required' => false,
            'type' => 'integer',
            'description' => 'The number of items per page',
        ];
    }
}
