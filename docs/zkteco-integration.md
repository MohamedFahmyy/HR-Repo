# ZKTeco Biometric Terminal Integration Guide
## Enterprise Native PHP HRIS & Attendance Engine

This document provides complete technical specifications, protocol implementations, network topologies, and physical device configuration instructions for integrating **ZKTeco Biometric Devices** (Fingerprint, Face Recognition, Palm, RFID Card) with this Enterprise HR & Payroll System.

---

### 1. Architectural Overview

The application includes a modular, extensible integration layer located at `app/Integrations/ZKTeco/`:

```
app/Integrations/ZKTeco/
├── Contracts/
│   └── BiometricDeviceAdapterInterface.php
├── DTOs/
│   └── RawBiometricPunchDTO.php
├── Adapters/
│   ├── ZKTecoDirectTcpAdapter.php   (Direct binary socket protocol over TCP port 4370)
│   ├── ZKTecoAdmsPushAdapter.php    (HTTP/HTTPS Push / iClock / SilkID ADMS protocol)
│   └── ZKTecoMockAdapter.php        (Simulated terminal for testing & offline environments)
├── Services/
│   └── BiometricSyncService.php     (Orchestrator, event deduplication & attendance trigger)
└── Exceptions/
    └── BiometricDeviceException.php
```

---

### 2. Supported Communication Protocols

| Protocol | Typical Models | Communication Direction | Network Requirements |
| :--- | :--- | :--- | :--- |
| **ADMS / PUSH (Recommended)** | SilkID, G3, ProBG, SpeedFace V5L, uFace series with ADMS firmware | **Terminal $\rightarrow$ Server** (Outbound HTTP POST) | Works behind NAT, firewalls, and dynamic branch IPs. Terminal only needs outbound internet access to the ERP server. |
| **Direct Socket TCP (Port 4370)** | Standalone LAN terminals (K40, IN01, iClock 360, MB20, etc.) | **Server $\rightarrow$ Terminal** (Inbound TCP socket) | Server must have direct IP connectivity to the terminal. Requires static LAN IP or VPN tunnel for remote branches. |
| **Mock Simulator** | Any test or staging environment | In-memory deterministic simulation | No physical device needed. Fully validates calculation pipelines. |

---

### 3. Protocol Implementation Details

#### Method A: ADMS / iClock Cloud Push (HTTP POST)

ZKTeco devices equipped with ADMS (Automatic Data Master Server) or Push Firmware initiate outbound HTTP requests to the centralized server.

1. **Terminal Configuration in Device Menu:**
   - Navigate to: `Menu` $\rightarrow$ `Comm.` $\rightarrow$ `Cloud Server Server / ADMS / Web Server`.
   - **Server Address:** `your-domain.com` (or server WAN IP).
   - **Server Port:** `80` (or `443` for HTTPS).
   - **Server URL:** `/api/zkteco/iclock/cdata` (or full URL path `http://your-server/test-folder/public/api/zkteco/iclock/cdata`).
   - **Push Interval:** `1 minute` (or real-time push enabled).
   - **Device SN:** Automatically sent by device (e.g. `CJ49202601001`).

2. **Payload Parsing:**
   The terminal posts tab-delimited plain text in the following format:
   ```tsv
   PIN	Time	State	Verify	Workcode
   1001	2026-10-15 08:01:25	0	1	0
   1002	2026-10-15 08:14:10	0	15	0
   1001	2026-10-15 17:05:30	1	1	0
   ```
   - **State (Punch Type):**
     - `0`: Check-in (دخول)
     - `1`: Check-out (خروج)
     - `2`: Break-out (خروج استراحة)
     - `3`: Break-in (عودة من استراحة)
     - `4`: Overtime-in (دخول إضافي)
     - `5`: Overtime-out (خروج إضافي)
   - **Verify (Verification Method):**
     - `1`: Fingerprint (بصمة إصبع)
     - `2`: Password / PIN (كلمة مرور)
     - `3`: RFID Card (بطاقة ممغنطة)
     - `4` / `15`: Face / Palm Biometrics (بصمة وجه / كف)

