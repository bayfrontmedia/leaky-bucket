<?php

namespace Bayfront\LeakyBucket;

use Bayfront\ArrayHelpers\Arr;

class Bucket
{

    protected string $id; // Bucket ID

    protected AdapterInterface $storage; // AdapterInterface

    protected array $settings;

    protected mixed $bucket; // This bucket

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

    public function __construct(string $id, AdapterInterface $storage, array $settings = [])
    {

        $this->id = $id;

        $this->storage = $storage;

        $default_settings = [
            'capacity' => 10,
            'leak' => 10
        ];

        $this->settings = array_merge($default_settings, Arr::only($settings, array_keys($default_settings)));

        foreach ($this->settings as $v) {

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

    public function getLeakPerSecond(): float
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
        return 1 / $this->getLeakPerSecond();
    }

    /**
     * Returns the number of seconds until bucket has capacity for number of drops.
     * Returns 0 if bucket has capacity.
     *
     * @param int $drops
     * @return float
     */

    public function getSecondsUntilCapacity(int $drops = 1): float
    {

        $remaining = $this->getCapacityRemaining();

        if ($remaining > $drops) {
            return 0;
        }

        $overage = ($this->getCapacityUsed() + $drops) - $this->getCapacity();

        return $this->getSecondsPerDrop() * $overage;

    }

    /**
     * Returns the number of seconds until bucket would be empty.
     *
     * @return float
     */

    public function getSecondsUntilEmpty(): float
    {
        return $this->getCapacityUsed() * $this->getSecondsPerDrop();
    }

    /**
     * Manually update the bucket's timestamp.
     *
     * The bucket's timestamp is automatically updated when any of the following methods are called:
     * - fill
     * - leak
     * - spill
     * - dump
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
     * If not allowed to overflow and the bucket does not have the required capacity,
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

        $leakage = $elapsed_secs * $this->getLeakPerSecond(); // How much the bucket needs to leak

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
     * Checks if this bucket contains any additional data, or a specific key in dot notation.
     *
     * @param string|null $key (If NULL, checks if any additional data exists)
     *
     * @return bool
     */

    public function hasData(?string $key = NULL): bool
    {

        if (NULL === $key) {
            return (isset($this->bucket['data']) && !empty($this->bucket['data']));
        }

        return Arr::has($this->bucket, 'data.' . $key);

    }

    /**
     * Sets additional data for this bucket in dot notation.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */

    public function setData(string $key, mixed $value): self
    {

        Arr::set($this->bucket, 'data.' . $key, $value);

        return $this;

    }

    /**
     * Returns this bucket's additional data key in dot notation, or an optional default value if not found.
     *
     * @param string|null $key (Returns the entire data array when NULL)
     * @param mixed|null $default
     *
     * @return mixed
     */

    public function getData(?string $key = NULL, mixed $default = NULL): mixed
    {

        if (NULL === $key) {
            return Arr::get($this->bucket, 'data', $default);
        }

        return Arr::get($this->bucket, 'data.' . $key, $default);

    }

    /**
     * Removes additional data key in dot notation for this bucket.
     *
     * @param string|null $key (Removes the entire data array when NULL)
     *
     * @return self
     */

    public function forgetData(?string $key = NULL): self
    {

        if (NULL === $key) {

            Arr::forget($this->bucket, 'data');

        } else {

            Arr::forget($this->bucket, 'data.' . $key);

        }

        return $this;

    }

}