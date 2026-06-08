import api from './api';

export interface LeaveRecord {
  id: number;
  user_id: number;
  company_id: number;
  leave_type: 'tahunan' | 'sakit' | 'darurat' | 'lainnya';
  start_date: string;
  end_date: string;
  total_days: number;
  reason: string;
  attachment_url: string | null;
  status: 'pending' | 'approved' | 'rejected';
  approved_by: number | null;
  approved_at: string | null;
  rejection_reason: string | null;
  quota_deducted: number;
  created_at: string;
}

export async function submitLeave(params: {
  leave_type: string;
  start_date: string;
  end_date: string;
  reason: string;
  attachment_url?: string;
}): Promise<LeaveRecord> {
  const { data } = await api.post('/leave/submit', params);
  return data.data;
}

export async function getMyLeaves(): Promise<LeaveRecord[]> {
  const { data } = await api.get('/leave/history');
  return data.data;
}

export async function getPendingLeaves(): Promise<(LeaveRecord & { full_name: string; nip: string; leave_quota_total: number; leave_quota_used: number })[]> {
  const { data } = await api.get('/leave/pending');
  return data.data;
}

export async function getLeaveQuota(): Promise<{ total: number; used: number; remaining: number }> {
  const { data } = await api.get('/leave/quota');
  return data.data;
}

export async function approveLeave(id: number): Promise<void> {
  await api.post(`/leave/${id}/approve`);
}

export async function rejectLeave(id: number, reason: string): Promise<void> {
  await api.post(`/leave/${id}/reject`, { reason });
}
