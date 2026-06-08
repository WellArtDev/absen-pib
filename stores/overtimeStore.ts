import { create } from 'zustand';
import * as api from '@/services/overtime';
import type { OvertimeRecord } from '@/services/overtime';

interface OvertimeState {
  activeOvertime: OvertimeRecord | null;
  history: OvertimeRecord[];
  pendingApprovals: (OvertimeRecord & { full_name: string; nip: string })[];
  isLoading: boolean;

  startOvertime: (params: {
    photo: string;
    latitude: number;
    longitude: number;
    address?: string;
  }) => Promise<void>;
  endOvertime: (params: {
    overtime_id: number;
    photo: string;
    latitude: number;
    longitude: number;
    address?: string;
  }) => Promise<void>;
  loadHistory: () => Promise<void>;
  loadPendingApprovals: () => Promise<void>;
  approve: (overtimeId: number) => Promise<void>;
  reject: (overtimeId: number, reason: string) => Promise<void>;
}

export const useOvertimeStore = create<OvertimeState>((set, get) => ({
  activeOvertime: null,
  history: [],
  pendingApprovals: [],
  isLoading: false,

  startOvertime: async (params) => {
    const record = await api.startOvertime(params);
    set({ activeOvertime: record });
  },

  endOvertime: async (params) => {
    const record = await api.endOvertime(params);
    set({ activeOvertime: null });
    set({ history: [record, ...get().history] });
  },

  loadHistory: async () => {
    set({ isLoading: true });
    try {
      const records = await api.getMyOvertimeHistory();
      set({ history: records });
    } finally {
      set({ isLoading: false });
    }
  },

  loadPendingApprovals: async () => {
    const records = await api.getPendingOvertimes();
    set({ pendingApprovals: records });
  },

  approve: async (overtimeId) => {
    await api.approveOvertime(overtimeId);
    set({ pendingApprovals: get().pendingApprovals.filter((o) => o.id !== overtimeId) });
  },

  reject: async (overtimeId, reason) => {
    await api.rejectOvertime(overtimeId, reason);
    set({ pendingApprovals: get().pendingApprovals.filter((o) => o.id !== overtimeId) });
  },
}));
