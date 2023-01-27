<?php

namespace Bayfront\LeakyBucket\Adapters;

use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;

class Flysystem implements AdapterInterface
{

    protected Filesystem $storage;

    protected string $root;

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
     * @throws AdapterException
     */

    public function exists(string $id): bool
    {

        try {
            return $this->storage->has($this->root . '/' . $this->_getFilename($id));
        } catch (FilesystemException $e) {
            throw new AdapterException($e->getMessage(), 0, $e);
        }

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

        try {
            $this->storage->write($this->root . '/' . $this->_getFilename($id), $contents);
        } catch (FilesystemException $e) {
            throw new AdapterException($e->getMessage(), 0, $e);
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
            return $this->storage->read($this->root . '/' . $this->_getFilename($id));
        } catch (FilesystemException|UnableToReadFile $e) {
            throw new AdapterException($e->getMessage(), 0, $e);
        }

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
            $this->storage->delete($this->root . '/' . $this->_getFilename($id));
        } catch (FilesystemException|UnableToDeleteFile $e) {
            throw new AdapterException($e->getMessage(), 0, $e);
        }

    }

}