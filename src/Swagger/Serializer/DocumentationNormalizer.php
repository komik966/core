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
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Core\Swagger\DTO\Path;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

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
    private $serializer;


    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, OperationMethodResolverInterface $operationMethodResolver, OperationPathResolverInterface $operationPathResolver, Serializer $serializer, UrlGeneratorInterface $urlGenerator = null, $oauthEnabled = false, $oauthType = '', $oauthFlow = '', $oauthTokenUrl = '', $oauthAuthorizationUrl = '', array $oauthScopes = [], array $apiKeys = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null)
    {
        if ($urlGenerator) {
            @trigger_error(sprintf('Passing an instance of %s to %s() is deprecated since version 2.1 and will be removed in 3.0.', UrlGeneratorInterface::class, __METHOD__), E_USER_DEPRECATED);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationMethodResolver = $operationMethodResolver;
        $this->operationPathResolver = $operationPathResolver;
        $this->serializer = $serializer;
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
        $paths = new \ArrayObject();

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceShortName = $resourceMetadata->getShortName();

            $paths->append($this->serializer->normalize(new Path($resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::COLLECTION)));
            $paths->append($this->serializer->normalize(new Path($resourceClass, $resourceShortName, $resourceMetadata, $mimeTypes, OperationType::ITEM)));

            if (null === $this->subresourceOperationFactory) {
                continue;
            }
            $this->subresourceNormalizer->normalize();
        }

        $paths->ksort();

        return $this->computeDoc($object, $paths, $context);
    }

    /**
     * Computes the Swagger documentation.
     *
     * @param Documentation $documentation
     * @param \ArrayObject  $paths
     * @param array         $context
     *
     * @return array
     */
    private function computeDoc(Documentation $documentation, \ArrayObject $paths, array $context): array
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

        if (count($poolSrv->getDefinitions) > 0) {
            $poolSrv->getDefinitions->ksort();
            $doc['definitions'] = $poolSrv->getDefinitions;
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
