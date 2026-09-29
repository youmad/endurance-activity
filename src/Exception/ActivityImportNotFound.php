<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

use Youmad\Endurance\Activity\ValueObject\ActivityImportId;

final class ActivityImportNotFound extends \RuntimeException
{
    public static function withId(ActivityImportId $importId): self
    {
        return new self(
            sprintf(
                'Activity import %s was not found.',
                $importId->toString(),
            ),
        );
    }
}
