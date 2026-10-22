<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\Config;

//A wrapper service for the PHP-based caching to save on several MySQL reads
class DatabaseCacheService {
  private $cacheInstance;

  public function __construct(Application $app) {
    $this->app = $app;

    $config = new Config();
    $config->setPath(CACHE_PATH);

    $this->cacheInstance = CacheManager::getInstance('Files', $config);
  }

  /*
  * Attempt to load a cached instance of an object.
  * @param string $itemKey The unique identifier of the object in the cache
  * @return object the cached object, or null if $itemKey was a miss in the cache
  */
  public function GetCachedItem($itemKey) {
    $cachedItem = $this->_LoadCacheObject($itemKey);

    if (!$cachedItem->isHit()) {
      return null;
    }

    return $cachedItem->get();
  }

  /*
  * Store an object in the cache for
  * @param string $itemKey The unique identifier of the object in the cache
  * @param object $item The object that is to be stored in the cache
  */
  public function SetCachedItem($itemKey, $item) {
    $cachedItem = $this->_LoadCacheObject($itemKey);

    $cachedItem->set($item)->expiresAfter(CACHE_SECONDS);
    $this->cacheInstance->save($cachedItem);
  }

  /*
  * Remove an object from the cache
  * @param string $itemKey The unique identifier of the object in the cache
  */
  public function DeleteCachedItem($itemKey) {
    $this->cacheInstance->deleteItem($itemKey);
  }

  /*
  * Load a phpFastCache cache object for a given key. Is used in both get and set events.
  * @param string $itemKey The unique identifier of the object in the cache
  * @return object the phpFastCache object for the given key
  */
  private function _LoadCacheObject($itemKey) {
    return $this->cacheInstance->getItem($itemKey);
  }
}
