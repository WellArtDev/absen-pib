import { useEffect, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useAttendanceStore } from '@/stores/attendanceStore';
import { useOvertimeStore } from '@/stores/overtimeStore';
import { useLeaveStore } from '@/stores/leaveStore';
import { format } from 'date-fns';
import { id } from 'date-fns/locale/id';

type TabType = 'attendance' | 'overtime' | 'leave';

export default function HistoryScreen() {
  const profile = useAuthStore((s) => s.profile);
  const { history, loadHistory } = useAttendanceStore();
  const overtimeHistory = useOvertimeStore((s) => s.history);
  const loadOvertimeHistory = useOvertimeStore((s) => s.loadHistory);
  const myLeaves = useLeaveStore((s) => s.myLeaves);
  const loadMyLeaves = useLeaveStore((s) => s.loadMyLeaves);
  const [tab, setTab] = useState<TabType>('attendance');
  const [refreshing, setRefreshing] = useState(false);
  const router = useRouter();

  const loadData = async () => {
    if (!profile?.id) return;
    const end = new Date().toISOString();
    const start = new Date(Date.now() - 30 * 24 * 3600000).toISOString();

    await Promise.all([
      loadHistory(profile.id, start, end),
      loadOvertimeHistory(profile.id),
      loadMyLeaves(profile.id),
    ]);
  };

  useEffect(() => {
    loadData();
  }, [profile?.id, tab]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'approved': return '#10B981';
      case 'rejected': return '#EF4444';
      default: return '#F59E0B';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'approved': return 'Disetujui';
      case 'rejected': return 'Ditolak';
      default: return format(new Date(status), 'dd MMM yyyy');
    }
  };

  const renderAttendanceItem = ({ item }: { item: any }) => (
    <TouchableOpacity
      style={styles.itemCard}
      onPress={() => router.push(`/(app)/attendance/${item.id}`)}
    >
      <View style={styles.itemLeft}>
        <View style={[styles.typeBadge, item.type === 'check_in' ? styles.badgeIn : styles.badgeOut]}>
          <Text style={styles.badgeText}>{item.type === 'check_in' ? 'IN' : 'OUT'}</Text>
        </View>
      </View>
      <View style={styles.itemCenter}>
        <Text style={styles.itemTitle}>
          {format(new Date(item.server_timestamp), 'EEE, dd MMM yyyy', { locale: id })}
        </Text>
        <Text style={styles.itemTime}>
          {format(new Date(item.server_timestamp), 'HH:mm')}
          {item.is_late && ' • Terlambat'}
          {item.is_suspect && ' • ⚠️ Mencurigakan'}
        </Text>
        {item.address && <Text style={styles.itemAddress} numberOfLines={1}>{item.address}</Text>}
      </View>
      <Text style={styles.arrow}>›</Text>
    </TouchableOpacity>
  );

  const renderOvertimeItem = ({ item }: { item: any }) => (
    <TouchableOpacity
      style={styles.itemCard}
      onPress={() => router.push(`/(app)/overtime/${item.id}`)}
    >
      <View style={styles.itemLeft}>
        <View style={[styles.typeBadge, styles.badgeOvertime]}>
          <Text style={styles.badgeText}>OT</Text>
        </View>
      </View>
      <View style={styles.itemCenter}>
        <Text style={styles.itemTitle}>
          Lembur {format(new Date(item.check_in_at), 'dd MMM yyyy', { locale: id })}
        </Text>
        <Text style={styles.itemTime}>
          {format(new Date(item.check_in_at), 'HH:mm')}
          {item.check_out_at ? ` - ${format(new Date(item.check_out_at), 'HH:mm')}` : ' (aktif)'}
        </Text>
        <Text style={[styles.statusLabel, { color: getStatusColor(item.status) }]}>
          {item.status === 'approved' ? 'Disetujui' : item.status === 'rejected' ? 'Ditolak' : 'Menunggu'}
        </Text>
      </View>
      <Text style={styles.arrow}>›</Text>
    </TouchableOpacity>
  );

  const renderLeaveItem = ({ item }: { item: any }) => (
    <TouchableOpacity
      style={styles.itemCard}
      onPress={() => router.push(`/(app)/leave/${item.id}`)}
    >
      <View style={styles.itemLeft}>
        <View style={[styles.typeBadge, styles.badgeLeave]}>
          <Text style={styles.badgeText}>C</Text>
        </View>
      </View>
      <View style={styles.itemCenter}>
        <Text style={styles.itemTitle}>
          {item.leave_type === 'tahunan' ? 'Cuti Tahunan' :
           item.leave_type === 'sakit' ? 'Cuti Sakit' :
           item.leave_type === 'darurat' ? 'Cuti Darurat' : 'Cuti Lainnya'}
        </Text>
        <Text style={styles.itemTime}>
          {format(new Date(item.start_date), 'dd MMM')} - {format(new Date(item.end_date), 'dd MMM yyyy')}
          {' • '}{item.total_days} hari
        </Text>
        <Text style={[styles.statusLabel, { color: getStatusColor(item.status) }]}>
          {item.status === 'approved' ? 'Disetujui' : item.status === 'rejected' ? 'Ditolak' : 'Menunggu'}
        </Text>
      </View>
      <Text style={styles.arrow}>›</Text>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Tab Bar */}
      <View style={styles.tabBar}>
        {(['attendance', 'overtime', 'leave'] as TabType[]).map((t) => (
          <TouchableOpacity
            key={t}
            style={[styles.tab, tab === t && styles.tabActive]}
            onPress={() => setTab(t)}
          >
            <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>
              {t === 'attendance' ? 'Absensi' : t === 'overtime' ? 'Lembur' : 'Cuti'}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Lists */}
      {tab === 'attendance' && (
        <FlatList
          data={history}
          keyExtractor={(item) => item.id}
          renderItem={renderAttendanceItem}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>Belum ada riwayat absensi</Text>
            </View>
          }
        />
      )}
      {tab === 'overtime' && (
        <FlatList
          data={overtimeHistory}
          keyExtractor={(item) => item.id}
          renderItem={renderOvertimeItem}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>Belum ada riwayat lembur</Text>
            </View>
          }
        />
      )}
      {tab === 'leave' && (
        <FlatList
          data={myLeaves}
          keyExtractor={(item) => item.id}
          renderItem={renderLeaveItem}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>Belum ada riwayat cuti</Text>
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    padding: 4,
    margin: 16,
    marginBottom: 0,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  tab: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 10,
  },
  tabActive: { backgroundColor: '#2563EB' },
  tabText: { fontSize: 14, fontWeight: '600', color: '#6B7280' },
  tabTextActive: { color: '#fff' },
  list: { padding: 16, paddingTop: 8, gap: 8 },
  itemCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  itemLeft: { marginRight: 12 },
  typeBadge: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeIn: { backgroundColor: '#D1FAE5' },
  badgeOut: { backgroundColor: '#FEF3C7' },
  badgeOvertime: { backgroundColor: '#DBEAFE' },
  badgeLeave: { backgroundColor: '#F3E8FF' },
  badgeText: { fontWeight: '800', fontSize: 12, color: '#111827' },
  itemCenter: { flex: 1 },
  itemTitle: { fontSize: 15, fontWeight: '600', color: '#111827' },
  itemTime: { fontSize: 13, color: '#6B7280', marginTop: 2 },
  itemAddress: { fontSize: 12, color: '#9CA3AF', marginTop: 2 },
  statusLabel: { fontSize: 12, fontWeight: '600', marginTop: 2 },
  arrow: { fontSize: 20, color: '#D1D5DB', fontWeight: '300', marginLeft: 8 },
  empty: { padding: 48, alignItems: 'center' },
  emptyText: { color: '#9CA3AF', fontSize: 15 },
});
