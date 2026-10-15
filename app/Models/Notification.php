<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = [
        'user_id',
        'title',
        'message',
        'link',
        'is_read',
        'read_at'
    ];

    public static function createForUser(int $userId, string $title, string $message, ?string $link = null): void
    {
        $sql = "INSERT INTO `notifications` (`user_id`, `title`, `message`, `link`) VALUES (:user_id, :title, :message, :link)";
        Database::execute($sql, [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link
        ]);
    }

    public function getUnreadForUser(int $userId): array
    {
        return Database::fetchAll(
            "SELECT * FROM `notifications` WHERE `user_id` = :uid AND `is_read` = 0 ORDER BY `id` DESC LIMIT 10",
            ['uid' => $userId]
        );
    }

    public function markAsRead(int $id, int $userId): bool
    {
        return Database::execute(
            "UPDATE `notifications` SET `is_read` = 1, `read_at` = NOW() WHERE `id` = :id AND `user_id` = :uid",
            ['id' => $id, 'uid' => $userId]
        );
    }
}
