<?php
/**
 * @package leaky-bucket
 * @link https://github.com/bayfrontmedia/leaky-bucket
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\LeakyBucket;

interface AdapterInterface
{

    /**
     * Does the bucket exist.
     *
     * @param string $id
     *
     * @return bool
     */

    public function exists(string $id): bool;

    /**
     * Save bucket.
     *
     * @param string $id
     * @param string $contents
     *
     * @returns void
     *
     * @throws AdapterException
     */

    public function save(string $id, string $contents): void;

    /**
     * Read contents of bucket.
     *
     * @param string $id
     *
     * @return string
     *
     * @throws AdapterException
     */

    public function read(string $id): string;

    /**
     * Delete bucket.
     *
     * @param string $id
     *
     * @return void
     *
     * @throws AdapterException
     */

    public function delete(string $id): void;

}