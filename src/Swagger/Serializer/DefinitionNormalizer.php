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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DefinitionNormalizer implements NormalizerInterface
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $nameConverter;
    private $typeExtractor;


    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, TypeExtractor $typeExtractor, NameConverterInterface $nameConverter = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->typeExtractor = $typeExtractor;
        $this->nameConverter = $nameConverter;
    }

    /**
     * @param \ArrayObject     $definitions
     * @param ResourceMetadata $resourceMetadata
     * @param string           $resourceClass
     * @param array|null       $serializerContext
     *
     * @return string
     */
    public function normalize(\ArrayObject $definitions, ResourceMetadata $resourceMetadata, string $resourceClass, array $serializerContext = null): string
    {
        $definitionKey = $this->getDefinitionKey($resourceMetadata->getShortName(), (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []));

        if (!isset($definitions[$definitionKey])) {
            $definitions[$definitionKey] = [];  // Initialize first to prevent infinite loop
            $definitions[$definitionKey] = $this->getDefinitionSchema($resourceClass, $resourceMetadata, $definitions, $serializerContext);
        }

        return $definitionKey;
    }

    private function getDefinitionKey(string $resourceShortName, array $groups): string
    {
        return $groups ? sprintf('%s-%s', $resourceShortName, implode('_', $groups)) : $resourceShortName;
    }

    /**
     * Gets a definition Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param string           $resourceClass
     * @param ResourceMetadata $resourceMetadata
     * @param \ArrayObject     $definitions
     * @param array|null       $serializerContext
     *
     * @return \ArrayObject
     */
    private function getDefinitionSchema(string $resourceClass, ResourceMetadata $resourceMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $definitionSchema = new \ArrayObject(['type' => 'object']);

        if (null !== $description = $resourceMetadata->getDescription()) {
            $definitionSchema['description'] = $description;
        }

        if (null !== $iri = $resourceMetadata->getIri()) {
            $definitionSchema['externalDocs'] = ['url' => $iri];
        }

        $options = isset($serializerContext[AbstractNormalizer::GROUPS]) ? ['serializer_groups' => $serializerContext[AbstractNormalizer::GROUPS]] : [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName) : $propertyName;

            if ($propertyMetadata->isRequired()) {
                $definitionSchema['required'][] = $normalizedPropertyName;
            }

            $definitionSchema['properties'][$normalizedPropertyName] = $this->getPropertySchema($propertyMetadata, $definitions, $serializerContext);
        }

        return $definitionSchema;
    }

    /**
     * Gets a property Schema Object.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#schemaObject
     *
     * @param PropertyMetadata $propertyMetadata
     * @param \ArrayObject     $definitions
     * @param array|null       $serializerContext
     *
     * @return \ArrayObject
     */
    private function getPropertySchema(PropertyMetadata $propertyMetadata, \ArrayObject $definitions, array $serializerContext = null): \ArrayObject
    {
        $propertySchema = new \ArrayObject($propertyMetadata->getAttributes()['swagger_context'] ?? []);

        if (false === $propertyMetadata->isWritable()) {
            $propertySchema['readOnly'] = true;
        }

        if (null !== $description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        if (null === $type = $propertyMetadata->getType()) {
            return $propertySchema;
        }

        $isCollection = $type->isCollection();
        if (null === $valueType = $isCollection ? $type->getCollectionValueType() : $type) {
            $builtinType = 'string';
            $className = null;
        } else {
            $builtinType = $valueType->getBuiltinType();
            $className = $valueType->getClassName();
        }

        $valueSchema = $this->typeExtractor->getType($builtinType, $isCollection, $className, $propertyMetadata->isReadableLink(), $definitions, $serializerContext);

        return new \ArrayObject((array) $propertySchema + $valueSchema);
    }
}
