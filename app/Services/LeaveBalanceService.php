<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveRequest;
use RuntimeException;

class LeaveBalanceService
{
    private LeaveBalance $balanceModel;
    private LeaveBalanceTransaction $transModel;
    private LeaveRequest $requestModel;

    public function __construct(
        ?LeaveBalance $balanceModel = null,
        ?LeaveBalanceTransaction $transModel = null,
        ?LeaveRequest $requestModel = null
    ) {
        $this->balanceModel = $balanceModel ?? new LeaveBalance();
        $this->transModel = $transModel ?? new LeaveBalanceTransaction();
        $this->requestModel = $requestModel ?? new LeaveRequest();
    }

    public function getOrCreateBalance(int $employeeId, int $leaveTypeId, ?int $year = null): array
    {
        $year = $year ?? (int)date('Y');
        $record = Database::fetchOne(
            "SELECT * FROM `leave_balances` WHERE employee_id = :emp_id AND leave_type_id = :type_id AND year = :year LIMIT 1",
            ['emp_id' => $employeeId, 'type_id' => $leaveTypeId, 'year' => $year]
        );

        if (!$record) {
            $defaultDays = (float)Database::fetchColumn("SELECT default_days_per_year FROM `leave_types` WHERE id = :id", ['id' => $leaveTypeId]);
            $id = $this->balanceModel->create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
                'opening_balance' => $defaultDays,
                'accrued_days' => 0.00,
                'used_days' => 0.00,
                'current_balance' => $defaultDays,
            ]);

            $this->transModel->create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'transaction_type' => 'opening',
                'amount' => $defaultDays,
                'balance_after' => $defaultDays,
                'notes' => "الرصيد الافتتاحي للعام {$year}",
            ]);

            $record = $this->balanceModel->findOrFail($id);
        }

        return $record;
    }

    public function getRemainingBalance(int $employeeId, int $leaveTypeId, ?int $year = null): float
    {
        $bal = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);
        return (float)($bal['current_balance'] ?? 0.0);
    }

    public function revertBalance(int $employeeId, int $leaveTypeId, float $days, int $requestId = 0, string $reason = '', ?int $year = null, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): bool
    {
        $reqId = $referenceId ?? $requestId;
        $note = $notes ?? $reason;
        return $this->reverseDeduction($employeeId, $leaveTypeId, $days, $reqId, $note);
    }

    public function deductBalance(int $employeeId, int $leaveTypeId, float $days, int $requestId = 0, string $reason = '', ?int $year = null, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): bool
    {
        $reqId = $referenceId ?? $requestId;
        $note = $notes ?? $reason;
        return Database::transaction(function () use ($employeeId, $leaveTypeId, $days, $requestId, $reason) {
            $balanceRecord = $this->getOrCreateBalance($employeeId, $leaveTypeId);
            $currentBalance = (float)$balanceRecord['current_balance'];

            // Check if sufficient balance for paid leaves
            $isPaid = (bool)Database::fetchColumn("SELECT is_paid FROM `leave_types` WHERE id = :id", ['id' => $leaveTypeId]);
            if ($isPaid && $currentBalance < $days) {
                throw new RuntimeException("رصيد الإجازة غير كافٍ. الرصيد المتاح: {$currentBalance} يوم، المطلوب: {$days} يوم.");
            }

            $newBalance = max(0.00, round($currentBalance - $days, 2));
            $usedDays = round((float)$balanceRecord['used_days'] + $days, 2);

            $this->balanceModel->update($balanceRecord['id'], [
                'used_days' => $usedDays,
                'current_balance' => $newBalance,
            ]);

            $this->transModel->create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'transaction_type' => 'deduction',
                'amount' => -$days,
                'balance_after' => $newBalance,
                'reference_type' => 'leave_requests',
                'reference_id' => $requestId,
                'notes' => $reason ?: "خصم رصيد لطلب الإجازة رقم #{$requestId}",
            ]);

            return true;
        });
    }

    public function reverseDeduction(int $employeeId, int $leaveTypeId, float $days, int $requestId, string $reason = ''): bool
    {
        return Database::transaction(function () use ($employeeId, $leaveTypeId, $days, $requestId, $reason) {
            $balanceRecord = $this->getOrCreateBalance($employeeId, $leaveTypeId);
            $currentBalance = (float)$balanceRecord['current_balance'];
            $newBalance = round($currentBalance + $days, 2);
            $usedDays = max(0.00, round((float)$balanceRecord['used_days'] - $days, 2));

            $this->balanceModel->update($balanceRecord['id'], [
                'used_days' => $usedDays,
                'current_balance' => $newBalance,
            ]);

            $this->transModel->create([
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'transaction_type' => 'reversal',
                'amount' => $days,
                'balance_after' => $newBalance,
                'reference_type' => 'leave_requests',
                'reference_id' => $requestId,
                'notes' => $reason ?: "إلغاء واسترجاع رصيد طلب إجازة ملغي رقم #{$requestId}",
            ]);

            return true;
        });
    }
}
