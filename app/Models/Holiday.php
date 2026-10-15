<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Holiday extends Model
{
    protected string $table = 'holidays';
    protected array $fillable = [
        'calendar_id',
        'name_ar',
        'name_en',
        'start_date',
        'end_date',
        'is_recurring'
    ];

    public function isHoliday(string $date): bool
    {
        $sql = "SELECT COUNT(*) FROM `holidays` 
                WHERE (start_date <= :d1 AND end_date >= :d2) 
                   OR (is_recurring = 1 AND DATE_FORMAT(start_date, '%m-%d') <= DATE_FORMAT(:d3, '%m-%d') AND DATE_FORMAT(end_date, '%m-%d') >= DATE_FORMAT(:d4, '%m-%d'))";
        return (int)Database::fetchColumn($sql, ['d1' => $date, 'd2' => $date, 'd3' => $date, 'd4' => $date]) > 0;
    }
}
