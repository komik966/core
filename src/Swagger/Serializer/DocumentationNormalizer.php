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

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Swagger API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    const SWAGGER_VERSION = '2.0';
    const FORMAT = 'json';

    private $resourceMetadataFactory;
    private $operationMethodResolver;
    private $operationPathResolver;
    private $oauthEnabled;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;
    private $apiKeys;
    private $subresourceOperationFactory;


    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver, UrlGeneratorInterface $urlGenerator = null, $oauthEnabled = false, $oauthType = '', $oauthFlow = '', $oauthTokenUrl = '', $oauthAuthorizationUrl = '', array $oauthScopes = [], array $apiKeys = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null)
    {
        if ($urlGenerator) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.1 and will be removed in 3.0.', UrlGeneratorInterface::class, __METHOD__), E_USER_DEPRECATED);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->apiKeys = $apiKeys;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $mimeTypes = $object->getMimeTypes();
        $definitions = new \ArrayObject();
        $paths = new \ArrayObject();

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceShortName = $resourceMetadata->getShortName();

            $this->addPaths($paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::COLLECTION);
            $this->addPaths($paths, $definitions, $resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::ITEM);

            if (null === $this->subresourceOperationFactory) {
                continue;
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $subresourceOperation) {
                $operationName = 'get';
                $subResourceMetadata = $this->resourceMetadataFactory->create($subresourceOperation['resource_class']);
                $serializerContext = $this->getSerializerContext(OperationType::SUBRESOURCE, false, $subResourceMetadata, $operationName);
                $responseDefinitionKey = $this->getDefinition($definitions, $subResourceMetadata, $subresourceOperation['resource_class'], $serializerContext);

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

                if ($parameters = $this->getFiltersParameters($resourceClass, $operationName, $subResourceMetadata, $definitions, $serializerContext)) {
                    foreach ($parameters as $parameter) {
                        if (!in_array($parameter['name'], $parametersMemory, true)) {
                            $pathOperation['parameters'][] = $parameter;
                        }
                    }
                }

                $paths[$this->getPath($subresourceOperation['shortNames'][0], $subresourceOperation['route_name'], $subresourceOperation, OperationType::SUBRESOURCE)] = new \ArrayObject(['get' => $pathOperation]);
            }
        }

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($object, $definitions, $paths, $context);
    }

    /**
     * Updates the list of entries in the paths collection.
     *
     * @param \ArrayObject     $paths
     * @param \ArrayObject     $definitions
     * @param string           $resourceClass
     * @param string           $resourceShortName
     * @param ResourceMetadata $resourceMetadata
     * @param array            $mimeTypes
     * @param string           $operationType
     */
    private function addPaths(\ArrayObject $paths, \ArrayObject $definitions, string $resourceClass, string $resourceShortName, ResourceMetadata $resourceMetadata, array $mimeTypes, string $operationType)
    {
        if (null === $operations = OperationType::COLLECTION === $operationType ? $resourceMetadata->getCollectionOperations() : $resourceMetadata->getItemOperations()) {
            return;
        }

        foreach ($operations as $operationName => $operation) {
            $path = $this->getPath($resourceShortName, $operationName, $operation, $operationType);
            $method = OperationType::ITEM === $operationType ? $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName) : $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);

            $paths[$path][strtolower($method)] = $this->getPathOperation($operationName, $operation, $method, $operationType, $resourceClass, $resourceMetadata, $mimeTypes, $definitions);
        }
    }

    /**
     * Gets the path for an operation.
     *
     * If the path ends with the optional _format parameter, it is removed
     * as optional path parameters are not yet supported.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/issues/93
     *
     * @param string $resourceShortName
     * @param string $operationName
     * @param array  $operation
     * @param string $operationType
     *
     * @return string
     */
    private function getPath(string $resourceShortName, string $operationName, array $operation, string $operationType): string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return $path;
    }

    /**
     * Gets a path Operation Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operation-object
     *
     * @param string           $operationName
     * @param array            $operation
     * @param string           $method
     * @param string           $operationType
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param string[]         $mimeTypes
     * @param \ArrayObject     $definitions
     *
     * @return \ArrayObject
     */
    private function getPathOperation(string $operationName, array $operation, string $method, string $operationType, string $resourceClass, ResourceMetadata $resourceMetadata, array $mimeTypes, \ArrayObject $definitions): \ArrayObject
    {
        $pathOperation = new \ArrayObject($operation['swagger_context'] ?? []);
        $resourceShortName = $resourceMetadata->getShortName();
        $pathOperation['tags'] ?? $pathOperation['tags'] = [$resourceShortName];
        $pathOperation['operationId'] ?? $pathOperation['operationId'] = lcfirst($operationName).ucfirst($resourceShortName).ucfirst($operationType);

        switch ($method) {
            case 'GET':
                return $this->updateGetOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'POST':
                return $this->updatePostOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'PATCH':
                $pathOperation['summary'] ?? $pathOperation['summary'] = sprintf('Updates the %s resource.', $resourceShortName);
                // no break
            case 'PUT':
                return $this->updatePutOperation($pathOperation, $mimeTypes, $operationType, $resourceMetadata, $resourceClass, $resourceShortName, $operationName, $definitions);
            case 'DELETE':
                return $this->updateDeleteOperation($pathOperation, $resourceShortName);
        }

        return $pathOperation;
    }

    /**
     * Computes the Swagger documentation.
     *
     * @param Documentation $documentation
     * @param \ArrayObject  $definitions
     * @param \ArrayObject  $paths
     * @param array         $context
     *
     * @return array
     */
    private function computeDoc(Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths, array $context): array
    {
        $doc = [
            'swagger' => self::SWAGGER_VERSION,
            'basePath' => $context['base_url'] ?? '/',
            'info' => [
                'title' => $documentation->getTitle(),
                'version' => $documentation->getVersion(),
            ],
            'paths' => $paths,
        ];

        $securityDefinitions = [];
        $security = [];

        if ($this->oauthEnabled) {
            $securityDefinitions['oauth'] = [
                'type' => $this->oauthType,
                'description' => 'OAuth client_credentials Grant',
                'flow' => $this->oauthFlow,
                'tokenUrl' => $this->oauthTokenUrl,
                'authorizationUrl' => $this->oauthAuthorizationUrl,
                'scopes' => $this->oauthScopes,
            ];

            $security[] = ['oauth' => []];
        }

        if ($this->apiKeys) {
            foreach ($this->apiKeys as $key => $apiKey) {
                $name = $apiKey['name'];
                $type = $apiKey['type'];

                $securityDefinitions[$key] = [
                    'type' => 'apiKey',
                    'in' => $type,
                    'description' => sprintf('Value for the %s %s', $name, 'query' === $type ? sprintf('%s parameter', $type) : $type),
                    'name' => $name,
                ];

                $security[] = [$key => []];
            }
        }

        if ($securityDefinitions && $security) {
            $doc['securityDefinitions'] = $securityDefinitions;
            $doc['security'] = $security;
        }

        if ('' !== $description = $documentation->getDescription()) {
            $doc['info']['description'] = $description;
        }

        if (count($definitions) > 0) {
            $doc['definitions'] = $definitions;
        }

        return $doc;
    }


    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }
}
