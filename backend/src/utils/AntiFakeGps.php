<?php
declare(strict_types=1);

namespace App\Utils;

use App\Database;

class AntiFakeGps
{
    /**
     * Server-side GPS spoof detection.
     * Returns [score, flags, isSuspect]
     */
    public static function check(array $record): array
    {
        $flags = [];
        $score = 0;
        $db = Database::getInstance();

        // 1. Duplicate coordinates check (same coords between different users same day)
        $today = date('Y-m-d');
        $stmt = $db->prepare(
            'SELECT id, user_id FROM attendances
             WHERE latitude = :lat AND longitude = :lng
               AND user_id != :uid
               AND DATE(server_timestamp) = :today
             LIMIT 1'
        );
        $stmt->execute([
            ':lat' => $record['latitude'],
            ':lng' => $record['longitude'],
            ':uid' => $record['user_id'],
            ':today' => $today,
        ]);
        if ($stmt->fetch()) {
            $flags[] = 'duplicate_coords';
            $score++;
        }

        // 2. GPS timestamp sanity check
        if (!empty($record['gps_timestamp'])) {
            $gpsTime = strtotime($record['gps_timestamp']);
            $serverTime = time();
            $driftMinutes = abs($serverTime - $gpsTime) / 60;
            if ($driftMinutes > 120) {
                $flags[] = 'gps_timestamp_drift';
                $score++;
            }
        }

        // 3. Impossible travel detection
        $stmt = $db->prepare(
            'SELECT latitude, longitude, server_timestamp FROM attendances
             WHERE user_id = :uid AND type = "check_in"
             ORDER BY server_timestamp DESC LIMIT 1'
        );
        $stmt->execute([':uid' => $record['user_id']]);
        $prev = $stmt->fetch();

        if ($prev) {
            $distance = self::haversine(
                (float) $prev['latitude'],
                (float) $prev['longitude'],
                (float) $record['latitude'],
                (float) $record['longitude']
            );
            $timeDiff = (time() - strtotime($prev['server_timestamp'])) / 60;

            if ($distance > 100000 && $timeDiff < 5) {
                $flags[] = 'impossible_travel';
                $score++;
            }
        }

        return [
            'score' => $score,
            'flags' => $flags,
            'is_suspect' => $score >= 3,
        ];
    }

    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
