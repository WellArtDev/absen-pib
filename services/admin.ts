import api from './api';

export interface Employee {
  id: number;
  company_id: number;
  office_id: number | null;
  role: string;
  nip: string;
  full_name: string;
  email: string;
  avatar_url: string | null;
  phone: string | null;
  leave_quota_total: number;
  leave_quota_used: number;
  is_active: number;
  created_at: string;
}

export interface DashboardStats {
  total_employees: number;
  present_today: number;
  late_today: number;
  absent_today: number;
  pending_overtime: number;
  pending_leaves: number;
}

export async function getDashboard(): Promise<DashboardStats> {
  const { data } = await api.get('/admin/dashboard');
  return data.data;
}

export async function getEmployees(page = 1, limit = 20) {
  const { data } = await api.get('/admin/employees', { params: { page, limit } });
  return data; // includes meta
}

export async function createEmployee(params: {
  nip: string;
  full_name: string;
  email: string;
  password: string;
  role: string;
  office_id?: number;
}): Promise<Employee> {
  const { data } = await api.post('/admin/employees', params);
  return data.data;
}

export async function getEmployeeDetail(id: number) {
  const { data } = await api.get(`/admin/employees/${id}`);
  return data.data;
}

export async function updateEmployee(id: number, params: Record<string, unknown>) {
  const { data } = await api.put(`/admin/employees/${id}`, params);
  return data;
}
