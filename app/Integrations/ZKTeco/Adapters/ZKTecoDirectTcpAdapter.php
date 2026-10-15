<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\Adapters;

use App\Integrations\ZKTeco\Contracts\BiometricDeviceAdapterInterface;
use App\Integrations\ZKTeco\DTOs\RawBiometricPunchDTO;
use App\Integrations\ZKTeco\Exceptions\BiometricDeviceException;
use DateTimeImmutable;
use DateTimeInterface;

class ZKTecoDirectTcpAdapter implements BiometricDeviceAdapterInterface
{
    private const CMD_CONNECT = 1000;
    private const CMD_EXIT = 1001;
    private const CMD_ENABLEDEVICE = 1002;
    private const CMD_DISABLEDEVICE = 1003;
    private const CMD_VERSION = 1100;
    private const CMD_ATTLOG_RRQ = 13;
    private const CMD_ACK_OK = 2000;
    private const CMD_ACK_ERROR = 2001;
    private const CMD_ACK_DATA = 2003;
    private const CMD_PREPARE_DATA = 1500;
    private const CMD_DATA = 1501;

    private int $deviceId;
    private string $ip;
    private int $port;
    private string $commKey;
    private int $timeout;
    private mixed $socket = null;
    private int $sessionId = 0;
    private int $replyId = 0;
    private array $lastStatus = [];

    public function __construct(int $deviceId, string $ip, int $port = 4370, string $commKey = '0', int $timeout = 5)
    {
        $this->deviceId = $deviceId;
        $this->ip = $ip;
        $this->port = $port;
        $this->commKey = $commKey;
        $this->timeout = $timeout;
        $this->lastStatus = [
            'connected' => false,
            'last_check' => date('Y-m-d H:i:s'),
            'protocol' => 'direct_tcp',
            'endpoint' => "{$ip}:{$port}",
        ];
    }

    public function testConnection(): bool
    {
        try {
            $connected = $this->connect();
            if ($connected) {
                $this->disconnect();
                $this->lastStatus['connected'] = true;
                $this->lastStatus['message'] = 'Handshake successfully acknowledged by device.';
                return true;
            }
        } catch (\Throwable $e) {
            $this->lastStatus['connected'] = false;
            $this->lastStatus['message'] = $e->getMessage();
        }

        return false;
    }

    public function getDeviceInfo(): array
    {
        $this->connect();
        try {
            $version = $this->getFirmwareVersion();
            $this->disconnect();

            return [
                'device_id' => $this->deviceId,
                'ip' => $this->ip,
                'port' => $this->port,
                'firmware' => $version,
                'protocol' => 'direct_tcp',
                'status' => 'online',
            ];
        } catch (\Throwable $e) {
            $this->disconnect();
            throw new BiometricDeviceException("Failed to retrieve device info: " . $e->getMessage(), 0, $this->deviceId);
        }
    }

    public function fetchAttendanceEvents(?DateTimeInterface $since = null): array
    {
        $this->connect();
        $punches = [];

        try {
            // Disable device during readout for data consistency
            $this->sendCommand(self::CMD_DISABLEDEVICE);

            // Request attendance log
            $response = $this->sendCommand(self::CMD_ATTLOG_RRQ);
            $responseCode = $this->extractResponseCode($response);

            if ($responseCode === self::CMD_ACK_OK || $responseCode === self::CMD_PREPARE_DATA) {
                $data = $this->receiveDataPacket();
                $punches = $this->parseAttendanceRecords($data, $since);
            }

            // Re-enable device
            $this->sendCommand(self::CMD_ENABLEDEVICE);
            $this->disconnect();

            $this->lastStatus['connected'] = true;
            $this->lastStatus['records_count'] = count($punches);

            return $punches;
        } catch (\Throwable $e) {
            $this->disconnect();
            $this->lastStatus['connected'] = false;
            $this->lastStatus['last_error'] = $e->getMessage();
            throw new BiometricDeviceException("Direct TCP Attendance Sync failed: " . $e->getMessage(), 0, $this->deviceId, [], $e);
        }
    }

    public function getLastSyncStatus(): array
    {
        return $this->lastStatus;
    }

