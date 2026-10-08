# Endurance Activity

Domain model and application services for endurance activities: lifecycle,
measurements, laps, sessions, devices and summaries.

## Installation

Requires PHP `^8.5`.

```bash
composer require youmad/endurance-activity
```

## Activity lifecycle

```php
use Youmad\Endurance\Activity\Entity\Activity;
use Youmad\Endurance\Foundation\ValueObject\Instant;

$instant = static fn (string $value): Instant =>
    Instant::fromDateTimeImmutable(new DateTimeImmutable($value));

$activity = Activity::start($instant('2026-09-10T10:00:00Z'));
$activity->pause($instant('2026-09-10T10:20:00Z'));
$activity->resume($instant('2026-09-10T10:25:00Z'));
$activity->finish($instant('2026-09-10T10:45:00Z'));

$snapshot = $activity->snapshot();
$timerDuration = $activity->timerDuration();
```

Lifecycle and summary inconsistencies raise domain exceptions. The model retains
reported measurements and distinguishes active timer duration from elapsed time.
Pool lengths and segment efforts are represented as activity details.

## Streaming imports

`Application\UseCase\ImportActivityStream` consumes `ActivityImportItem` streams.
An import is staged under a generation and becomes visible after successful
activation. The application implements the persistence, transaction and recovery
interfaces in `Application\Port` and configures the item handlers.

Ready-made integrations are available in
[`youmad/endurance-activity-fit`](https://github.com/youmad/endurance-activity-fit)
and
[`youmad/endurance-activity-postgresql`](https://github.com/youmad/endurance-activity-postgresql).

## Measurements and timelines

Observations support scalar, text, position, range, vector and array readings.
Track read models retain repeated scalar types in their original order, with
unit, origin and source attribution. `UnambiguousScalarMeasurements::byType()`
returns only types with exactly one reading. Lap and Session summaries require
unique measurement types.

Source-specific metadata implements `Telemetry\MeasurementMetadata` with a stable
namespaced type, positive schema version and JSON-compatible data.

`Lap::create()` and `ActivitySession::create()` accept a `SummaryAdjacencyPolicy`
separately from timeline precision. `NonOverlapping` permits gaps and rejects
all overlaps. `AbutWithinTwoWholeSeconds` permits at most two seconds between
whole-second boundary views, without changing stored timestamps. The later
summary selects the policy for each adjacent pair. If omitted, second precision
selects abutment and microsecond precision selects non-overlap.

## Development

From a source checkout:

```bash
composer install
composer check
```

## License

[MPL-2.0](LICENSE).
