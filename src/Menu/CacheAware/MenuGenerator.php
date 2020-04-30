<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace App\Menu\CacheAware;

use App\Menu\Item\ItemBuilderInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Search\QueryExecutorInterface;
use App\Search\SearchResultLocationExtractor;
use App\Values\MenuQueryParameters;

final class MenuGenerator implements MenuGeneratorInterface
{
    /** @var \App\Service\Cache\CacheServiceInterface */
    private $cacheService;

    /** @var \App\Search\QueryExecutorInterface */
    private $executor;

    public function __construct(
        CacheServiceInterface $cacheService,
        QueryExecutorInterface $executor
    ) {
        $this->cacheService = $cacheService;
        $this->executor = $executor;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function generate(
        MenuQueryParameters $queryParameters,
        ItemBuilderInterface $builder,
        string $key
    ): array {
        $item = $this->cacheService->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $locationSearchResults = $this->executor->getResults($queryParameters);

        $menuItems = SearchResultLocationExtractor::extract($locationSearchResults);

        $menu = $builder->build($menuItems, $queryParameters->getRootLocationId());

        $item->expiresAfter((int) $this->cacheService->getCacheExpirationTime());
        $item->set($menu);
        $item->tag('location-' . $queryParameters->getRootLocationId());
        $item->tag('ez-location-' . $queryParameters->getRootLocationId() . '-eng-GB|1');
        $item->tag('location-path-' . $queryParameters->getRootLocationId());

        $this->cacheService->save($item);

        return $menu;
    }
}
