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
use PDOException;

class PDO implements AdapterInterface
{

    protected $pdo;

    protected $table;

    /**
     * PDO constructor.
     *
     * @param \PDO $pdo
     * @param string $table
     *
     * @throws AdapterException
     */

    public function __construct(\PDO $pdo, string $table = 'buckets')
    {

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // Throw exceptions

        try {

            $query = $pdo->prepare("CREATE TABLE IF NOT EXISTS $table (`id` varchar(255) NOT NULL PRIMARY KEY, `contents` text NOT NULL)");

            $query->execute();

        } catch (PDOException $e) {

            throw new AdapterException($e->getMessage(), 0, $e);

        }

        $this->pdo = $pdo;

        $this->table = $table;
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

        try {

            if (!$this->read($id)) {
                return false;
            } else {
                return true;
            }

        } catch (AdapterException $e) {

            return false;

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

            $stmt = $this->pdo->prepare("INSERT INTO $this->table (id, contents) values (:id, :contents) ON DUPLICATE KEY UPDATE contents=:contents");

            $stmt->execute([
                ':id' => $id,
                ':contents' => $contents
            ]);

        } catch (PDOException $e) {

            throw new AdapterException('Unable to save (' . $id . ')', 0, $e);

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

            $stmt = $this->pdo->prepare("SELECT contents FROM $this->table WHERE id = :id");

            $stmt->execute([
                ':id' => $id
            ]);

            return $stmt->fetchColumn();

        } catch (PDOException $e) {

            throw new AdapterException('Unable to read (' . $id . ')', 0, $e);

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

            $stmt = $this->pdo->prepare("DELETE FROM $this->table WHERE id = :id");

            $stmt->execute([
                ':id' => $id
            ]);

            if ($stmt->rowCount()) {
                return;
            }

        } catch (PDOException $e) {

            throw new AdapterException('Unable to delete (' . $id . ')', 0, $e);

        }

        throw new AdapterException('Unable to delete (' . $id . ')');

    }

}