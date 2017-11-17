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

namespace ApiPlatform\Core\Swagger\Extractor;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Swagger\DTO\Definition;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Serializer;

final class TypeExtractor
{
    private $resourceClassResolver;
    private $resourceMetadataFactory;
    private $serializer;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, Serializer $serializer)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->serializer = $serializer;
    }

    /**
     * Gets the Swagger's type corresponding to the given PHP's type.
     *
     * @param string       $type
     * @param bool         $isCollection
     * @param string       $className
     * @param bool         $readableLink
     * @param array|null   $serializerContext
     *
     * @return array
     */
    public function getType(string $type, bool $isCollection, string $className = null, bool $readableLink = null, array $serializerContext = null): array
    {
        if ($isCollection) {
            return ['type' => 'array', 'items' => $this->getType($type, false, $className, $readableLink, $serializerContext)];
        }

        if (Type::BUILTIN_TYPE_STRING === $type) {
            return ['type' => 'string'];
        }

        if (Type::BUILTIN_TYPE_INT === $type) {
            return ['type' => 'integer'];
        }

        if (Type::BUILTIN_TYPE_FLOAT === $type) {
            return ['type' => 'number'];
        }

        if (Type::BUILTIN_TYPE_BOOL === $type) {
            return ['type' => 'boolean'];
        }

        if (Type::BUILTIN_TYPE_OBJECT === $type) {
            if (null === $className) {
                return ['type' => 'string'];
            }

            if (is_subclass_of($className, \DateTimeInterface::class)) {
                return ['type' => 'string', 'format' => 'date-time'];
            }

            if (!$this->resourceClassResolver->isResourceClass($className)) {
                return ['type' => 'string'];
            }

            if (true === $readableLink) {
                return ['$ref' => sprintf('#/definitions/%s',
                    $this->serializer->normalize(
                        new Definition($this->resourceMetadataFactory->create($className), $className, $serializerContext)
                    )
                )];
            }
        }

        return ['type' => 'string'];
    }
}
