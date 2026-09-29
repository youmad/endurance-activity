<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Device;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Device\ActivityDevice;
use Youmad\Endurance\Activity\Device\DeviceDescriptor;
use Youmad\Endurance\Activity\Exception\InvalidActivityDevice;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;

final class ActivityDeviceTest extends TestCase
{
    public function testCanCreateUnknownActivityDevice(): void
    {
        $id = ActivityDeviceId::generate();

        $device = ActivityDevice::unknown($id);

        self::assertSame(
            $id,
            $device->id,
        );

        self::assertNull(
            $device->descriptor,
        );

        self::assertFalse(
            $device->isDescribed(),
        );
    }

    public function testCanCreateDescribedActivityDevice(): void
    {
        $id = ActivityDeviceId::generate();

        $descriptor = DeviceDescriptor::create(
            manufacturer: 'garmin',
            product: 'edge_840',
            serialNumber: '1234567890',
            productName: 'Edge 840',
        );

        $device = ActivityDevice::described(
            id: $id,
            descriptor: $descriptor,
        );

        self::assertSame(
            $id,
            $device->id,
        );

        self::assertSame(
            $descriptor,
            $device->descriptor,
        );

        self::assertTrue(
            $device->isDescribed(),
        );
    }

    public function testCanDescribePreviouslyUnknownDevice(): void
    {
        $id = ActivityDeviceId::generate();

        $unknownDevice = ActivityDevice::unknown($id);

        $descriptor = DeviceDescriptor::create(
            manufacturer: 'tacx',
            product: 'neo_2t',
            productName: 'Tacx NEO 2T',
        );

        $describedDevice = $unknownDevice->withDescriptor(
            $descriptor,
        );

        self::assertTrue(
            $describedDevice->id->equals(
                $unknownDevice->id,
            ),
        );

        self::assertSame(
            $descriptor,
            $describedDevice->descriptor,
        );

        self::assertFalse(
            $unknownDevice->isDescribed(),
        );

        self::assertTrue(
            $describedDevice->isDescribed(),
        );
    }

    public function testCanCreatePartialDeviceDescriptor(): void
    {
        $descriptor = DeviceDescriptor::create(
            productName: 'Unknown Power Meter',
        );

        self::assertNull(
            $descriptor->manufacturer,
        );

        self::assertNull(
            $descriptor->product,
        );

        self::assertSame(
            'Unknown Power Meter',
            $descriptor->productName,
        );
    }

    public function testDeviceDescriptorsCanBeEqual(): void
    {
        $first = DeviceDescriptor::create(
            manufacturer: 'garmin',
            product: 'edge_840',
            serialNumber: '1234567890',
            productName: 'Edge 840',
        );

        $second = DeviceDescriptor::create(
            manufacturer: 'garmin',
            product: 'edge_840',
            serialNumber: '1234567890',
            productName: 'Edge 840',
        );

        self::assertTrue(
            $first->equals($second),
        );
    }

    public function testDifferentDeviceDescriptorsAreNotEqual(): void
    {
        $first = DeviceDescriptor::create(
            manufacturer: 'garmin',
            product: 'edge_840',
        );

        $second = DeviceDescriptor::create(
            manufacturer: 'garmin',
            product: 'edge_1040',
        );

        self::assertFalse(
            $first->equals($second),
        );
    }

    public function testCannotCreateEmptyDeviceDescriptor(): void
    {
        $this->expectException(
            InvalidActivityDevice::class,
        );

        DeviceDescriptor::create();
    }

    public function testDescriptorValuesCannotBeEmpty(): void
    {
        $this->expectException(
            InvalidActivityDevice::class,
        );

        DeviceDescriptor::create(
            manufacturer: '',
        );
    }

    public function testDescriptorValuesCannotHaveOuterWhitespace(): void
    {
        $this->expectException(
            InvalidActivityDevice::class,
        );

        DeviceDescriptor::create(
            productName: ' Edge 840 ',
        );
    }
}
