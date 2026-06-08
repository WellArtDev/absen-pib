import { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams } from 'expo-router';
import { getLeaveById } from '@/services/leave';
import type { LeaveRecord } from '@/services/leave';
import { format } from 'date-fns';
import { id } from 'date-fns/locale/id';

export default function LeaveDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [record, setRecord] = useState<LeaveRecord | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (id) {
      getLeaveById(id)
        .then(setRecord)
        .catch(() => {})
        .finally(() => setLoading(false));
    }
  }, [id]);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  if (!record) {
    return (
      <View style={styles.center}>
        <Text style={styles.emptyText}>Data tidak ditemukan</Text>
      </View>
    );
  }

  const leaveTypeLabel = (type: string) => {
    switch (type) {
      case 'tahunan': return 'Cuti Tahunan';
      case 'sakit': return 'Cuti Sakit';
      case 'darurat': return 'Cuti Darurat';
      default: return 'Cuti Lainnya';
    }
  };

  const statusConfig = (status: string) => {
    switch (status) {
      case 'approved': return { bg: '#D1FAE5', color: '#059669', label: 'Disetujui' };
      case 'rejected': return { bg: '#FEE2E2', color: '#DC2626', label: 'Ditolak' };
      default: return { bg: '#FEF3C7', color: '#D97706', label: 'Menunggu Approval' };
    }
  };

  const status = statusConfig(record.status);

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Status */}
      <View style={[styles.statusCard, { backgroundColor: status.bg }]}>
        <Text style={[styles.statusTitle, { color: status.color }]}>
          {status.label}
        </Text>
      </View>

      {/* Detail */}
      <View style={styles.card}>
        <Text style={styles.sectionTitle}>{leaveTypeLabel(record.leave_type)}</Text>

        <View style={styles.row}>
          <Text style={styles.label}>Tanggal</Text>
          <Text style={styles.value}>
            {format(new Date(record.start_date), 'dd MMMM yyyy', { locale: id })} - {format(new Date(record.end_date), 'dd MMMM yyyy', { locale: id })}
          </Text>
        </View>

        <View style={styles.row}>
          <Text style={styles.label}>Durasi</Text>
          <Text style={styles.value}>{record.total_days} hari</Text>
        </View>

        <View style={styles.row}>
          <Text style={styles.label}>Alasan</Text>
          <Text style={styles.value}>{record.reason}</Text>
        </View>

        {record.attachment_url && (
          <View style={styles.row}>
            <Text style={styles.label}>Lampiran</Text>
            <Text style={[styles.value, { color: '#2563EB' }]}>Tersedia</Text>
          </View>
        )}

        {record.rejection_reason && (
          <View style={styles.row}>
            <Text style={styles.label}>Alasan Ditolak</Text>
            <Text style={[styles.value, { color: '#DC2626' }]}>{record.rejection_reason}</Text>
          </View>
        )}

        <View style={styles.row}>
          <Text style={styles.label}>Diajukan Pada</Text>
          <Text style={styles.value}>
            {format(new Date(record.created_at), 'dd MMMM yyyy, HH:mm', { locale: id })}
          </Text>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  content: { padding: 16, gap: 16, paddingBottom: 48 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  emptyText: { color: '#9CA3AF', fontSize: 15 },
  statusCard: {
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  statusTitle: { fontSize: 18, fontWeight: '800' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  sectionTitle: { fontSize: 17, fontWeight: '700', color: '#111827', marginBottom: 16 },
  row: { marginBottom: 14 },
  label: { fontSize: 12, fontWeight: '600', color: '#9CA3AF', textTransform: 'uppercase', marginBottom: 2 },
  value: { fontSize: 15, color: '#111827', lineHeight: 22 },
});