3. **Server Response:**
   The server responds with an acknowledgement:
   ```
   OK
   ```
   Or `OK: [count]` so the terminal marks punches as uploaded and avoids redundant transmissions.

---

#### Method B: Direct TCP Binary Socket (Port 4370)

The native ZKTeco binary protocol communicates over TCP socket connection on default port `4370`.

1. **Packet Header Format (8 Bytes):**
   - Bytes 0-1: Protocol identifier (`0x5050` for standard, `0x5050` or `0x0000` session).
   - Bytes 2-3: Command code (e.g., `CMD_CONNECT = 1000`, `CMD_EXIT = 1001`, `CMD_ATTLOG_RRQ = 501`).
   - Bytes 4-5: 16-bit Checksum.
   - Bytes 6-7: 16-bit Session ID.

2. **Handshake Sequence:**
   1. Server opens TCP socket: `fsockopen("tcp://{$ip}", 4370, $errCode, $errStr, $timeout)`.
   2. Server transmits `CMD_CONNECT (1000)` packet.
   3. Device responds with `CMD_ACK_OK (2000)` containing a unique 16-bit Session ID.
   4. Server sends `CMD_DISABLEDEVICE (1007)` to lock keypad and prevent concurrent user inputs during download.
   5. Server sends `CMD_ATTLOG_RRQ (501)` to query attendance log buffer.
   6. Device streams attendance packets (40-byte fixed structures in newer firmware, or 14-byte in legacy firmware).
   7. Server sends `CMD_ENABLEDEVICE (1008)` to unlock keypad.
   8. Server sends `CMD_EXIT (1001)` and terminates socket cleanly.

---

### 4. Deduplication & Idempotent Processing Strategy

Attendance devices often register multiple punches within minutes (e.g. an employee touching the scanner twice). The system ensures zero duplicate records and zero data corruption:

1. **Unique Database Constraint on Raw Events:**
   ```sql
   UNIQUE KEY `uk_raw_punch` (`device_id`, `device_user_id`, `punch_time`)
   ```
   Any identical punch transmitted more than once is ignored gracefully using `INSERT IGNORE` or idempotent upsert.

2. **Configurable Duplicate Punch Window:**
   In `config/attendance.php`:
   ```php
   'duplicate_punch_window_minutes' => 5,
   ```
   Punches for the same user within 5 minutes of an existing punch are flagged as redundant.

3. **Separation of Raw Events and Calculated Records:**
   - Raw terminal punches are stored permanently in `biometric_raw_events` as an audit log.
   - The attendance calculation service consumes raw punches, matches them with shift assignments, evaluates grace periods, and generates clean records in `attendance_records`.

---

### 5. Biometric Employee Mapping

ZKTeco devices identify employees using numeric terminal IDs (e.g. User ID `1`, `1001`). In large companies, terminal IDs might differ between branches or devices.

The system uses `biometric_employee_mappings` to decouple device hardware IDs from employee profiles:
- `device_id`: The ID of the physical device.
- `device_user_id`: The user ID registered on the terminal screen.
- `employee_id`: The internal primary key in the `employees` table.
- `card_number`: Optional RFID card serial number.

---

### 6. Physical Device Acceptance Test Procedure

Before declaring hardware integration production-verified in a live client environment, execute the physical device acceptance test:

1. **Step 1: Network Ping & Port Check**
   ```bash
   Test-NetConnection -ComputerName 192.168.1.202 -Port 4370
   ```
2. **Step 2: Admin Portal Device Connection Test**
   - Login to the HRIS Admin Portal.
   - Navigate to: **الأجهزة البيومترية** (`/devices`).
   - Click **اختبار الاتصال** (Test Connection).
   - Verify terminal model, serial number, firmware version, and log count are returned.
3. **Step 3: Physical Punch Test**
   - Register employee fingerprint / face on the terminal with Device User ID `1001`.
   - Ensure employee mapping exists in `/devices` mapping screen.
   - Punch on the device.
   - Run manual sync via `/devices` or execute CLI:
     ```bash
     php bin/sync-attendance.php --device-id=1
     ```
   - Verify punch appears in `biometric_raw_events` and daily attendance reflects the punch.
