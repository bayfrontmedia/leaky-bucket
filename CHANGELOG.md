# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities

## [2.2.2] - 2024.12.24

### Changed

- Changed `created_at` and `updated_at` columns of the `PDO` adapter.

## [2.2.1] - 2024.12.23

### Added

- Tested up to PHP v8.4.
- Updated GitHub issue templates.

## [2.2.0] - 2023.05.02

### Added

- Added `getSecondsUntilCapacity` method.

## [2.1.0] - 2023.04.28

### Added

- Added `down` method to remove database table in `PDO` adapter.

### Changed

- Added `DEFAULT CHARSET` and `COLLATE` to database table in `PDO` adapter.
- Made the config array in the `Bucket` constructor optional.

## [2.0.0] - 2023.01.27

### Added

- Added support for PHP 8.

## [1.2.1] - 2021.05.31

### Added

- Added the `up` method for the `PDO` adapter to create the necessary database table.

### Changed

- Updated vendor libraries.

## [1.2.0] - 2020.11.04

### Added

- Added `getSecondsUntilEmpty` method.

## [1.1.0] - 2020.09.14

### Changed

- Updated bucket data to support dot notation.

## [1.0.1] - 2020.09.13

### Fixed

- Updated PDO adapter from referencing `$this->pdo` from within the constructor, 
as it can return `NULL` under certain circumstances.

## [1.0.0] - 2020.09.11

### Added

- Initial release.