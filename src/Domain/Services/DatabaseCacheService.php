<?php
namespace HootDraft\Domain\Services;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

//A wrapper service for the PHP-based caching to save on several MySQL reads
class DatabaseCacheService {
  private $cacheInstance;

  public function __construct(
    #[Autowire('%hootdraft.cache_path%')] string $cachePath,
    #[Autowire('%hootdraft.cache_seconds%')] private int $cacheSeconds,
  ) {
    $config = new ConfigurationOption(['path' => $cachePath]);

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

    return $this->copy($cachedItem->get());
  }

  /*
  * Store an object in the cache for
  * @param string $itemKey The unique identifier of the object in the cache
  * @param object $item The object that is to be stored in the cache
  */
  public function SetCachedItem($itemKey, $item) {
    $cachedItem = $this->_LoadCacheObject($itemKey);

    $cachedItem->set($this->copy($item))->expiresAfter($this->cacheSeconds);
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
  * phpFastCache keeps the very object it was given for the rest of the request, so a caller that decorates
  * what it got back (hiding fields of a private draft, say) would corrupt what the next caller reads.
  * Hand out and keep shallow copies; the cached objects only hold scalars.
  */
  private function copy($item) {
    return is_object($item) ? clone $item : $item;
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