    private function connect(): bool
    {
        $remote = "tcp://{$this->ip}:{$this->port}";
        $errno = 0;
        $errstr = '';

        $this->socket = @stream_socket_client($remote, $errno, $errstr, (float)$this->timeout, STREAM_CLIENT_CONNECT);

        if (!$this->socket) {
            throw new BiometricDeviceException("Unable to connect to {$remote}: [{$errno}] {$errstr}", $errno, $this->deviceId);
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Send connection command
        $this->sessionId = 0;
        $this->replyId = 0;
        $reply = $this->sendCommand(self::CMD_CONNECT);

        $code = $this->extractResponseCode($reply);
        if ($code !== self::CMD_ACK_OK) {
            $this->disconnect();
            throw new BiometricDeviceException("Device refused connection handshake. Response code: {$code}", $code, $this->deviceId);
        }

        // Store session ID returned in reply
        $this->sessionId = $this->extractSessionId($reply);
        return true;
    }

    private function disconnect(): void
    {
        if ($this->socket) {
            try {
                $this->sendCommand(self::CMD_EXIT);
            } catch (\Throwable) {
                // Ignore exit errors
            }
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    private function sendCommand(int $command, string $data = ''): string
    {
        if (!$this->socket) {
            throw new BiometricDeviceException("Socket not connected.", 0, $this->deviceId);
        }

        $this->replyId++;
        $packet = $this->createPacket($command, $data);
        fwrite($this->socket, $packet);

        $response = fread($this->socket, 1024);
        if ($response === false || strlen($response) < 8) {
            throw new BiometricDeviceException("Empty or malformed response from terminal.", 0, $this->deviceId);
        }

        return $response;
    }

    private function createPacket(int $command, string $data = ''): string
    {
        $chksum = 0;
        // Temporary packet for checksum calculation
        $packet = pack('vvvv', $command, $chksum, $this->sessionId, $this->replyId) . $data;
        $chksum = $this->calculateChecksum($packet);

        return pack('vvvv', $command, $chksum, $this->sessionId, $this->replyId) . $data;
    }

    private function calculateChecksum(string $p): int
    {
        $len = strlen($p);
        $sum = 0;
        for ($i = 0; $i < $len; $i += 2) {
            $val = ord($p[$i]);
            if ($i + 1 < $len) {
                $val += (ord($p[$i + 1]) << 8);
            }
            $sum += $val;
            while ($sum > 0xFFFF) {
                $sum = ($sum & 0xFFFF) + ($sum >> 16);
            }
        }
        return (~$sum) & 0xFFFF;
    }

    private function extractResponseCode(string $response): int
    {
        if (strlen($response) < 2) return -1;
        $unpacked = unpack('vcode', substr($response, 0, 2));
        return $unpacked['code'] ?? -1;
    }

    private function extractSessionId(string $response): int
    {
        if (strlen($response) < 6) return 0;
        $unpacked = unpack('v', substr($response, 4, 2));
        return $unpacked[1] ?? 0;
    }

    private function getFirmwareVersion(): string
    {
        $res = $this->sendCommand(self::CMD_VERSION);
        if (strlen($res) > 8) {
            return trim(substr($res, 8));
        }
        return 'Standard ZK Ver 6.6+';
    }

    private function receiveDataPacket(): string
    {
        $buffer = '';
        while ($chunk = fread($this->socket, 4096)) {
            $buffer .= $chunk;
            if (strlen($chunk) < 4096) {
                break;
            }
        }
        return $buffer;
    }

    private function parseAttendanceRecords(string $data, ?DateTimeInterface $since): array
    {
        $records = [];
        if (empty($data)) {
            return $records;
        }

        // ZKTeco attendance log packets are typically 40 bytes per record in newer firmware or 14 bytes in older
        $dataLen = strlen($data);
        $recordSize = 40;

        if ($dataLen % 40 !== 0 && $dataLen % 14 === 0) {
            $recordSize = 14;
        }

        $offset = ($dataLen >= 8 && substr($data, 0, 4) === "\x50\x50\x82\x7d") ? 8 : 0;

        while ($offset + $recordSize <= $dataLen) {
            $recordData = substr($data, $offset, $recordSize);
            $offset += $recordSize;

            if ($recordSize === 40) {
                // 40-byte record: UserID (24 bytes string), VerifyType (1 byte), State (1 byte), Timestamp (encoded 4 bytes or string)
                $userId = trim(substr($recordData, 0, 24));
                $verifyType = ord($recordData[24]);
                $state = ord($recordData[25]);
                $timestampBytes = unpack('V', substr($recordData, 26, 4))[1] ?? 0;
                $punchDate = $this->decodeZKTime($timestampBytes);
            } else {
                // 14-byte legacy record
                $unpacked = unpack('vuser/Cstate/Vtime/Cverify', $recordData);
                $userId = (string)($unpacked['user'] ?? '');
                $state = $unpacked['state'] ?? 0;
                $verifyType = $unpacked['verify'] ?? 1;
                $punchDate = $this->decodeZKTime($unpacked['time'] ?? 0);
            }

            if (!empty($userId) && $punchDate !== null) {
                if ($since !== null && $punchDate < $since) {
                    continue;
                }

                $records[] = new RawBiometricPunchDTO(
                    deviceId: $this->deviceId,
                    deviceUserId: $userId,
                    punchTime: DateTimeImmutable::createFromInterface($punchDate),
                    punchState: $state,
                    verifyType: $verifyType,
                    rawPayload: bin2hex($recordData)
                );
            }
        }

        return $records;
    }

    private function decodeZKTime(int $zkTime): ?\DateTime
    {
        if ($zkTime <= 0) return null;

        $second = $zkTime % 60;
        $zkTime = (int)($zkTime / 60);
        $minute = $zkTime % 60;
        $zkTime = (int)($zkTime / 60);
        $hour = $zkTime % 24;
        $zkTime = (int)($zkTime / 24);
        $day = ($zkTime % 31) + 1;
        $zkTime = (int)($zkTime / 31);
        $month = ($zkTime % 12) + 1;
        $zkTime = (int)($zkTime / 12);
        $year = $zkTime + 2000;

        $dt = new \DateTime();
        $dt->setDate($year, $month, $day);
        $dt->setTime($hour, $minute, $second);
        return $dt;
    }
}
