import { create } from 'zustand';
import * as api from '@/services/attendance';
import type { AttendanceRecord } from '@/services/attendance';

interface AttendanceState {
  todayCheckIn: AttendanceRecord | null;
  todayCheckOut: AttendanceRecord | null;
  history: AttendanceRecord[];
  isLoading: boolean;

  loadTodayStatus: () => Promise<void>;
  loadHistory: (startDate: string, endDate: string) => Promise<void>;
  setTodayCheckIn: (record: AttendanceRecord) => void;
  setTodayCheckOut: (record: AttendanceRecord) => void;
}

export const useAttendanceStore = create<AttendanceState>((set) => ({
  todayCheckIn: null,
  todayCheckOut: null,
  history: [],
  isLoading: false,

  loadTodayStatus: async () => {
    set({ isLoading: true });
    try {
      const { check_in, check_out } = await api.getTodayStatus();
      set({ todayCheckIn: check_in, todayCheckOut: check_out });
    } finally {
      set({ isLoading: false });
    }
  },

  loadHistory: async (startDate, endDate) => {
    set({ isLoading: true });
    try {
      const records = await api.getHistory(startDate, endDate);
      set({ history: records });
    } finally {
      set({ isLoading: false });
    }
  },

  setTodayCheckIn: (record) => set({ todayCheckIn: record }),
  setTodayCheckOut: (record) => set({ todayCheckOut: record }),
}));
