import { useEffect, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  Alert,
  RefreshControl,
} from 'react-native';
import { useLeaveStore } from '@/stores/leaveStore';
import { useAuthStore } from '@/stores/authStore';

export default function LeaveApprovalsScreen() {
  const { pendingApprovals, approveLeave, rejectLeave, loadPendingApprovals } = useLeaveStore();
  const profile = useAuthStore((s) => s.profile);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    loadPendingApprovals();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadPendingApprovals();
    setRefreshing(false);
  };

  const handleApprove = (leaveId: string, userId: string, totalDays: number) => {
    if (!profile) return;
    Alert.alert('Approve Cuti', 'Setujui pengajuan cuti ini? Kuota akan dikurangi.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Setujui',
        onPress: async () => {
          try {
            await approveLeave(leaveId, userId, profile.id, totalDays);
          } catch (err: any) {
            Alert.alert('Gagal', err.message);
          }
        },
      },
    ]);
  };

  const handleReject = (leaveId: string) => {
    if (!profile) return;
    Alert.prompt(
      'Tolak Cuti',
      'Alasan penolakan:',
      async (reason) => {
        if (!reason) return;
        try {
          await rejectLeave(leaveId, profile.id, reason);
        } catch (err: any) {
          Alert.alert('Gagal', err.message);
        }
      },
      'plain-text',
      '',
      'default'
    );
  };

  const leaveTypeLabel = (type: string) => {
    switch (type) {
      case 'tahunan': return 'Cuti Tahunan';
      case 'sakit': return 'Cuti Sakit';
      case 'darurat': return 'Cuti Darurat';
      default: return 'Lainnya';
    }
  };

  const renderItem = ({ item }: { item: any }) => (
    <View style={styles.card}>
      <View style={styles.header}>
        <View>
          <Text style={styles.employeeName}>
            {item.profiles?.full_name || 'Karyawan'}
          </Text>
          <Text style={styles.nip}>NIP: {item.profiles?.nip || '-'}</Text>
        </View>
        <View style={styles.typeBadge}>
          <Text style={styles.typeText}>{leaveTypeLabel(item.leave_type)}</Text>
        </View>
      </View>

      <View style={styles.detail}>
        <Text style={styles.detailLabel}>
          Tanggal: {new Date(item.start_date).toLocaleDateString('id-ID')} - {new Date(item.end_date).toLocaleDateString('id-ID')}
        </Text>
        <Text style={styles.detailLabel}>Durasi: {item.total_days} hari</Text>
        <Text style={styles.detailLabel}>Alasan: {item.reason}</Text>
        {item.profiles && (
          <Text style={styles.quotaInfo}>
            Kuota terpakai: {item.profiles.leave_quota_used}/{item.profiles.leave_quota_total} hari
          </Text>
        )}
      </View>

      <View style={styles.actions}>
        <TouchableOpacity
          style={styles.approveBtn}
          onPress={() => handleApprove(item.id, item.user_id, item.total_days)}
        >
          <Text style={styles.approveText}>Setujui</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.rejectBtn}
          onPress={() => handleReject(item.id)}
        >
          <Text style={styles.rejectText}>Tolak</Text>
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={pendingApprovals}
        keyExtractor={(item) => item.id}
        renderItem={renderItem}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyText}>Tidak ada pengajuan cuti pending</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  list: { padding: 16, gap: 12 },
  card: { backgroundColor: '#fff', borderRadius: 12, padding: 16, borderWidth: 1, borderColor: '#E5E7EB' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 },
  employeeName: { fontSize: 16, fontWeight: '700', color: '#111827' },
  nip: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  typeBadge: { backgroundColor: '#F3E8FF', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  typeText: { fontSize: 12, fontWeight: '700', color: '#7C3AED' },
  detail: { gap: 4, marginBottom: 12 },
  detailLabel: { fontSize: 13, color: '#374151' },
  quotaInfo: { fontSize: 12, color: '#2563EB', fontWeight: '600', marginTop: 4 },
  actions: { flexDirection: 'row', gap: 10 },
  approveBtn: {
    flex: 1,
    backgroundColor: '#D1FAE5',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  approveText: { color: '#059669', fontSize: 14, fontWeight: '700' },
  rejectBtn: {
    flex: 1,
    backgroundColor: '#FEE2E2',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  rejectText: { color: '#DC2626', fontSize: 14, fontWeight: '700' },
  empty: { padding: 48, alignItems: 'center' },
  emptyText: { color: '#9CA3AF', fontSize: 15 },
});
