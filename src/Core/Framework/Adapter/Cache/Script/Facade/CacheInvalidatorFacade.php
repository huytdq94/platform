<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Cache\Script\Facade;

use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;

/**
 * The `cache` service allows you to invalidate the cache if some entity is updated.
 *
 * @package core
 * @script-service custom_endpoint
 **/
class CacheInvalidatorFacade
{
    private CacheInvalidator $cacheInvalidator;

    /**
     * @internal
     */
    public function __construct(CacheInvalidator $cacheInvalidator)
    {
        $this->cacheInvalidator = $cacheInvalidator;
    }

    /**
     * `invalidate()` allows you to invalidate all cache entries with the given tag.
     *
     * @param array $tags The tags for which all cache entries should be invalidated as array.
     *
     * @example cache-invalidation/simple-script.twig Invalidate a hard coded tag.
     * @example cache-invalidation/filter-by-entity.twig Build tags based on written entities and invalidate those tags.
     * @example cache-invalidation/complex-invalidation.twig Build tags if products with a specific property is created and invalidate those tags.
     */
    public function invalidate(array $tags): void
    {
        $this->cacheInvalidator->invalidate($tags);
    }
}
