<?php

/**
 * @package leaky-bucket
 * @link https://github.com/bayfrontmedia/leaky-bucket
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\LeakyBucket;

use Bayfront\ArrayHelpers\Arr;

class Bucket
{

    protected $id; // Bucket ID

    protected $storage; // AdapterInterface

    protected $settings;

    protected $bucket; // This bucket

    /**
     * Throttle constructor.
     *
     * @param string $id
     * @param AdapterInterface $storage
     * @param array $settings
     *
     * @throws BucketException
     * @throws AdapterException
     */

    public function __construct(string $id, AdapterInterface $storage, array $settings)
    {

        $this->id = $id;

        $this->storage = $storage;

        $default_settings = [
            'capacity' => 10,
            'leak' => 10
        ];

        $this->settings = Arr::only(array_merge($default_settings, $settings), [
            'capacity',
            'leak'
        ]);

        foreach ($this->settings as $k => $v) {

            if (!is_int($v)) {
                throw new BucketException('Invalid bucket configuration for bucket ID: ' . $id);
            }

        }

        if ($this->exists()) {

            /* @throws AdapterException */

            $this->bucket = json_decode($this->storage->read($id), true);

            /*
             * See save() for why the number of drops are converted
             */

            $this->bucket['drops'] = (float)$this->bucket['drops'];

        } else {

            $this->reset();

        }

        if (Arr::isMissing($this->bucket, [
            'drops',
            'time'
        ])) {
            throw new BucketException('Invalid bucket contents for bucket ID: ' . $id);
        }

    }

    /*
     * ############################################################
     * Bucket
     * ############################################################
     */

    /**
     * Checks if this bucket ID already exists in storage.
     *
     * @return bool
     */

    public function exists(): bool
    {
        return $this->storage->exists($this->id);
    }

    /**
     * Saves the bucket.
     *
     * @return self
     *
     * @throws AdapterException
     */

    public function save(): self
    {

        /*
         * The number of drops are converted to a string to prevent
         * extremely long decimal places due to the way PHP works with
         * json_encode and serialize_precision.
         */

        $this->bucket['drops'] = (string)$this->bucket['drops'];

        $this->storage->save($this->id, json_encode($this->bucket));

        return $this;

    }

    /**
     * Returns entire bucket contents.
     *
     * @return array
     */

    public function get(): array
    {
        return $this->bucket;
    }

    /**
     * Reset all bucket information and data.
     *
     * @return self
     */

    public function reset(): self
    {

        $this->bucket = [
            'drops' => 0,
            'time' => time()
        ];

        return $this;

    }

    /**
     * Resets bucket and deletes the file in storage.
     *
     * @return self
     *
     * @throws AdapterException
     */

    public function delete(): self
    {

        $this->reset();

        $this->storage->delete($this->id);

        return $this;

    }

    /**
     * Checks if bucket is full.
     *
     * @return bool
     */

    public function isFull(): bool
    {
        return $this->getCapacityUsed() >= $this->settings['capacity'];
    }

    /**
     * Returns the total bucket capacity.
     *
     * @return int
     */

    public function getCapacity(): int
    {
        return $this->settings['capacity'];
    }

    /**
     * Returns the number of drops in the bucket.
     *
     * @return float
     */

    public function getCapacityUsed(): float
    {
        return $this->bucket['drops'];
    }

    /**
     * Returns the remaining bucket capacity.
     *
     * @return float
     */

    public function getCapacityRemaining(): float
    {

        if ($this->isFull()) {
            return 0;
        }

        return $this->settings['capacity'] - $this->getCapacityUsed();
    }

    /**
     * Checks if bucket has the capacity fo fill by a given number of drops.
     *
     * @param int $drops
     *
     * @return bool
     */

    public function hasCapacity(int $drops = 1): bool
    {
        return $this->settings['capacity'] >= ($this->getCapacityUsed() + abs($drops));
    }

    /**
     * Returns the number of drops per second the bucket will leak.
     *
     * @return float
     */

    public function getLeakRate(): float
    {
        return $this->settings['leak'] / 60;
    }

    /**
     * Returns the number of seconds required to leak one drop.
     *
     * @return float
     */

    public function getSecondsPerDrop(): float
    {
        return 1 / $this->getLeakRate();
    }

    /**
     * Update the bucket's timestamp.
     *
     * @return self
     */

    public function touch(): self
    {

        $this->bucket['time'] = time();

        return $this;

    }

    /**
     * Returns the bucket's last timestamp.
     *
     * @return int
     */
    public function getLastTime(): int
    {
        return $this->bucket['time'];
    }

    /*
     * ############################################################
     * Add
     * ############################################################
     */

    /**
     * Fills the bucket with a given number of drops.
     *
     * If not allowed to overflow and the bucket does not have the needed capacity,
     * a BucketException will be thrown. Otherwise, the bucket will be allowed to overflow.
     *
     * @param int $drops
     * @param bool $allow_overflow
     *
     * @return self
     *
     * @throws BucketException
     */

    public function fill(int $drops = 1, bool $allow_overflow = false): self
    {

        if (false === $allow_overflow && !$this->hasCapacity($drops)) {
            throw new BucketException('Unable to fill ' . abs($drops) . ' drops to bucket (' . $this->id . '): Not enough capacity');
        }

        $this->bucket['drops'] += abs($drops); // Force positive integer

        $this->bucket['time'] = time();

        return $this;

    }

    /*
     * ############################################################
     * Subtract
     * ############################################################
     */

    /**
     * Updates the bucket by calculating how many drops to leak since it's last timestamp.
     *
     * @return self
     */

    public function leak(): self
    {

        $now = time();

        $elapsed_secs = $now - $this->bucket['time']; // Seconds since last update

        $leakage = $elapsed_secs * $this->getLeakRate(); // How much the bucket needs to leak

        if ($leakage > $this->bucket['drops']) { // Do not leak under 0

            $this->bucket['drops'] = 0;

        } else {

            $this->bucket['drops'] = $this->bucket['drops'] - $leakage;

        }

        $this->bucket['time'] = $now;

        return $this;

    }

    /**
     * Spills a given number of drops from the bucket.
     *
     * @param int $drops
     *
     * @return self
     */

    public function spill(int $drops = 1): self
    {

        $total = $this->bucket['drops'] - abs($drops); // Force positive integer

        if ($total < 0) {
            $total = 0;
        }

        $this->bucket['drops'] = $total;

        $this->bucket['time'] = time();

        return $this;

    }

    /**
     * Dumps (empties) all drops from the bucket in excess of its capacity.
     *
     * @return self
     */

    public function overflow(): self
    {

        if ($this->bucket['drops'] > $this->settings['capacity']) {
            $this->bucket['drops'] = $this->settings['capacity'];
        }

        return $this;

    }

    /**
     * Dumps (empties) all drops from the bucket.
     *
     * @return self
     */

    public function dump(): self
    {

        $this->bucket['drops'] = 0;

        $this->bucket['time'] = time();

        return $this;

    }

    /*
     * ############################################################
     * Data
     * ############################################################
     */

    /**
     * Checks if this bucket contains any additional data.
     *
     * @return bool
     */

    public function hasData(): bool
    {
        return isset($this->bucket['data']) && !empty($this->bucket['data']);
    }

    /**
     * Sets additional data for this bucket.
     *
     * @param array $data
     *
     * @return self
     */

    public function setData(array $data): self
    {

        $this->bucket['data'] = $data;

        return $this;

    }

    /**
     * Returns this bucket's additional data, or empty array if not existing.
     *
     * @return array
     */

    public function getData(): array
    {

        if ($this->hasData()) {
            return $this->bucket['data'];
        }

        return [];

    }

    /**
     * Removes all additional data for this bucket.
     *
     * @return self
     */

    public function forgetData(): self
    {

        if ($this->hasData()) {
            unset($this->bucket['data']);
        }

        return $this;

    }

}