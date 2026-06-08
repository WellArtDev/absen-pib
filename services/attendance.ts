import api from './api';

export interface AttendanceRecord {
  id: number;
  user_id: number;
  company_id: number;
  office_id: number | null;
  type: 'check_in' | 'check_out';
  photo_url: string;
  latitude: number;
  longitude: number;
  altitude: number | null;
  gps_accuracy: number | null;
  address: string | null;
  device_info: string | null;
  gps_providers: string | null;
  gps_timestamp: string | null;
  server_timestamp: string;
  is_late: number;
  suspicion_score: number;
  suspicion_flags: string | null;
  is_suspect: number;
  created_at: string;
}

export async function checkIn(params: {
  photo: string; // base64
  latitude: number;
  longitude: number;
  altitude?: number;
  gps_accuracy?: number;
  address?: string;
  device_info?: Record<string, unknown>;
  gps_providers?: string[];
  gps_timestamp?: string;
  office_id?: number;
}): Promise<AttendanceRecord> {
  const { data } = await api.post('/attendance/check-in', params);
  return data.data;
}

export async function checkOut(params: {
  photo: string;
  latitude: number;
  longitude: number;
  altitude?: number;
  gps_accuracy?: number;
  address?: string;
  gps_providers?: string[];
  gps_timestamp?: string;
}): Promise<AttendanceRecord> {
  const { data } = await api.post('/attendance/check-out', params);
  return data.data;
}

export async function getTodayStatus() {
  const { data } = await api.get('/attendance/today');
  return data.data as { check_in: AttendanceRecord | null; check_out: AttendanceRecord | null };
}

export async function getHistory(start: string, end: string, page = 1, limit = 20) {
  const { data } = await api.get('/attendance/history', { params: { start, end, page, limit } });
  return data.data as AttendanceRecord[];
}

export async function getDetail(id: number) {
  const { data } = await api.get(`/attendance/${id}`);
  return data.data as AttendanceRecord;
}

export async function getAll(start: string, end: string) {
  const { data } = await api.get('/attendance/all', { params: { start, end } });
  return data.data as (AttendanceRecord & { full_name: string; nip: string })[];
}
