import { Platform } from 'react-native';
import * as Location from 'expo-location';

export interface GpsData {
  latitude: number;
  longitude: number;
  altitude: number | null;
  accuracy: number | null;
  provider: string;
  timestamp: number;
}

export interface AntiFakeResult {
  score: number;
  flags: string[];
  isSuspect: boolean;
  details: Record<string, boolean>;
}

export async function getCurrentPosition(): Promise<GpsData[]> {
  const results: GpsData[] = [];

  // Request permissions
  const { status } = await Location.requestForegroundPermissionsAsync();
  if (status !== 'granted') {
    throw new Error('Izin lokasi tidak diberikan');
  }

  // Try high accuracy GPS first
  try {
    const pos = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.High,
    });

    results.push({
      latitude: pos.coords.latitude,
      longitude: pos.coords.longitude,
      altitude: pos.coords.altitude || null,
      accuracy: pos.coords.accuracy || null,
      provider: 'gps',
      timestamp: pos.timestamp,
    });
  } catch {
    // Fallback: try lower accuracy
    try {
      const pos = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
      });
      results.push({
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude,
        altitude: pos.coords.altitude || null,
        accuracy: pos.coords.accuracy || null,
        provider: 'gps',
        timestamp: pos.timestamp,
      });
    } catch {
      // Network provider fallback
      try {
        const pos = await Location.getLastKnownPositionAsync({});
        if (pos) {
          results.push({
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            altitude: pos.coords.altitude || null,
            accuracy: pos.coords.accuracy || null,
            provider: 'network',
            timestamp: pos.timestamp,
          });
        }
      } catch {
        throw new Error('Gagal mendapatkan lokasi');
      }
    }
  }

  return results;
}

function calculateDistance(
  lat1: number,
  lon1: number,
  lat2: number,
  lon2: number
): number {
  const R = 6371000; // Earth radius in meters
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

export async function runAntiFakeCheck(gpsData: GpsData[]): Promise<AntiFakeResult> {
  const flags: string[] = [];
  const details: Record<string, boolean> = {};

  // 1. Mock location detection (Android)
  if (Platform.OS === 'android') {
    try {
      const mockEnabled = await Location.isBackgroundLocationAvailableAsync?.();
      // Note: direct mock location detection needs native module
      // Using heuristic: if accuracy is suspiciously exact
      details.mock_location = false; // can't detect directly in Expo
    } catch {
      details.mock_location = false;
    }
  }

  // 2. Multi-provider cross-check
  if (gpsData.length > 1) {
    const [primary, ...others] = gpsData;
    let maxDiff = 0;
    for (const other of others) {
      const diff = calculateDistance(
        primary.latitude,
        primary.longitude,
        other.latitude,
        other.longitude
      );
      if (diff > maxDiff) maxDiff = diff;
    }
    details.multi_provider = maxDiff > 50;
    if (details.multi_provider) flags.push('multi_provider_mismatch');
  }

  // 3. Altitude check
  if (gpsData.length > 0) {
    const primary = gpsData[0];
    details.altitude_suspicious = primary.altitude !== null && primary.altitude < 0;
    if (details.altitude_suspicious) flags.push('suspicious_altitude');
  }

  // 4. GPS timestamp vs device time
  if (gpsData.length > 0) {
    const primary = gpsData[0];
    const deviceTime = Date.now();
    const gpsTime = primary.timestamp;
    const driftMinutes = Math.abs(deviceTime - gpsTime) / 60000;
    details.gps_timestamp_drift = driftMinutes > 120;
    if (details.gps_timestamp_drift) flags.push('gps_timestamp_drift');
  }

  const score = flags.length;
  return {
    score,
    flags,
    isSuspect: score >= 3,
    details,
  };
}

export function isWithinRadius(
  lat1: number,
  lon1: number,
  lat2: number,
  lon2: number,
  radiusMeters: number
): boolean {
  const distance = calculateDistance(lat1, lon1, lat2, lon2);
  return distance <= radiusMeters;
}

export function getPrimaryLocation(gpsData: GpsData[]): GpsData {
  return gpsData[0];
}
