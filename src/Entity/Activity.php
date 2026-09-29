<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Entity;

use Youmad\Endurance\Activity\Detail\ActivityDetail;
use Youmad\Endurance\Activity\Detail\SequentialActivityDetail;
use Youmad\Endurance\Activity\Exception\CannotApplyActivitySummary;
use Youmad\Endurance\Activity\Exception\CannotConfirmActivityStart;
use Youmad\Endurance\Activity\Exception\CannotFinishActivity;
use Youmad\Endurance\Activity\Exception\CannotPauseActivity;
use Youmad\Endurance\Activity\Exception\CannotRecordActivityDetail;
use Youmad\Endurance\Activity\Exception\CannotRecordActivitySession;
use Youmad\Endurance\Activity\Exception\CannotRecordLap;
use Youmad\Endurance\Activity\Exception\CannotRecordObservation;
use Youmad\Endurance\Activity\Exception\CannotResumeActivity;
use Youmad\Endurance\Activity\Session\ActivitySession;
use Youmad\Endurance\Activity\Summary\ActivitySummary;
use Youmad\Endurance\Activity\Telemetry\ActivityObservation;
use Youmad\Endurance\Activity\ValueObject\ActivityId;
use Youmad\Endurance\Activity\ValueObject\Lap;
use Youmad\Endurance\Foundation\ValueObject\Duration;
use Youmad\Endurance\Foundation\ValueObject\Instant;
use Youmad\Endurance\Foundation\ValueObject\TemporalResolution;

final class Activity
{
    public private(set) ?Instant $finishedAt = null;

    public private(set) ?Instant $lastObservationAt = null;

    public private(set) ?Instant $lastLapFinishedAt = null;

    public private(set) ?Instant $lastSessionFinishedAt = null;

    public private(set) ?Instant $lastSessionTimelineFinishedAt = null;

    public private(set) ?Instant $lastDetailFinishedAt = null;

    /**
     * @var array<string, Instant>
     */
    private array $lastSequentialDetailFinishedAt = [];

    public private(set) int $sessionCount = 0;

    public private(set) Duration $recordedSessionTimerDuration;

    public private(set) ?Instant $summaryReportedAt = null;

    public private(set) ?string $type = null;

    public private(set) ?int $localTimeOffsetSeconds = null;

    public private(set) ?Instant $pausedAt = null;

    public private(set) Duration $accumulatedPausedDuration;

    private Instant $latestTimestamp;

    private function __construct(
        public private(set) readonly ActivityId $id,
        public private(set) readonly Instant $startedAt,
    ) {
        $this->latestTimestamp = $startedAt;
        $this->accumulatedPausedDuration = Duration::zero();
        $this->recordedSessionTimerDuration = Duration::zero();
    }

    public static function start(Instant $startedAt): self
    {
        return self::startWithId(
            id: ActivityId::generate(),
            startedAt: $startedAt,
        );
    }

    public static function startWithId(
        ActivityId $id,
        Instant $startedAt,
    ): self {
        return new self(
            id: $id,
            startedAt: $startedAt,
        );
    }

    public static function restore(ActivitySnapshot $snapshot): self
    {
        $activity = new self(
            id: $snapshot->id,
            startedAt: $snapshot->startedAt,
        );
        $activity->finishedAt = $snapshot->finishedAt;
        $activity->lastObservationAt = $snapshot->lastObservationAt;
        $activity->lastLapFinishedAt = $snapshot->lastLapFinishedAt;
        $activity->lastSessionFinishedAt =
            $snapshot->lastSessionFinishedAt;
        $activity->lastSessionTimelineFinishedAt =
            $snapshot->lastSessionTimelineFinishedAt;
        $activity->lastDetailFinishedAt =
            $snapshot->lastDetailFinishedAt;
        $activity->lastSequentialDetailFinishedAt =
            $snapshot->lastSequentialDetailFinishedAt;
        $activity->sessionCount = $snapshot->sessionCount;
        $activity->recordedSessionTimerDuration =
            $snapshot->recordedSessionTimerDuration;
        $activity->summaryReportedAt = $snapshot->summaryReportedAt;
        $activity->type = $snapshot->type;
        $activity->localTimeOffsetSeconds =
            $snapshot->localTimeOffsetSeconds;
        $activity->pausedAt = $snapshot->pausedAt;
        $activity->accumulatedPausedDuration =
            $snapshot->accumulatedPausedDuration;
        $activity->latestTimestamp = $snapshot->latestTimestamp;

        return $activity;
    }

