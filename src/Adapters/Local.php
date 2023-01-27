<?php

namespace Bayfront\LeakyBucket\Adapters;

use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\AdapterInterface;

class Local implements AdapterInterface
{

    protected string $root;

    public function __construct(string $root = '')
    {
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
        return file_exists($this->root . '/' . $this->_getFilename($id));
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

        $dirname = dirname($this->root . '/' . $this->_getFilename($id));

        if (!is_dir($dirname)) {

            $dir = mkdir($dirname, 0755, true);

            if (false === $dir) {

                throw new AdapterException('Unable to save (' . $this->_getFilename($id) . ')');

            }
        }

        $write = file_put_contents($this->root . '/' . $this->_getFilename($id), $contents);

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

        $read = file_get_contents($this->root . '/' . $this->_getFilename($id));

        if ($read) {
            return $read;
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

        $delete = unlink($this->root . '/' . $this->_getFilename($id));

        if ($delete) {
            return;
        }

        throw new AdapterException('Unable to delete (' . $this->_getFilename($id) . ')');

    }

}