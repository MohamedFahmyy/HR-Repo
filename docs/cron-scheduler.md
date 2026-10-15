# Background Task Scheduling & Automation Guide
## Linux Crontab and Windows Task Scheduler

This application does not rely on framework workers or external queue services. All background routines are native PHP CLI scripts protected by database and lockfile locks to guarantee idempotency and prevent overlapping executions.

---

### 1. Available CLI Commands

| Script | Purpose | Recommended Schedule |
| :--- | :--- | :--- |
| `php bin/sync-attendance.php` | Connects to active ZKTeco biometric devices, fetches new attendance logs, and stores raw punches. | **Every 5 to 15 minutes** |
| `php bin/calculate-attendance.php` | Evaluates raw punches, shifts, grace periods, lateness, overtime, and absences for active employees. | **Daily at 23:55** and/or after every sync |
| `php bin/migrate.php` | Runs pending database schema migrations. | During deployment |
| `php bin/seed.php` | Seeds standard roles, permissions, shifts, policies, and test data. | During initial installation |

---

### 2. Linux Crontab Setup

Open crontab for the web user (`www-data` or root):

```bash
sudo crontab -u www-data -e
```

Add the following schedule entries (adjusting `/var/www/html/hr-payroll` to your document root):

```cron
# 1. Sync biometric devices every 10 minutes
*/10 * * * * /usr/bin/php /var/www/html/hr-payroll/bin/sync-attendance.php >> /var/www/html/hr-payroll/storage/logs/cron-sync.log 2>&1

# 2. Run attendance calculations daily at 23:55
55 23 * * * /usr/bin/php /var/www/html/hr-payroll/bin/calculate-attendance.php >> /var/www/html/hr-payroll/storage/logs/cron-calc.log 2>&1

# 3. Recalculate yesterday's overnight attendance daily at 06:30 AM
30 6 * * * /usr/bin/php /var/www/html/hr-payroll/bin/calculate-attendance.php --date=$(date -d "yesterday" +\%Y-\%m-\%d) >> /var/www/html/hr-payroll/storage/logs/cron-calc.log 2>&1
```

---

### 3. Windows Task Scheduler Setup

For Windows environments (e.g., Laragon, IIS, Windows Server):

#### Option A: PowerShell Command Line (Run as Administrator)

```powershell
# Task 1: ZKTeco Device Sync (Runs every 10 minutes)
$Action1 = New-ScheduledTaskAction -Execute "php.exe" -Argument "D:\laragon\www\test-folder\bin\sync-attendance.php" -WorkingDirectory "D:\laragon\www\test-folder"
$Trigger1 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 10) -RepetitionDuration (New-TimeSpan -Days 3650)
Register-ScheduledTask -TaskName "HRIS_ZKTeco_Sync" -Action $Action1 -Trigger $Trigger1 -Description "Synchronizes ZKTeco attendance devices every 10 minutes" -User "SYSTEM"

# Task 2: Daily Attendance Calculation (Runs daily at 23:50)
$Action2 = New-ScheduledTaskAction -Execute "php.exe" -Argument "D:\laragon\www\test-folder\bin\calculate-attendance.php" -WorkingDirectory "D:\laragon\www\test-folder"
$Trigger2 = New-ScheduledTaskTrigger -Daily -At "23:50"
Register-ScheduledTask -TaskName "HRIS_Attendance_Calculation" -Action $Action2 -Trigger $Trigger2 -Description "Calculates daily attendance records at midnight" -User "SYSTEM"
```

#### Option B: Windows GUI Task Scheduler

1. Open **Task Scheduler** (`taskschd.msc`).
2. Click **Create Task** in the right-hand panel.
3. In General tab:
   - Name: `HRIS_ZKTeco_Sync`
   - Run whether user is logged on or not
   - Run with highest privileges
4. In Triggers tab:
   - Begin the task: On a schedule (Daily, Repeat task every 10 minutes for a duration of Indefinitely).
5. In Actions tab:
   - Action: Start a program
   - Program/script: `php` (or full path `C:\path\to\php.exe`)
   - Add arguments: `bin/sync-attendance.php`
   - Start in: `D:\laragon\www\test-folder`

---

### 4. Overlap Prevention & Process Locking

Both CLI commands use non-blocking file locking (`flock(LOCK_EX | LOCK_NB)`):
- If a synchronization job takes longer due to a network delay on one terminal, a second scheduled run will safely skip execution with:
  ```
  [!] Another synchronization process is already running. Exiting.
  ```
- Locks automatically release upon script completion or error termination.
