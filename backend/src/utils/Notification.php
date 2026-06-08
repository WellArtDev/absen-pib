<?php
declare(strict_types=1);

namespace App\Utils;

use App\Database;

/**
 * Free notification via ntfy.sh
 * Also logs to database for history.
 */
class Notification
{
    private string $ntfyUrl = 'https://ntfy.sh';

    public function sendToCompany(int $companyId, string $title, string $message, string $type = 'system'): void
    {
        $topic = 'absen-pib-' . $companyId;

        // Send via ntfy.sh (non-blocking @ for fire-and-forget)
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->ntfyUrl . '/' . $topic,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $message,
            CURLOPT_HTTPHEADER => [
                'Title: ' . $title,
                'Priority: default',
                'Tags: ' . $type,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
        curl_exec($ch);
        curl_close($ch);

        // Log to database
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO notifications (company_id, title, message, type) VALUES (:cid, :title, :msg, :type)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':title' => $title,
            ':msg' => $message,
            ':type' => $type,
        ]);
    }

    public function sendToUser(int $userId, int $companyId, string $title, string $message, string $type = 'system'): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO notifications (company_id, user_id, title, message, type) VALUES (:cid, :uid, :title, :msg, :type)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
            ':title' => $title,
            ':msg' => $message,
            ':type' => $type,
        ]);

        // Also try FCM if user has token
        $stmt = $db->prepare('SELECT fcm_token FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if ($user && $user['fcm_token']) {
            $this->sendFcm($user['fcm_token'], $title, $message);
        }
    }

    private function sendFcm(string $token, string $title, string $body): void
    {
        // FCM via Firebase HTTP v1 (requires server key)
        // For free alternative, ntfy.sh is already used above
        // FCM implementation optional if budget for Firebase
    }

    public function getNotifications(int $userId, int $limit = 50): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM notifications WHERE user_id = :uid OR user_id IS NULL
             ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markRead(int $notifId): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
        $stmt->execute([':id' => $notifId]);
    }
}
