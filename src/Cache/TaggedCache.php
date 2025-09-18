<?php

namespace BaseApi\Cache;

use BaseApi\Cache\Stores\StoreInterface;

/**
 * Tagged cache implementation for cache invalidation by tags.
 * 
 * Provides the ability to associate cache entries with tags and invalidate
 * all entries for specific tags efficiently.
 */
class TaggedCache implements CacheInterface
{
    private StoreInterface $store;
    private array $tags;

    public function __construct(StoreInterface $store, array $tags)
    {
        $this->store = $store;
        $this->tags = array_unique($tags);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->taggedKeyExists($key)) {
            return $default;
        }

        $value = $this->store->get($this->taggedKey($key));
        return $value !== null ? $value : $default;
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $taggedKey = $this->taggedKey($key);
        
        try {
            $this->store->put($taggedKey, $value, $ttl);
            $this->associateWithTags($key, $taggedKey);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function forget(string $key): bool
    {
        $taggedKey = $this->taggedKey($key);
        $this->removeFromTags($key);
        return $this->store->forget($taggedKey);
    }

    public function flush(): bool
    {
        // Get all keys for these tags and delete them
        $keys = $this->getTaggedKeys();
        
        foreach ($keys as $key) {
            $this->store->forget($key);
        }

        // Clear the tag references
        foreach ($this->tags as $tag) {
            $this->store->forget($this->tagKey($tag));
        }

        return true;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, null);
    }

    public function increment(string $key, int $value = 1): int
    {
        $taggedKey = $this->taggedKey($key);
        $result = $this->store->increment($taggedKey, $value);
        
        // Ensure key is associated with tags
        $this->associateWithTags($key, $taggedKey);
        
        return $result;
    }

    public function decrement(string $key, int $value = 1): int
    {
        $taggedKey = $this->taggedKey($key);
        $result = $this->store->decrement($taggedKey, $value);
        
        // Ensure key is associated with tags
        $this->associateWithTags($key, $taggedKey);
        
        return $result;
    }

    public function tags(array $tags): TaggedCache
    {
        // Return new instance with combined tags
        $combinedTags = array_unique(array_merge($this->tags, $tags));
        return new TaggedCache($this->store, $combinedTags);
    }

    /**
     * Get all keys associated with the current tags.
     */
    public function getTaggedKeys(): array
    {
        $keys = [];
        
        foreach ($this->tags as $tag) {
            $tagKeys = $this->getKeysForTag($tag);
            $keys = array_merge($keys, $tagKeys);
        }

        return array_unique($keys);
    }

    private function taggedKey(string $key): string
    {
        // Create a unique key based on tags and original key
        $tagHash = $this->getTagHash();
        return "tagged:{$tagHash}:{$key}";
    }

    private function tagKey(string $tag): string
    {
        return "tag:{$tag}:keys";
    }

    private function getTagHash(): string
    {
        // Create consistent hash for tag combination
        sort($this->tags);
        return hash('md5', implode('|', $this->tags));
    }

    private function taggedKeyExists(string $key): bool
    {
        // Check if all tags still exist for this key
        $tagHash = $this->getTagHash();
        
        foreach ($this->tags as $tag) {
            $tagKeys = $this->getKeysForTag($tag);
            $expectedKey = $this->taggedKey($key);
            
            if (!in_array($expectedKey, $tagKeys)) {
                return false;
            }
        }

        return true;
    }

    private function associateWithTags(string $originalKey, string $taggedKey): void
    {
        foreach ($this->tags as $tag) {
            $tagKeysKey = $this->tagKey($tag);
            $existingKeys = $this->store->get($tagKeysKey) ?: [];
            
            if (!in_array($taggedKey, $existingKeys)) {
                $existingKeys[] = $taggedKey;
                $this->store->put($tagKeysKey, $existingKeys, null); // Store tag associations permanently
            }
        }
    }

    private function removeFromTags(string $originalKey): void
    {
        $taggedKey = $this->taggedKey($originalKey);
        
        foreach ($this->tags as $tag) {
            $tagKeysKey = $this->tagKey($tag);
            $existingKeys = $this->store->get($tagKeysKey) ?: [];
            
            $index = array_search($taggedKey, $existingKeys);
            if ($index !== false) {
                unset($existingKeys[$index]);
                $existingKeys = array_values($existingKeys);
                
                if (empty($existingKeys)) {
                    $this->store->forget($tagKeysKey);
                } else {
                    $this->store->put($tagKeysKey, $existingKeys, null);
                }
            }
        }
    }

    private function getKeysForTag(string $tag): array
    {
        $tagKeysKey = $this->tagKey($tag);
        return $this->store->get($tagKeysKey) ?: [];
    }
}
