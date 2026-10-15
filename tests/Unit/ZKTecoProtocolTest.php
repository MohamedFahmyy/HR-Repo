<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Integrations\ZKTeco\DTOs\RawBiometricPunchDTO;
use App\Integrations\ZKTeco\Adapters\ZKTecoAdmsPushAdapter;
use App\Integrations\ZKTeco\Adapters\ZKTecoMockAdapter;
use DateTimeImmutable;

class ZKTecoProtocolTest extends TestCase
{
    public function testRawBiometricPunchDtoCreationAndIntegrity(): void
    {
        $time = new DateTimeImmutable('2026-10-15 08:02:14');
        $dto = new RawBiometricPunchDTO(
            deviceId: 1,
            deviceUserId: '1001',
            punchTime: $time,
            punchState: 0,
            verifyType: 1,
            workCode: 0
        );

        $this->assertSame(1, $dto->deviceId);
        $this->assertSame('1001', $dto->deviceUserId);
        $this->assertSame('2026-10-15 08:02:14', $dto->punchTime->format('Y-m-d H:i:s'));
        $this->assertSame(0, $dto->punchState);

        $array = $dto->toArray();
        $this->assertArrayHasKey('device_id', $array);
        $this->assertArrayHasKey('device_user_id', $array);
        $this->assertArrayHasKey('punch_time', $array);
        $this->assertArrayHasKey('punch_state', $array);
        $this->assertSame('1001', $array['device_user_id']);
    }

    public function testAdmsPushPayloadParsing(): void
    {
        // Standard ZKTeco ADMS / iClock tab-delimited punch payload
        $rawPayload = "1001\t2026-10-15 08:01:25\t0\t1\t0\n" .
                      "1002\t2026-10-15 08:14:10\t0\t15\t0\n" .
                      "1001\t2026-10-15 17:05:30\t1\t1\t0\n";

        $adapter = new ZKTecoAdmsPushAdapter(1, 'SilkID-ADMS', $rawPayload);
        $punches = $adapter->fetchAttendanceEvents();

        $this->assertCount(3, $punches);

        $this->assertSame('1001', $punches[0]->deviceUserId);
        $this->assertSame('2026-10-15 08:01:25', $punches[0]->punchTime->format('Y-m-d H:i:s'));
        $this->assertSame(0, $punches[0]->punchState);

        $this->assertSame('1002', $punches[1]->deviceUserId);
        $this->assertSame('2026-10-15 08:14:10', $punches[1]->punchTime->format('Y-m-d H:i:s'));

        $this->assertSame('1001', $punches[2]->deviceUserId);
        $this->assertSame('2026-10-15 17:05:30', $punches[2]->punchTime->format('Y-m-d H:i:s'));
        $this->assertSame(1, $punches[2]->punchState);
    }

    public function testMockAdapterReturnsPunchesDeterministically(): void
    {
        $mockAdapter = new ZKTecoMockAdapter(3);
        $this->assertTrue($mockAdapter->testConnection());

        $deviceInfo = $mockAdapter->getDeviceInfo();
        $this->assertSame('Mock Hardware Simulator', $deviceInfo['name']);
        $this->assertSame('online', $deviceInfo['status']);

        $events = $mockAdapter->fetchAttendanceEvents();
        $this->assertNotEmpty($events);
        $this->assertInstanceOf(RawBiometricPunchDTO::class, $events[0]);
    }
}