    public function snapshot(): ActivitySnapshot
    {
        return ActivitySnapshot::create(
            id: $this->id,
            startedAt: $this->startedAt,
            finishedAt: $this->finishedAt,
            lastObservationAt: $this->lastObservationAt,
            lastLapFinishedAt: $this->lastLapFinishedAt,
            lastSessionFinishedAt: $this->lastSessionFinishedAt,
            lastSessionTimelineFinishedAt: $this->lastSessionTimelineFinishedAt,
            lastDetailFinishedAt: $this->lastDetailFinishedAt,
            lastSequentialDetailFinishedAt: $this->lastSequentialDetailFinishedAt,
            sessionCount: $this->sessionCount,
            recordedSessionTimerDuration: $this->recordedSessionTimerDuration,
            summaryReportedAt: $this->summaryReportedAt,
            type: $this->type,
            localTimeOffsetSeconds: $this->localTimeOffsetSeconds,
            pausedAt: $this->pausedAt,
            accumulatedPausedDuration: $this->accumulatedPausedDuration,
            latestTimestamp: $this->latestTimestamp,
        );
    }

    public function confirmStartedAt(Instant $startedAt): void
    {
        if (!$this->startedAt->equals($startedAt)) {
            throw new CannotConfirmActivityStart('Reported activity start does not match the aggregate start time.');
        }
    }

    public function isPaused(): bool
    {
        return null !== $this->pausedAt;
    }

    public function timerDuration(): ?Duration
    {
        $elapsedDuration = $this->elapsedDuration();

        if (null === $elapsedDuration) {
            return null;
        }

        return $elapsedDuration->minus(
            $this->accumulatedPausedDuration,
        );
    }

    public function elapsedDuration(): ?Duration
    {
        if (null === $this->finishedAt) {
            return null;
        }

        return Duration::between(
            $this->startedAt,
            $this->finishedAt,
        );
    }

    public function finish(Instant $finishedAt): void
    {
        if (null !== $this->finishedAt) {
            throw new CannotFinishActivity('Activity is already finished.');
        }

        if ($finishedAt->isBefore($this->latestTimestamp)) {
            throw new CannotFinishActivity('Activity cannot finish before its latest event.');
        }

        if (null !== $this->pausedAt) {
            $this->accumulatedPausedDuration = $this
                ->accumulatedPausedDuration
                ->plus(
                    Duration::between(
                        $this->pausedAt,
                        $finishedAt,
                    ),
                );
        }

        $this->finishedAt = $finishedAt;
        $this->pausedAt = null;
        $this->latestTimestamp = $finishedAt;
    }

    public function pause(Instant $pausedAt): void
    {
        if (null !== $this->finishedAt) {
            throw new CannotPauseActivity('Cannot pause a finished activity.');
        }

        if (null !== $this->pausedAt) {
            throw new CannotPauseActivity('Activity is already paused.');
        }

        if ($pausedAt->isBefore($this->latestTimestamp)) {
            throw new CannotPauseActivity('Activity cannot be paused before its latest event.');
        }

        $this->pausedAt = $pausedAt;
        $this->latestTimestamp = $pausedAt;
    }

    public function resume(Instant $resumedAt): void
    {
        if (null !== $this->finishedAt) {
            throw new CannotResumeActivity('Cannot resume a finished activity.');
        }

        $pausedAt = $this->pausedAt;

        if (null === $pausedAt) {
            throw new CannotResumeActivity('Activity is not paused.');
        }

        if ($resumedAt->isBefore($this->latestTimestamp)) {
            throw new CannotResumeActivity('Activity cannot be resumed before its latest event.');
        }

        $this->accumulatedPausedDuration = $this
            ->accumulatedPausedDuration
            ->plus(
                Duration::between(
                    $pausedAt,
                    $resumedAt,
                ),
            );

        $this->pausedAt = null;
        $this->latestTimestamp = $resumedAt;
    }

    public function recordObservation(
        ActivityObservation $observation,
    ): void {
        if (null !== $this->finishedAt) {
            throw new CannotRecordObservation('Cannot record an observation for a finished activity.');
        }

        if (
            $observation->timestamp->isBefore(
                $this->latestTimestamp,
            )
        ) {
            throw new CannotRecordObservation('Observation cannot be earlier than the latest activity event.');
        }

        $this->lastObservationAt = $observation->timestamp;
        $this->latestTimestamp = $observation->timestamp;
    }

