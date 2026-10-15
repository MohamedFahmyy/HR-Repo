<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\DTOs;

use DateTimeImmutable;

class RawBiometricPunchDTO
{
    public function __construct(
        public readonly int $deviceId,
        public readonly string $deviceUserId,
        public readonly DateTimeImmutable $punchTime,
        public readonly int $punchState = 0, // 0=Check-in, 1=Check-out, 2=Break-out, 3=Break-in, 4=OT-in, 5=OT-out
        public readonly int $verifyType = 1, // 1=Fingerprint, 2=Password, 3=Card, 4=Face
        public readonly int $workCode = 0,
        public readonly ?string $rawPayload = null
    ) {}

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'device_user_id' => $this->deviceUserId,
            'punch_time' => $this->punchTime->format('Y-m-d H:i:s'),
            'punch_state' => $this->punchState,
            'verify_type' => $this->verifyType,
            'work_code' => $this->workCode,
            'raw_payload' => $this->rawPayload,
        ];
    }
}
