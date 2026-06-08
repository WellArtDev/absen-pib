import { create } from 'zustand';
import * as api from '@/services/leave';
import type { LeaveRecord } from '@/services/leave';

interface LeaveState {
  myLeaves: LeaveRecord[];
  pendingApprovals: (LeaveRecord & { full_name: string; nip: string; leave_quota_total: number; leave_quota_used: number })[];
  remainingQuota: number;
  isLoading: boolean;

  submitLeave: (params: Parameters<typeof api.submitLeave>[0]) => Promise<void>;
  loadMyLeaves: () => Promise<void>;
  loadPendingApprovals: () => Promise<void>;
  loadRemainingQuota: () => Promise<void>;
  approveLeave: (leaveId: number) => Promise<void>;
  rejectLeave: (leaveId: number, reason: string) => Promise<void>;
}

export const useLeaveStore = create<LeaveState>((set, get) => ({
  myLeaves: [],
  pendingApprovals: [],
  remainingQuota: 12,
  isLoading: false,

  submitLeave: async (params) => {
    const record = await api.submitLeave(params);
    set({ myLeaves: [record, ...get().myLeaves] });
  },

  loadMyLeaves: async () => {
    set({ isLoading: true });
    try {
      const records = await api.getMyLeaves();
      set({ myLeaves: records });
    } finally {
      set({ isLoading: false });
    }
  },

  loadPendingApprovals: async () => {
    const records = await api.getPendingLeaves();
    set({ pendingApprovals: records });
  },

  loadRemainingQuota: async () => {
    const { remaining } = await api.getLeaveQuota();
    set({ remainingQuota: remaining });
  },

  approveLeave: async (leaveId) => {
    await api.approveLeave(leaveId);
    set({ pendingApprovals: get().pendingApprovals.filter((l) => l.id !== leaveId) });
  },

  rejectLeave: async (leaveId, reason) => {
    await api.rejectLeave(leaveId, reason);
    set({ pendingApprovals: get().pendingApprovals.filter((l) => l.id !== leaveId) });
  },
}));
