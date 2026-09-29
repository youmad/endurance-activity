<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

use Youmad\Endurance\Activity\Application\Import\ActivityImportItem;

final class UnsupportedActivityImportItem extends \LogicException
{
    public static function forItem(
        ActivityImportItem $item,
    ): self {
        return new self(
            sprintf(
                'Unsupported activity import item: %s.',
                $item::class,
            ),
        );
    }
}
