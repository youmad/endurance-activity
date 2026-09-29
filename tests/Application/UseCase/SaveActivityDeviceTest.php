<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Activity\Application\Port\ActivityDeviceWriter;
use Youmad\Endurance\Activity\Application\UseCase\SaveActivityDevice;
use Youmad\Endurance\Activity\Device\ActivityDevice;
use Youmad\Endurance\Activity\Device\DeviceDescriptor;
use Youmad\Endurance\Activity\ValueObject\ActivityDeviceId;
use Youmad\Endurance\Activity\ValueObject\ActivityId;

final class SaveActivityDeviceTest extends TestCase
{
    public function testSavesActivityDevice(): void
    {
        $activityId = ActivityId::generate();

        $device = ActivityDevice::described(
            id: ActivityDeviceId::generate(),
            descriptor: DeviceDescriptor::create(
                manufacturer: 'garmin',
                product: 'edge_840',
                serialNumber: '1234567890',
                productName: 'Edge 840',
            ),
        );

        $devices = $this->createMock(
            ActivityDeviceWriter::class,
        );

        $devices
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(
                    static fn ($actualActivityId): bool => $activityId
                        ->equals($actualActivityId),
                ),
                $device,
            );

        $useCase = new SaveActivityDevice(
            devices: $devices,
        );

        $useCase->handle(
            activityId: $activityId,
            device: $device,
        );
    }

    public function testCanSaveUnknownActivityDevice(): void
    {
        $activityId = ActivityId::generate();

        $device = ActivityDevice::unknown(
            ActivityDeviceId::generate(),
        );

        $devices = $this->createMock(
            ActivityDeviceWriter::class,
        );

        $devices
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(
                    static fn ($actualActivityId): bool => $activityId
                        ->equals($actualActivityId),
                ),
                $device,
            );

        $useCase = new SaveActivityDevice(
            devices: $devices,
        );

        $useCase->handle(
            activityId: $activityId,
            device: $device,
        );
    }
}
