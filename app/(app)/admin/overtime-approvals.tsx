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
import { useOvertimeStore } from '@/stores/overtimeStore';
import { useAuthStore } from '@/stores/authStore';

export default function OvertimeApprovalsScreen() {
  const { pendingApprovals, approve, reject, loadPendingApprovals } = useOvertimeStore();
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

  const handleApprove = (id: string, userId: string) => {
    if (!profile) return;
    Alert.alert('Approve Lembur', 'Setujui lembur ini?', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Setujui',
        onPress: async () => {
          try {
            await approve(id, profile.id);
          } catch (err: any) {
            Alert.alert('Gagal', err.message);
          }
        },
      },
    ]);
  };

  const handleReject = (id: string) => {
    if (!profile) return;
    Alert.prompt(
      'Tolak Lembur',
      'Alasan penolakan:',
      async (reason) => {
        if (!reason) return;
        try {
          await reject(id, profile.id, reason);
        } catch (err: any) {
          Alert.alert('Gagal', err.message);
        }
      },
      'plain-text',
      '',
      'default'
    );
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
      </View>

      <View style={styles.detail}>
        <Text style={styles.detailLabel}>Tanggal: {new Date(item.check_in_at).toLocaleDateString('id-ID')}</Text>
        <Text style={styles.detailLabel}>
          Waktu: {new Date(item.check_in_at).toLocaleTimeString('id-ID')}
          {item.check_out_at ? ` - ${new Date(item.check_out_at).toLocaleTimeString('id-ID')}` : ' (aktif)'}
        </Text>
        {item.duration_minutes && (
          <Text style={styles.detailLabel}>Durasi: {Math.round(item.duration_minutes / 60)} jam {item.duration_minutes % 60} menit</Text>
        )}
        {item.check_in_address && (
          <Text style={styles.detailAddress} numberOfLines={2}>{item.check_in_address}</Text>
        )}
      </View>

      <View style={styles.actions}>
        <TouchableOpacity
          style={styles.approveBtn}
          onPress={() => handleApprove(item.id, item.user_id)}
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
            <Text style={styles.emptyText}>Tidak ada pengajuan lembur pending</Text>
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
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  employeeName: { fontSize: 16, fontWeight: '700', color: '#111827' },
  nip: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  detail: { gap: 4, marginBottom: 12 },
  detailLabel: { fontSize: 13, color: '#374151' },
  detailAddress: { fontSize: 12, color: '#9CA3AF', marginTop: 4 },
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
