<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Response;
use App\Utils\Notification;

class NotificationController
{
    public function list(): void
    {
        $userId = Auth::getUserId();
        $notif = new Notification();
        $notifications = $notif->getNotifications($userId);

        Response::success($notifications);
    }

    public function registerFcm(array $body): void
    {
        $userId = Auth::getUserId();
        $db = \App\Database::getInstance();

        $stmt = $db->prepare('UPDATE users SET fcm_token = :token WHERE id = :id');
        $stmt->execute([':token' => $body['fcm_token'] ?? '', ':id' => $userId]);

        Response::success(null, 'Token FCM terdaftar');
    }

    public function markRead(int $id): void
    {
        $notif = new Notification();
        $notif->markRead($id);

        Response::success(null, 'Notifikasi ditandai sudah dibaca');
    }
}
