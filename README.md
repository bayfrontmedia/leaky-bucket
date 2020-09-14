## Leaky Bucket

Framework agnostic throttling using the leaky bucket algorithm.

A bucket has a defined capacity and leak rate per minute.
Buckets can also store additional arbitrary data.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](https://github.com/bayfrontmedia/php-array-helpers/blob/master/LICENSE).

## Author

John Robinson, [Bayfront Media](https://www.bayfrontmedia.com)

## Requirements

* PHP >= 7.1.0
* PDO PHP extension
* JSON PHP extension

## Installation

```
composer require bayfrontmedia/leaky-bucket
```

## Usage

### Storage adapter

A `Bayfront\LeakyBucket\ApadpterInterface` must be passed to the `Bayfront\LeakyBucket\Bucket` constructor.
There are a variety of storage adapters available, each with their own required configuration.

**Flysystem**

The Flysystem adapter allows you to use a [Flysystem](https://github.com/thephpleague/flysystem) `League\Flysystem\Filesystem` instance for bucket storage.

```
use Bayfront\LeakyBucket\Adapters\Flysystem;

$adapter = new Flysystem($filesystem, '/root_path');
```

**Local**

The local adapter allows you to store buckets locally using native PHP.

```
use Bayfront\LeakyBucket\Adapters\Local;

$adapter = new Local('/root_path');
```

**PDO**

The PDO adapter allows you to use a `\PDO` instance for bucket storage into a database, and may throw a `Bayfront\LeakyBucket\AdapterException` exception in its constructor.

The PDO adapter will create/use a table named "buckets" unless otherwise specified in the constructor.

```
use Bayfront\LeakyBucket\Adapters\PDO;

try {

    $adapter = new PDO($dbh, 'table_to_use');

} catch (AdapterException $e) {
    die($e->getMessage());
}
```
 
### Start using Leaky Bucket

Once your adapter has been created, it can be used with Leaky Bucket. 
In addition, an array containing keys defining the bucket structure should be passed to the constructor.

```
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

```
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

```
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

```
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

```
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

```
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

```
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

```
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

```
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

```
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

```
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

```
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

```
echo $bucket->getSecondsPerDrop();
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

```
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

```
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

```
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

```
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

```
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

```
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

```
$bucket->dump();
```

<hr />

### hasData

**Description:**

Checks if this bucket contains any additional data.

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```
if ($bucket->hasData()) {
    // Do something
}
```

<hr />

### setData

**Description:**

Sets additional data for this bucket.

**Parameters:**

- `$data` (array)

**Returns:**

- (self)

**Example:**

```
$bucket->setData([
    'client_id' => 45
]);
```

<hr />

### getData

**Description:**

Returns this bucket's additional data, or empty array if not existing.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```
$data = $bucket->getData();
```

<hr />

### forgetData

**Description:**

Removes all additional data for this bucket.

**Parameters:**

- None

**Returns:**

- (self)

**Example:**

```
$bucket->forgetData();
```