    public function recordSession(
        ActivitySession $session,
    ): void {
        if (null !== $this->summaryReportedAt) {
            throw new CannotRecordActivitySession('Cannot record a session after the activity summary was applied.');
        }

        if ($session->startedAt->isBefore($this->startedAt)) {
            throw new CannotRecordActivitySession('Activity session cannot start before the activity.');
        }

        if (
            null !== $this->finishedAt
            && $this->finishedAt->isBefore(
                $session->finishedAt,
            )
        ) {
            throw new CannotRecordActivitySession('Activity session cannot finish after the activity.');
        }

        if (
            null !== $this->lastSessionFinishedAt
            && !$session->adjacencyPolicy->allows(
                previousEnd: $this->lastSessionFinishedAt,
                nextStart: $session->startedAt,
            )
        ) {
            if (
                !$session->startedAt->isBefore(
                    $this->lastSessionFinishedAt,
                )
            ) {
                throw new CannotRecordActivitySession('Activity session must abut the previous session within two whole seconds.');
            }

            $overlapMicroseconds = Duration::between(
                $session->startedAt,
                $this->lastSessionFinishedAt,
            )->toMicroseconds();

            throw new CannotRecordActivitySession(sprintf('Activity session interval %s..%s overlaps the previous session ending at %s by %d microseconds.', $session->startedAt->toDateTimeImmutable()->format('Y-m-d\TH:i:s.uP'), $session->finishedAt->toDateTimeImmutable()->format('Y-m-d\TH:i:s.uP'), $this->lastSessionFinishedAt->toDateTimeImmutable()->format('Y-m-d\TH:i:s.uP'), $overlapMicroseconds));
        }

        $timelineStartedAt = $session->startedAt;

        if (
            null !== $this->lastSessionTimelineFinishedAt
            && $timelineStartedAt->isBefore(
                $this->lastSessionTimelineFinishedAt,
            )
        ) {
            $timelineStartedAt =
                $this->lastSessionTimelineFinishedAt;
        }

        $this->lastSessionTimelineFinishedAt = self::addDuration(
            instant: $timelineStartedAt,
            duration: $session->elapsedDuration(),
        );
        $this->lastSessionFinishedAt = $session->finishedAt;
        ++$this->sessionCount;
        $this->recordedSessionTimerDuration = $this
            ->recordedSessionTimerDuration
            ->plus($session->timerDuration);

        if ($session->finishedAt->isAfter($this->latestTimestamp)) {
            $this->latestTimestamp = $session->finishedAt;
        }
    }

