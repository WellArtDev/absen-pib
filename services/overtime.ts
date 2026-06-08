import api from './api';

export interface OvertimeRecord {
  id: number;
  user_id: number;
  company_id: number;
  check_in_photo_url: string;
  check_out_photo_url: string | null;
  check_in_lat: number;
  check_in_lng: number;
  check_out_lat: number | null;
  check_out_lng: number | null;
  check_in_address: string | null;
  check_out_address: string | null;
  check_in_at: string;
  check_out_at: string | null;
  duration_minutes: number | null;
  status: 'pending' | 'approved' | 'rejected';
  approved_by: number | null;
  approved_at: string | null;
  rejection_reason: string | null;
  created_at: string;
}

export async function startOvertime(params: {
  photo: string;
  latitude: number;
  longitude: number;
  address?: string;
}): Promise<OvertimeRecord> {
  const { data } = await api.post('/overtime/start', params);
  return data.data;
}

export async function endOvertime(params: {
  overtime_id: number;
  photo: string;
  latitude: number;
  longitude: number;
  address?: string;
}): Promise<OvertimeRecord> {
  const { data } = await api.post('/overtime/end', params);
  return data.data;
}

export async function getMyOvertimeHistory(): Promise<OvertimeRecord[]> {
  const { data } = await api.get('/overtime/history');
  return data.data;
}

export async function getPendingOvertimes(): Promise<(OvertimeRecord & { full_name: string; nip: string })[]> {
  const { data } = await api.get('/overtime/pending');
  return data.data;
}

export async function approveOvertime(id: number): Promise<void> {
  await api.post(`/overtime/${id}/approve`);
}

export async function rejectOvertime(id: number, reason: string): Promise<void> {
  await api.post(`/overtime/${id}/reject`, { reason });
}
