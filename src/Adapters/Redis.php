<?php

namespace Bayfront\LeakyBucket\Adapters;

use Bayfront\LeakyBucket\AdapterInterface;

class Redis implements AdapterInterface
{

    protected \Redis $redis;
    protected string $prefix;
    protected int $ttl;

    public function __construct(\Redis $redis, string $prefix = '', int $ttl = 3600)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $id): bool
    {
        return (bool)$this->redis->exists($this->prefix . $id);
    }

    /**
     * @inheritDoc
     */
    public function save(string $id, string $contents): void
    {
        $this->redis->setex($this->prefix . $id, $this->ttl, $contents);
    }

    /**
     * @inheritDoc
     */
    public function read(string $id): string
    {
        return $this->redis->get($this->prefix . $id);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $id): void
    {
        $this->redis->unlink($this->prefix . $id);
    }

}