    public function applySummary(
        ActivitySummary $summary,
        ?TemporalResolution $timelineResolution = null,
    ): void {
        $timelineResolution ??= TemporalResolution::Microsecond;
        if (null !== $this->summaryReportedAt) {
            $sameSummary =
                $this->summaryReportedAt->equals($summary->reportedAt)
                && $this->sessionCount === $summary->sessionCount
                && $this->type === $summary->type;

            if ($sameSummary) {
                if (null === $summary->localTimeOffsetSeconds) {
                    return;
                }

                if (null === $this->localTimeOffsetSeconds) {
                    $this->localTimeOffsetSeconds =
                        $summary->localTimeOffsetSeconds;

                    return;
                }

                if (
                    $this->localTimeOffsetSeconds
                    === $summary->localTimeOffsetSeconds
                ) {
                    return;
                }
            }

            throw new CannotApplyActivitySummary('A different activity summary has already been applied.');
        }

        if ($summary->sessionCount !== $this->sessionCount) {
            throw new CannotApplyActivitySummary(sprintf('Activity summary declares %d sessions, but %d sessions were recorded.', $summary->sessionCount, $this->sessionCount));
        }

        $lastSessionFinishedAt = $this->lastSessionFinishedAt;

        if (null === $lastSessionFinishedAt) {
            throw new CannotApplyActivitySummary('Activity summary cannot be applied before at least one session is recorded.');
        }

        $sessionTimelineFinishedAt =
            $this->lastSessionTimelineFinishedAt;

        if (
            null === $sessionTimelineFinishedAt
            || $sessionTimelineFinishedAt->isBefore(
                $lastSessionFinishedAt,
            )
        ) {
            throw new CannotApplyActivitySummary('Activity session timeline state is inconsistent.');
        }

        $effectiveFinishedAt = $sessionTimelineFinishedAt;
        $acceptedLapFinishedAt = null;

        if (
            TemporalResolution::Second === $timelineResolution
            && null !== $this->lastLapFinishedAt
        ) {
            // Garmin compares start_time + whole-second total_elapsed_time
            // for Lap/Session containment and rejects only a delta > 1.
            // FIT starts are whole seconds: flooring the calculated ends
            // gives the same comparison while preserving precise durations.
            $lapEndSeconds = (int) $this->lastLapFinishedAt
                ->toDateTimeImmutable()->format('U');
            $sessionEndSeconds = (int) $lastSessionFinishedAt
                ->toDateTimeImmutable()->format('U');

            if ($lapEndSeconds - $sessionEndSeconds <= 1) {
                $acceptedLapFinishedAt = $this->lastLapFinishedAt;
            }
        }

        $acceptedObservationAt = null;
        if (
            TemporalResolution::Second === $timelineResolution
            && null !== $this->lastObservationAt
        ) {
            // FIT Record/Session containment also includes a one-second
            // excess over the calculated Session end. Keep source instants
            // intact; only the enclosing Activity finish may be extended.
            $observationSeconds = (int) $this->lastObservationAt
                ->toDateTimeImmutable()->format('U');
            $sessionEndSeconds = (int) $lastSessionFinishedAt
                ->toDateTimeImmutable()->format('U');

            if ($observationSeconds - $sessionEndSeconds <= 1) {
                $acceptedObservationAt = $this->lastObservationAt;
            }
        }

        $containedBoundaries = [
            'observation' => $this->lastObservationAt,
            'lap' => $this->lastLapFinishedAt,
            'activity detail' => $this->lastDetailFinishedAt,
            'event' => $this->latestTimestamp,
        ];

        foreach ($containedBoundaries as $name => $boundary) {
            if (null === $boundary) {
                continue;
            }

            // latestTimestamp also advances when a Lap is recorded. Do not
            // reject the same accepted Lap boundary again as an event.
            $isAcceptedLapBoundary = null !== $acceptedLapFinishedAt
                && ('lap' === $name || 'event' === $name)
                && $boundary->equals($acceptedLapFinishedAt);

            // Observations also advance latestTimestamp. Accepting one here
            // must not make it fail again under the generic event boundary.
            $isAcceptedObservationBoundary = null !== $acceptedObservationAt
                && ('observation' === $name || 'event' === $name)
                && $boundary->equals($acceptedObservationAt);

            if (
                'lap' === $name
                && TemporalResolution::Second === $timelineResolution
            ) {
                $boundaryIsContained = $isAcceptedLapBoundary;
            } else {
                $boundaryIsContained = $isAcceptedLapBoundary
                    || $isAcceptedObservationBoundary
                    || $timelineResolution->includesAtUpperBoundary(
                        boundary: $sessionTimelineFinishedAt,
                        candidate: $boundary,
                    );
            }

            if (!$boundaryIsContained) {
                throw new CannotApplyActivitySummary(sprintf('Final activity session cannot finish before the latest %s.', $name));
            }

            if ($boundary->isAfter($effectiveFinishedAt)) {
                $effectiveFinishedAt = $boundary;
            }
        }

        if (null === $this->finishedAt) {
            $this->finish($effectiveFinishedAt);
        }

        $elapsedDuration = Duration::between(
            $this->startedAt,
            $effectiveFinishedAt,
        );

        if (
            $this->recordedSessionTimerDuration->isLongerThan(
                $elapsedDuration,
            )
        ) {
            throw new CannotApplyActivitySummary('Recorded session timer duration cannot exceed activity elapsed duration.');
        }

        // Keep raw session boundaries unchanged while allowing the aggregate
        // timeline to preserve the durations of sequential sessions whose
        // coarse source boundaries overlap within their declared resolution.
        $this->finishedAt = $effectiveFinishedAt;
        $this->pausedAt = null;
        $this->latestTimestamp = $effectiveFinishedAt;
        // The Activity message timer is advisory. Session summaries are the
        // canonical source for aggregate timer duration because they contain
        // the detailed per-session timing and are required for valid files.
        $this->accumulatedPausedDuration = $elapsedDuration->minus(
            $this->recordedSessionTimerDuration,
        );
        $this->summaryReportedAt = $summary->reportedAt;
        $this->type = $summary->type;
        $this->localTimeOffsetSeconds =
            $summary->localTimeOffsetSeconds;
    }

