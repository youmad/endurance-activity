<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Exception;

final class DuplicateActivityImportItemHandler extends \LogicException
{
    /**
     * @param class-string $itemClass
     */
    public static function forItemClass(
        string $itemClass,
    ): self {
        return new self(
            sprintf(
                'More than one activity import handler is registered for %s.',
                $itemClass,
            ),
        );
    }
}
