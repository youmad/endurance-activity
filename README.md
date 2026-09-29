# youmad/endurance-activity

A framework-agnostic domain model and application layer for endurance
activities.

The package represents activity lifecycle, telemetry, laps, sessions, devices,
summaries, and import coordination without depending on a file format,
database, message broker, or web framework.

## Features

- activity start, pause, resume, and finish invariants;
- observations and scalar, text, position, range, vector, and array
  measurements;
- laps, sessions, pool lengths, segment efforts, and activity summaries;
- device metadata and device-status observations;
- idempotent streaming imports with generation-based activation;
- persistence-neutral write, transaction, diagnostics, recovery, and read
  ports;
- activity and lap read models, plus cursor-paginated track reads.

## Installation

```bash
composer require youmad/endurance-activity
```

## Usage

The aggregate enforces chronological lifecycle transitions and exposes a
snapshot for persistence adapters:

```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Foundation\ValueObject\Instant;

require __DIR__.'/vendor/autoload.php';

$instant = static fn(string $value): Instant =>
    Instant::fromDateTimeImmutable(new DateTimeImmutable($value));

$activity = Activity::start($instant('2026-09-10T10:00:00Z'));
$activity->pause($instant('2026-09-10T10:20:00Z'));
$activity->resume($instant('2026-09-10T10:25:00Z'));
$activity->finish($instant('2026-09-10T10:45:00Z'));

$snapshot = $activity->snapshot();
$timerDuration = $activity->timerDuration();
```

Domain violations are reported through specific exceptions rather than
silently changing timestamps or measurements.

## Import model

Format-specific integrations produce `ActivityImportItem` streams. The
`ImportActivityStream` use case stages those items under an import generation
and makes them visible only after successful activation. Implementations of the
ports under `Application\Port` provide persistence, transaction, batching,
diagnostics, and recovery behavior.

FIT mapping is intentionally kept in the separate
`youmad/endurance-activity-fit` package. PostgreSQL implementations of the
persistence ports are provided by `youmad/endurance-activity-postgresql`.

## Track readings

`ActivityTrackPointReadModel::measurements()` preserves the ordered list of
scalar readings, including repeated types. Each `ActivityScalarMeasurement`
contains its value, unit, optional origin and source attribution/device ID.
An absent origin is represented by `null`, not an assumed reported value.

Consumers requiring one value per type can use
`UnambiguousScalarMeasurements::byType()`. A type is included only when it has
exactly one reading. Equal values, identical sources and different units do
not resolve ambiguity. The PostgreSQL track adapter uses this policy for
convenience fields such as `heartRate` and `altitude`.

Lap and Session summaries retain their existing unique-type invariant.
This track contract covers scalar readings; it does not broaden support for
multiple positions or expose source-specific metadata.

## Source-specific measurement metadata

`MeasurementMetadata` defines an explicit representation through
`metadataType()`, `metadataVersion()`, and `metadataData()`. Implementations own
stable namespaced type identifiers, positive schema versions, and named data
fields. These names must not be derived from PHP class or property names.
Nested data may contain arrays, strings, integers, finite floats, booleans, and
null; source-specific objects and enums must be converted explicitly.

Changing a class name or property visibility must not change this representation.
Incompatible changes to the data contract require a new version. The contract
does not depend on a database or on FIT.

## Summary adjacency policies

`SummaryAdjacencyPolicy` expresses two sequence rules independently of timestamp
precision. `NonOverlapping` rejects every overlap and permits positive gaps.
`AbutWithinTwoWholeSeconds` compares whole-second boundary views and permits a
difference of at most two seconds in either direction. It does not round the
gap duration or change either stored instant.

`Lap::create()` and `ActivitySession::create()` accept an optional
`adjacencyPolicy` independently of `timelineResolution`. Activity aggregates and
read models apply the incoming summary's policy to its boundary with the previous
summary. Mixed-policy sequences therefore use the policy on the later summary.

Omitted/null policy arguments select whole-second abutment for second precision
and non-overlap for microsecond precision. Integrations should select a policy
explicitly. The fallback is defined in `SummaryAdjacency::legacyPolicy()`.

Policy identifiers are `non_overlapping` and `abut_within_two_whole_seconds`.
FIT projection explicitly chooses the latter. Temporal resolution still controls
precision-related boundary rules elsewhere in Activity.

## Development

```bash
composer install
composer check
```

`composer check` validates the package metadata, runs PHPUnit and PHPStan
(level 6), and checks the code style with PHP CS Fixer (`@Symfony`).

Run individual checks or apply code-style fixes with:

```bash
composer test
composer analyse
composer cs:check
composer cs:fix
```

## License

The project-authored source code, tests, and documentation in this package
are licensed under the Mozilla Public License 2.0 (`MPL-2.0`).

> This Source Code Form is subject to the terms of the Mozilla Public
> License, v. 2.0. If a copy of the MPL was not distributed with this
> file, You can obtain one at https://mozilla.org/MPL/2.0/.

See [LICENSE](LICENSE). Dependencies retain their own licenses.
