import { create } from 'zustand';
import * as authApi from '@/services/auth';
import type { LoginResponse } from '@/services/auth';
import { getToken, logout as apiLogout } from '@/services/auth';

interface AuthState {
  token: string | null;
  profile: LoginResponse['user'] | null;
  isLoading: boolean;
  isLoggedIn: boolean;

  login: (email: string, password: string) => Promise<void>;
  register: (params: Parameters<typeof authApi.register>[0]) => Promise<void>;
  logout: () => Promise<void>;
  forgotPassword: (email: string) => Promise<void>;
  loadSession: () => Promise<void>;
  fetchProfile: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  token: null,
  profile: null,
  isLoading: true,
  isLoggedIn: false,

  login: async (email, password) => {
    const result = await authApi.login(email, password);
    set({
      token: result.token,
      profile: result.user,
      isLoggedIn: true,
    });
  },

  register: async (params) => {
    await authApi.register(params);
  },

  logout: async () => {
    await apiLogout();
    set({ token: null, profile: null, isLoggedIn: false });
  },

  forgotPassword: async (email) => {
    await authApi.forgotPassword(email);
  },

  loadSession: async () => {
    try {
      const token = await getToken();
      if (!token) {
        set({ isLoading: false });
        return;
      }
      set({ token, isLoggedIn: true });
      await get().fetchProfile();
    } catch {
      set({ isLoading: false });
    }
  },

  fetchProfile: async () => {
    try {
      const profile = await authApi.getProfile();
      set({ profile, isLoading: false });
    } catch {
      set({ isLoading: false });
    }
  },
}));
