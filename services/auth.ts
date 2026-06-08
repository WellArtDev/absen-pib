import api from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const TOKEN_KEY = '@auth_token';

export interface LoginResponse {
  token: string;
  user: {
    id: number;
    company_id: number;
    office_id: number | null;
    role: 'superadmin' | 'owner' | 'admin' | 'sales' | 'karyawan';
    nip: string;
    full_name: string;
    email: string;
    avatar_url: string | null;
    leave_quota_total: number;
    leave_quota_used: number;
  };
}

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await api.post('/auth/login', { email, password });
  await AsyncStorage.setItem(TOKEN_KEY, data.data.token);
  return data.data;
}

export async function register(params: {
  company_id: number;
  nip: string;
  full_name: string;
  email: string;
  password: string;
  role: string;
}): Promise<void> {
  await api.post('/auth/register', params);
}

export async function logout(): Promise<void> {
  await AsyncStorage.removeItem(TOKEN_KEY);
}

export async function getToken(): Promise<string | null> {
  return AsyncStorage.getItem(TOKEN_KEY);
}

export async function getProfile() {
  const { data } = await api.get('/profile');
  return data.data;
}

export async function updateProfile(params: { full_name?: string; phone?: string; avatar_url?: string }) {
  const { data } = await api.put('/profile', params);
  return data;
}

export async function forgotPassword(email: string) {
  const { data } = await api.post('/auth/forgot-password', { email });
  return data;
}
