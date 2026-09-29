<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Device;

use Youmad\Endurance\Activity\Exception\InvalidActivityDevice;

final readonly class DeviceDescriptor
{
    private function __construct(
        public ?string $manufacturer,
        public ?string $product,
        public ?string $serialNumber,
        public ?string $productName,
        public ?string $description,
    ) {
    }

    public static function create(
        ?string $manufacturer = null,
        ?string $product = null,
        ?string $serialNumber = null,
        ?string $productName = null,
        ?string $description = null,
    ): self {
        self::assertOptionalText(
            'manufacturer',
            $manufacturer,
        );

        self::assertOptionalText(
            'product',
            $product,
        );

        self::assertOptionalText(
            'serial number',
            $serialNumber,
        );

        self::assertOptionalText(
            'product name',
            $productName,
        );

        self::assertOptionalText(
            'description',
            $description,
        );

        if (
            null === $manufacturer
            && null === $product
            && null === $serialNumber
            && null === $productName
            && null === $description
        ) {
            throw new InvalidActivityDevice('Device descriptor must contain at least one value.');
        }

        return new self(
            manufacturer: $manufacturer,
            product: $product,
            serialNumber: $serialNumber,
            productName: $productName,
            description: $description,
        );
    }

    private static function assertOptionalText(
        string $field,
        ?string $value,
    ): void {
        if (null === $value) {
            return;
        }

        if (
            '' === $value
            || trim($value) !== $value
        ) {
            throw new InvalidActivityDevice(sprintf('Device %s must be a non-empty trimmed string.', $field));
        }
    }

    public function equals(self $other): bool
    {
        return $this->manufacturer === $other->manufacturer
            && $this->product === $other->product
            && $this->serialNumber === $other->serialNumber
            && $this->productName === $other->productName
            && $this->description === $other->description;
    }
}
