<?php

/**
 * @package leaky-bucket
 * @link https://github.com/bayfrontmedia/leaky-bucket
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\LeakyBucket\Adapters;

use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class Flysystem implements AdapterInterface
{

    protected $storage;

    protected $root;

    public function __construct(Filesystem $storage, string $root = '')
    {

        $this->storage = $storage;

        $this->root = '/' . trim($root, '/'); // Trim slashes

    }

    protected function _getFilename(string $id): string
    {
        return 'bucket-' . $id . '.json';
    }

    /**
     * Does the bucket exist.
     *
     * @param string $id
     *
     * @return bool
     */

    public function exists(string $id): bool
    {
        return $this->storage->has($this->root . '/' . $this->_getFilename($id));
    }

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

    public function save(string $id, string $contents): void
    {

        $write = $this->storage->put($this->root . '/' . $this->_getFilename($id), $contents);

        if (false === $write) {

            throw new AdapterException('Unable to save (' . $this->_getFilename($id) . ')');

        }

    }

    /**
     * Read contents of bucket.
     *
     * @param string $id
     *
     * @return string
     *
     * @throws AdapterException
     */

    public function read(string $id): string
    {

        try {

            $read = $this->storage->read($this->root . '/' . $this->_getFilename($id));

            if ($read) {
                return $read;
            }

        } catch (FileNotFoundException $e) {

            throw new AdapterException('Unable to read (' . $this->_getFilename($id) . ')', 0, $e);

        }

        throw new AdapterException('Unable to read (' . $this->_getFilename($id) . ')');

    }

    /**
     * Delete bucket.
     *
     * @param string $id
     *
     * @return void
     *
     * @throws AdapterException
     */

    public function delete(string $id): void
    {

        try {

            $delete = $this->storage->delete($this->root . '/' . $this->_getFilename($id));

            if ($delete) {
                return;
            }

        } catch (FileNotFoundException $e) {

            throw new AdapterException('Unable to delete (' . $this->_getFilename($id) . ')', 0, $e);

        }

        throw new AdapterException('Unable to delete (' . $this->_getFilename($id) . ')');

    }

}