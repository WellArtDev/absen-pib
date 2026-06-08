export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'http://localhost:8000';

export const FALLBACK_LOCATION = {
  latitude: -6.2088,
  longitude: 106.8456,
  address: 'Jakarta, Indonesia',
};

export const ATTENDANCE_RADIUS_METERS_DEFAULT = 200;
export const MAX_PHOTO_SIZE_BYTES = 1_048_576; // 1MB
export const GPS_ACCURACY_HIGH = 6;
export const SUSPICION_THRESHOLD = 3;
export const MULTI_PROVIDER_DIFF_METERS = 50;
export const IMPOSSIBLE_TRAVEL_METERS = 100_000;
export const IMPOSSIBLE_TRAVEL_MINUTES = 5;
export const GPS_TIMESTAMP_MAX_DRIFT_MINUTES = 120;
export const LEAVE_QUOTA_DEFAULT = 12;
