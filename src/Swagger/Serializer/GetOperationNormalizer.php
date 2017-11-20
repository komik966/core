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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Swagger\Extractor\ContextExtractor;
use Swagger\DTO\Definition;
use Swagger\DTO\FilterParameters;
use Swagger\DTO\GetOperation;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

final class GetOperationNormalizer implements NormalizerInterface
{
    private $contextExtractor;
    private $serializer;
    private $paginationEnabled;
    private $clientItemsPerPage;
    private $paginationPageParameterName;
    private $itemsPerPageParameterName;

    public function __construct(ContextExtractor $contextExtractor, Serializer $serializer, $paginationEnabled = true, $clientItemsPerPage = false, $paginationPageParameterName = 'page', $itemsPerPageParameterName = 'itemsPerPage')
    {
        $this->contextExtractor = $contextExtractor;
        $this->serializer = $serializer;
        $this->paginationEnabled = $paginationEnabled;
        $this->clientItemsPerPage = $clientItemsPerPage;
        $this->paginationPageParameterName = $paginationPageParameterName;
        $this->itemsPerPageParameterName = $itemsPerPageParameterName;
    }

    /**
     * @param GetOperation $object
     * @param null $format
     * @param array $context
     * @return mixed
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $serializerContext = $this->contextExtractor->getSerializerContext($object->getOperationType(), false, $object->getResourceMetadata(), $object->getOperationName());
        $responseDefinitionKey = $this->serializer->normalize(new Definition($object->getResourceMetadata(), $object->getResourceClass(), $serializerContext));
        $pathOperation = clone $object->getPathOperation();

        $pathOperation['produces'] ?? $pathOperation['produces'] = $object->getMimeTypes();

        if (OperationType::COLLECTION === $object->getOperationType()) {
            $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves the collection of %s resources.', $object->getResourceShortName());
            $pathOperation['responses'] ?? $pathOperation['responses'] = [
                '200' => [
                    'description' => sprintf('%s collection response', $object->getResourceShortName()),
                    'schema' => [
                        'type' => 'array',
                        'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                    ],
                ],
            ];

            if (!isset($pathOperation['parameters']) && $parameters = $this->serializer->normalize(new FilterParameters($object->getResourceClass(), $object->getOperationName(), $object->getResourceMetadata(), $serializerContext))) {
                $pathOperation['parameters'] = $parameters;
            }

            if ($this->paginationEnabled && $object->getResourceMetadata()->getCollectionOperationAttribute($object->getOperationName(), 'pagination_enabled', true, true)) {
                $pathOperation['parameters'][] = $this->getPaginationParameters();

                if ($object->getResourceMetadata()->getCollectionOperationAttribute($object->getOperationName(), 'pagination_client_items_per_page', $this->clientItemsPerPage, true)) {
                    $pathOperation['parameters'][] = $this->getItemsParPageParameters();
                }
            }

            return $pathOperation;
        }

        $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Retrieves a %s resource.', $object->getResourceShortName());
        $pathOperation['parameters'] ?? $pathOperation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'string',
        ]];
        $pathOperation['responses'] ?? $pathOperation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource response', $object->getResourceShortName()),
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

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof GetOperation;
    }
}
