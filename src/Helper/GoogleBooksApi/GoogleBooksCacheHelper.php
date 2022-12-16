<?php

declare(strict_types=1);

namespace App\Helper\GoogleBooksApi;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author tlefebvre@norsys.fr
 *
 * Call http cache if the key exists.
 * Populate cache each time a book is requested and does not exist in cache.
 */
class GoogleBooksCacheHelper
{
    const BOOK_CACHE_KEY_PREFIX = "biblio_book";

    const CACHE_EXPIRATION = 3600; // 1 hour

    private CacheInterface $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string|null $isbn
     */
    public function getBookCacheData(string $isbn = null): ?array
    {
        if (null === $isbn) {
            return null;
        }

        $bookCacheKey = sprintf('%s', self::BOOK_CACHE_KEY_PREFIX);
        $bookCacheItem = $this->cache->getItem($bookCacheKey);

        if (false === $bookCacheItem->isHit()) {
            return null;
        }

        return $bookCacheItem->get();
    }

    /**
     * @param array $book
     * @return void
     */
    public function addBookToCache(array $book): void
    {
        $bookCacheKey = sprintf('%s', self::BOOK_CACHE_KEY_PREFIX);

        $bookCacheItem = $this->cache->getItem($bookCacheKey);
        $bookCacheItem->set($book);
        $bookCacheItem->expiresAfter(self::CACHE_EXPIRATION);

        $this->cache->save($bookCacheItem);
    }
}