    public function recordLap(Lap $lap): void
    {
        if ($lap->startedAt->isBefore($this->startedAt)) {
            throw new CannotRecordLap('Lap cannot start before the activity.');
        }

        if (
            null !== $this->finishedAt
            && $this->finishedAt->isBefore(
                $lap->finishedAt,
            )
        ) {
            throw new CannotRecordLap('Lap cannot finish after the activity.');
        }

        if (
            null !== $this->lastLapFinishedAt
            && !$lap->adjacencyPolicy->allows(
                previousEnd: $this->lastLapFinishedAt,
                nextStart: $lap->startedAt,
            )
        ) {
            if (!$lap->startedAt->isBefore($this->lastLapFinishedAt)) {
                throw new CannotRecordLap('Lap must abut the previous lap within two whole seconds.');
            }

            $overlapMicroseconds = Duration::between(
                $lap->startedAt,
                $this->lastLapFinishedAt,
            )->toMicroseconds();

            throw new CannotRecordLap(sprintf('Lap interval %s..%s overlaps the previous lap ending at %s by %d microseconds.', $lap->startedAt->toDateTimeImmutable()->format('Y-m-d\TH:i:s.uP'), $lap->finishedAt->toDateTimeImmutable()->format('Y-m-d\TH:i:s.uP'), $this->lastLapFinishedAt->toDateTimeImmutable()->format('Y-m-d\TH:i:s.uP'), $overlapMicroseconds));
        }

        $this->lastLapFinishedAt = $lap->finishedAt;

        if ($lap->finishedAt->isAfter($this->latestTimestamp)) {
            $this->latestTimestamp = $lap->finishedAt;
        }
    }

    public function recordDetail(ActivityDetail $detail): void
    {
        $interval = $detail->interval();

        if ($interval->startedAt->isBefore($this->startedAt)) {
            throw new CannotRecordActivityDetail('Activity detail cannot start before the activity.');
        }

        if (
            null !== $this->finishedAt
            && $this->finishedAt->isBefore(
                $interval->finishedAt,
            )
        ) {
            throw new CannotRecordActivityDetail('Activity detail cannot finish after the activity.');
        }

        if ($detail instanceof SequentialActivityDetail) {
            $sequenceName = $detail->sequenceName();

            if (
                1 !== preg_match(
                    '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/',
                    $sequenceName,
                )
            ) {
                throw new CannotRecordActivityDetail('Activity detail sequence name must use canonical snake_case notation.');
            }

            $lastFinishedAt = $this
                ->lastSequentialDetailFinishedAt[$sequenceName]
                ?? null;

            if (
                null !== $lastFinishedAt
                && $interval->startedAt->isBefore($lastFinishedAt)
            ) {
                $overlapMicroseconds = Duration::between(
                    $interval->startedAt,
                    $lastFinishedAt,
                )->toMicroseconds();

                if (
                    $overlapMicroseconds
                    >= $detail->timelineResolution()->value
                ) {
                    throw new CannotRecordActivityDetail(sprintf('Activity details in sequence %s cannot overlap beyond their timeline resolution.', $sequenceName));
                }
            }

            if (
                null === $lastFinishedAt
                || $interval->finishedAt->isAfter($lastFinishedAt)
            ) {
                $this->lastSequentialDetailFinishedAt[$sequenceName] =
                    $interval->finishedAt;
            }
        }

        if (
            null === $this->lastDetailFinishedAt
            || $this->lastDetailFinishedAt->isBefore(
                $interval->finishedAt,
            )
        ) {
            $this->lastDetailFinishedAt = $interval->finishedAt;
        }

        if ($interval->finishedAt->isAfter($this->latestTimestamp)) {
            $this->latestTimestamp = $interval->finishedAt;
        }
    }

    private static function addDuration(
        Instant $instant,
        Duration $duration,
    ): Instant {
        $microseconds = $duration->toMicroseconds();

        if (0 === $microseconds) {
            return $instant;
        }

        $value = $instant->toDateTimeImmutable();
        $totalMicroseconds = (int) $value->format('u')
            + $microseconds;
        $seconds = (int) $value->format('U')
            + intdiv($totalMicroseconds, 1_000_000);
        $remainingMicroseconds = $totalMicroseconds % 1_000_000;
        $value = $value->setTimestamp($seconds);

        if (0 < $remainingMicroseconds) {
            $value = $value->modify(
                sprintf(
                    '+%d microseconds',
                    $remainingMicroseconds,
                ),
            );
        }

        return Instant::fromDateTimeImmutable($value);
    }
}
