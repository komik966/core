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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

final class ContextExtractor
{
    /**
     * @param string           $operationType
     * @param bool             $denormalization
     * @param ResourceMetadata $resourceMetadata
     * @param string           $operationName
     *
     * @return array|null
     */
    public function getSerializerContext(string $operationType, bool $denormalization, ResourceMetadata $resourceMetadata, string $operationName)
    {
        $contextKey = $denormalization ? 'denormalization_context' : 'normalization_context';

        if (OperationType::COLLECTION === $operationType) {
            return $resourceMetadata->getCollectionOperationAttribute($operationName, $contextKey, null, true);
        }

        return $resourceMetadata->getItemOperationAttribute($operationName, $contextKey, null, true);
    }
}
