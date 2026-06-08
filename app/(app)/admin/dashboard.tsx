import { useEffect, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
} from 'react-native';
import { useRouter } from 'expo-router';
import { getAllAttendances } from '@/services/attendance';
import type { AttendanceRecord } from '@/services/attendance';
import { format } from 'date-fns';
import { id } from 'date-fns/locale/id';

export default function AdminDashboard() {
  const router = useRouter();
  const [stats, setStats] = useState({
    totalEmployees: 0,
    presentToday: 0,
    lateToday: 0,
    absentToday: 0,
  });
  const [refreshing, setRefreshing] = useState(false);

  const loadStats = async () => {
    const today = new Date().toISOString().split('T')[0];
    try {
      const records = await getAllAttendances(
        `${today}T00:00:00Z`,
        `${today}T23:59:59Z`
      );
      const checkIns = records.filter((r: AttendanceRecord) => r.type === 'check_in');
      const uniqueUsers = new Set(checkIns.map((r: AttendanceRecord) => r.user_id));

      setStats({
        totalEmployees: 0, // Will be populated later
        presentToday: uniqueUsers.size,
        lateToday: checkIns.filter((r: AttendanceRecord) => r.is_late).length,
        absentToday: 0,
      });
    } catch {
      // Stats will show 0
    }
  };

  useEffect(() => {
    loadStats();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadStats();
    setRefreshing(false);
  };

  return (
    <View style={styles.container}>
      {/* Stats Grid */}
      <View style={styles.statsGrid}>
        <View style={[styles.statCard, { backgroundColor: '#EFF6FF' }]}>
          <Text style={styles.statNumber}>{stats.presentToday}</Text>
          <Text style={styles.statLabel}>Hadir Hari Ini</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#FEF3C7' }]}>
          <Text style={styles.statNumber}>{stats.lateToday}</Text>
          <Text style={styles.statLabel}>Terlambat</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#FEF2F2' }]}>
          <Text style={styles.statNumber}>{stats.absentToday}</Text>
          <Text style={styles.statLabel}>Belum Absen</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#F3E8FF' }]}>
          <Text style={styles.statNumber}>{stats.totalEmployees}</Text>
          <Text style={styles.statLabel}>Total Karyawan</Text>
        </View>
      </View>

      {/* Menu */}
      <View style={styles.menuSection}>
        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/admin/employees')}
        >
          <Text style={styles.menuIcon}>👥</Text>
          <Text style={styles.menuLabel}>Kelola Karyawan</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/admin/overtime-approvals')}
        >
          <Text style={styles.menuIcon}>✅</Text>
          <Text style={styles.menuLabel}>Approval Lembur</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/admin/leave-approvals')}
        >
          <Text style={styles.menuIcon}>📝</Text>
          <Text style={styles.menuLabel}>Approval Cuti</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/admin/reports')}
        >
          <Text style={styles.menuIcon}>📊</Text>
          <Text style={styles.menuLabel}>Export Laporan</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/admin/config')}
        >
          <Text style={styles.menuIcon}>⚙️</Text>
          <Text style={styles.menuLabel}>Konfigurasi Kantor</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>
      </View>

      <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6', padding: 16 },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    minWidth: '45%',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  statNumber: { fontSize: 28, fontWeight: '800', color: '#111827', marginBottom: 4 },
  statLabel: { fontSize: 12, fontWeight: '600', color: '#6B7280' },
  menuSection: {
    backgroundColor: '#fff',
    borderRadius: 12,
    overflow: 'hidden',
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  menuIcon: { fontSize: 20, marginRight: 12 },
  menuLabel: { flex: 1, fontSize: 15, fontWeight: '600', color: '#374151' },
  arrow: { fontSize: 20, color: '#D1D5DB' },
});
