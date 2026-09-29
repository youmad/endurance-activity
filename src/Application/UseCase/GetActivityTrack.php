<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\UseCase;

use Youmad\Endurance\Activity\Application\Port\ActivityTrackReadRepository;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackCursor;
use Youmad\Endurance\Activity\Application\Read\ActivityTrackPage;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final readonly class GetActivityTrack
{
    public const int DEFAULT_LIMIT = 500;

    public const int MAX_LIMIT = 1000;

    public function __construct(
        private ActivityTrackReadRepository $track,
    ) {
    }

    public function page(
        ActivityId $activityId,
        int $limit = self::DEFAULT_LIMIT,
        ?ActivityTrackCursor $after = null,
    ): ?ActivityTrackPage {
        if (1 > $limit || self::MAX_LIMIT < $limit) {
            throw new \InvalidArgumentException(sprintf('Activity track page limit must be between 1 and %d.', self::MAX_LIMIT));
        }

        return $this->track->findPage(
            activityId: $activityId,
            limit: $limit,
            after: $after,
        );
    }
}
