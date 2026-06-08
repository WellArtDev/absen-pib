import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

const QUEUE_KEY = '@attendance_queue';

export interface QueuedAttendance {
  id: string;
  type: 'check_in' | 'check_out';
  photoUri: string;
  latitude: number;
  longitude: number;
  altitude: number | null;
  gpsAccuracy: number | null;
  address: string | null;
  deviceInfo: Record<string, unknown>;
  gpsProviders: string[];
  gpsTimestamp: string;
  queuedAt: string;
}

export async function addToQueue(attendance: Omit<QueuedAttendance, 'id' | 'queuedAt'>): Promise<void> {
  const queue = await getQueue();
  queue.push({
    ...attendance,
    id: `${Date.now()}_${Math.random().toString(36).slice(2)}`,
    queuedAt: new Date().toISOString(),
  });
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
}

export async function getQueue(): Promise<QueuedAttendance[]> {
  try {
    const data = await AsyncStorage.getItem(QUEUE_KEY);
    return data ? JSON.parse(data) : [];
  } catch {
    return [];
  }
}

export async function clearQueue(): Promise<void> {
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify([]));
}

export async function removeFromQueue(id: string): Promise<void> {
  const queue = await getQueue();
  await AsyncStorage.setItem(
    QUEUE_KEY,
    JSON.stringify(queue.filter((q) => q.id !== id))
  );
}

export async function syncQueue(syncFn: (item: QueuedAttendance) => Promise<void>): Promise<number> {
  const queue = await getQueue();
  let synced = 0;

  for (const item of queue) {
    try {
      await syncFn(item);
      await removeFromQueue(item.id);
      synced++;
    } catch {
      break; // Stop on first failure, retry later
    }
  }

  return synced;
}

export function getDeviceInfo(): Record<string, unknown> {
  const constants = Platform.constants as Record<string, unknown>;
  return {
    brand: constants?.Brand || 'unknown',
    model: constants?.Model || 'unknown',
    os: Platform.OS,
    osVersion: Platform.Version,
  };
}
