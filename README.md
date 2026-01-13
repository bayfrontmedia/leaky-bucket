## Leaky Bucket

Framework-agnostic throttling using the leaky bucket algorithm.

A bucket has a defined capacity and leak rate per minute.
Buckets can also store additional arbitrary data.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](LICENSE).

## Author

<img src="https://cdn1.onbayfront.com/bfm/brand/bfm-logo.svg" alt="Bayfront Media" width="250" />

- [Bayfront Media homepage](https://www.bayfrontmedia.com?utm_source=github&amp;utm_medium=direct)
- [Bayfront Media GitHub](https://github.com/bayfrontmedia)

## Requirements

* PHP `^8.0` (Tested up to `8.4`)
* PDO PHP extension
* JSON PHP extension

### Suggested

- Redis PHP extension

## Installation

```shell
composer require bayfrontmedia/leaky-bucket
```

## Usage

### Storage adapter

A `Bayfront\LeakyBucket\ApadpterInterface` must be passed to the `Bayfront\LeakyBucket\Bucket` constructor.
There are a variety of storage adapters available, each with their own required configuration.

**Flysystem**

The `Flysystem` adapter allows you to use a [Flysystem](https://github.com/thephpleague/flysystem) `League\Flysystem\Filesystem` v3 instance for bucket storage.

```php
use Bayfront\LeakyBucket\Adapters\Flysystem;

$adapter = new Flysystem($filesystem, '/root_path');
```

**Local**

The `Local` adapter allows you to store buckets locally using native PHP.

```php
use Bayfront\LeakyBucket\Adapters\Local;

$adapter = new Local('/root_path');
```

**PDO**

The `PDO` adapter allows you to use a `\PDO` instance for bucket storage into a database.

The `PDO` adapter will create/use a table named "buckets" unless otherwise specified in the constructor.

```php
use Bayfront\LeakyBucket\Adapters\PDO;

$adapter = new PDO($dbh, 'table_to_use');
```

Use the `up` method to create the necessary database table to be used by this adapter.
Use the `down` method to remove the database table.

These methods throw a `Bayfront\LeakyBucket\AdapterException` exception.

```php
try {

    $adapter->up();

} catch (AdapterException $e) {
    die($e->getMessage());
}
```

**Redis**

The `Redis` adapter allows you to use a `\Redis` instance for bucket storage.
The Redis PHP extension must be installed.

```php
use Bayfront\LeakyBucket\Adapters\Redis;

$redis = new Redis();

try {
    $redis->pconnect('10.0.0.1', 6379, 2, 'cache');
} catch (RedisException $e) {
    $redis->connect('10.0.0.1', 6379, 2);
}

$redis->auth([
    'USERNAME',
    'PASSWORD'
]);

$adapter = new \Bayfront\LeakyBucket\Adapters\Redis($redis, 'bucket:prefix:', 3600);
```

The second argument allows a prefix to be defined for all bucket keys.
The third argument allows a TTL to be defined (in seconds) for all buckets

A good TTL value can be calculated based on the bucket's capacity and leak rate.
For example, if the capacity is 10 and the leak rate is 10 (drops per minute), the longest possible duration of a valid
bucket is 1 minute. Therefore, a TTL of 60 seconds will ensure the bucket is automatically deleted once it has reached
the longest possible measured duration.

The TTL is reset each time the bucket is saved.

### Start using Leaky Bucket

Once your adapter has been created, it can be used with Leaky Bucket. 
In addition, an optional settings array can be passed to the constructor.
The default settings are shown below.

```php
use Bayfront\LeakyBucket\AdapterException;
use Bayfront\LeakyBucket\BucketException;
use Bayfront\LeakyBucket\Bucket;

// Create/retrieve a bucket with a given ID

try {

    $bucket = new Bucket('bucket_id', $adapter, [
        'capacity' => 10, // Total drop capacity
        'leak' => 10 // Number of drops to leak per minute
    ]);

} catch (AdapterException | BucketException $e) {
    die($e->getMessage());
}

// Work with the bucket

$bucket->leak();

if ($bucket->hasCapacity()) {

    try {

        $bucket->fill()->save();

    } catch (AdapterException $e) {
        die($e->getMessage());
    }

}
```

**NOTE:**
Be sure to `leak()` the bucket before attempting to do any calculations regarding its capacity.
Also, the `save()` method must be used to store the current bucket settings for future use.

### Public methods

- [exists](#exists)
- [save](#save)
- [get](#get)
- [reset](#reset)
- [delete](#delete)
- [isFull](#isfull)
- [getCapacity](#getcapacity)
- [getCapacityUsed](#getcapacityused)
- [getCapacityRemaining](#getcapacityremaining)
- [hasCapacity](#hascapacity)
- [getLeakPerSecond](#getleakpersecond)
- [getSecondsPerDrop](#getsecondsperdrop)
- [getSecondsUntilCapacity](#getsecondsuntilcapacity)
- [getSecondsUntilEmpty](#getsecondsuntilempty)
- [touch](#touch)
- [getLastTime](#getlasttime)
- [fill](#fill)
- [leak](#leak)
- [spill](#spill)
- [overflow](#overflow)
- [dump](#dump)
- [hasData](#hasdata)
- [setData](#setdata)
- [getData](#getdata)
- [forgetData](#forgetdata)

<hr />

### exists

**Description:**

Checks if this bucket ID already exists in storage.

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```php
if ($bucket->exists()) {
    // Do something
}
```

<hr />

### save

**Description:**

Saves the bucket.

**Parameters:**

- None

**Returns:**

- (self)

**Throws:**

`Bayfront\LeakyBucket\AdapterException`

**Example:**

```php
try {

    $bucket->save();

} catch (AdapterException $e) {
    die($e->getMessage());
}
```

<hr />

### get

**Description:**

Returns entire bucket contents.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```php
$contents = $bucket->get();
```

<hr />

### reset

**Description:**

Reset all bucket information and data.

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```php
$bucket->reset();
```

<hr />

### delete

**Description:**

Resets bucket and deletes the file in storage.

**Parameters:**

- None

**Returns:**

- (self)

**Throws:**

`Bayfront\LeakyBucket\AdapterException`

**Example:**

```php
try {

    $bucket->delete();

} catch (AdapterException $e) {
    die($e->getMessage());
}
```

<hr />

### isFull

**Description:**

Checks if bucket is full.

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```php
if ($bucket->isFull()) {
    // Do something
}
```

<hr />

### getCapacity

**Description:**

Returns the total bucket capacity.

**Parameters:**

- None

**Returns:**

- (int)

**Example:**

```php
echo $bucket->getCapacity();
```

<hr />

### getCapacityUsed

**Description:**

Returns the number of drops in the bucket.

**Parameters:**

- None

**Returns:**

- (float)

**Example:**

```php
echo $bucket->getCapacityUsed();
```

<hr />

### getCapacityRemaining

**Description:**

Returns the remaining bucket capacity.

**Parameters:**

- None

**Returns:**

- (float)

**Example:**

```php
echo $bucket->getCapacityRemaining();
```

<hr />

### hasCapacity

**Description:**

Checks if bucket has the capacity fo fill by a given number of drops.

**Parameters:**

- `$drops = 1` (int)

**Returns:**

- (bool)

**Example:**

```php
if ($bucket->hasCapacity(5)) {
    // Do something
}
```

<hr />

### getLeakPerSecond

**Description:**

Returns the number of drops per second the bucket will leak.

**Parameters:**

- None

**Returns:**

- (float)

**Example:**

```php
echo $bucket->getLeakPerSecond();
```

<hr />

### getSecondsPerDrop

**Description:**

Returns the number of seconds required to leak one drop.

**Parameters:**

- None

**Returns:**

- (float)

**Example:**

```php
echo $bucket->getSecondsPerDrop();
```

<hr />

### getSecondsUntilCapacity

**Description:**

Returns the number of seconds until bucket has capacity for number of drops.
Returns `0` if bucket has capacity.

**Parameters:**

- `$drops = 1` (int)

**Returns:**

- (float)

**Example:**

```php
echo $bucket->getSecondsPerDrop();
```

<hr />

### getSecondsUntilEmpty

**Description:**

Returns the number of seconds until bucket would be empty.

**Parameters:**

- None

**Returns:**

- (float)

**Example:**

```php
echo $bucket->getSecondsUntilEmpty();
```

<hr />

### touch

**Description:**

Manually update the bucket's timestamp.

The bucket's timestamp is automatically updated when any of the following methods are called:

- [fill](#fill)
- [leak](#leak)
- [spill](#spill)
- [dump](#dump)

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```php
$bucket->touch();
```

<hr />

### getLastTime

**Description:**

Returns the bucket's last timestamp.

**Parameters:**

- None

**Returns:**

- (int)

**Example:**

```php
echo $bucket->getLastTime();
```

<hr />

### fill

**Description:**

Fills the bucket with a given number of drops.

If not allowed to overflow, and the bucket does not have the required capacity,
a `Bayfront\LeakyBucket\BucketException` will be thrown. 
Otherwise, the bucket will be allowed to overflow.

**Parameters:**

- `$drops = 1` (int)
- `$allow_overflow = false` (bool)

**Returns:**

- (self)

**Throws:**

- `Bayfront\LeakyBucket\BucketException`

**Example:**

```php
try {
    
    $bucket->fill();
    
} catch (BucketException $e) {
    die($e->getMessage());
}
```

<hr />

### leak

**Description:**

Updates the bucket by calculating how many drops to leak since it's last timestamp.

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```php
$bucket->leak();
```

<hr />

### spill

**Description:**

Spills a given number of drops from the bucket.

**Parameters:**

- `$drops = 1` (int)

**Returns:**

- (self)

**Example:**

```php
$bucket->spill(5);
```

<hr />

### overflow

**Description:**

Dumps (empties) all drops from the bucket in excess of its capacity.

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```php
$bucket->overflow();
```

<hr />

### dump

**Description:**

Dumps (empties) all drops from the bucket.

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```php
$bucket->dump();
```

<hr />

### hasData

**Description:**

Checks if this bucket contains any additional data, or a specific key in dot notation.

**Parameters:**

- `$key = NULL` (string|null): If `NULL`, checks if any additional data exists

**Returns:**

- (bool)

**Example:**

```php
if ($bucket->hasData('client_id')) {
    // Do something
}
```

<hr />

### setData

**Description:**

Sets additional data for this bucket in dot notation.

**Parameters:**

- `$key` (string)
- `$value` (mixed)

**Returns:**

- (self)

**Example:**

```php
$bucket->setData('client_id', 5);
```

<hr />

### getData

**Description:**

Returns this bucket's additional data key in dot notation, or an optional default value if not found.

**Parameters:**

- `$key = NULL` (string|null): Returns the entire data array when `NULL`
- `$default = NULL` (mixed)

**Returns:**

- (mixed)

**Example:**

```php
$client_id = $bucket->getData('client_id');
```

<hr />

### forgetData

**Description:**

Removes additional data key in dot notation for this bucket.

**Parameters:**

- `$key = NULL` (string|null): Removes the entire data array when `NULL`

**Returns:**

- (self)

**Example:**

```php
$bucket->forgetData('client_id');
```