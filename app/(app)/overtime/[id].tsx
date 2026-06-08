import { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams } from 'expo-router';
import { getOvertimeById } from '@/services/overtime';
import type { OvertimeRecord } from '@/services/overtime';
import { format } from 'date-fns';
import { id } from 'date-fns/locale/id';

export default function OvertimeDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [record, setRecord] = useState<OvertimeRecord | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (id) {
      getOvertimeById(id)
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

  const getStatusColor = (status: string) => (
    status === 'approved' ? { bg: '#D1FAE5', color: '#059669', label: 'Disetujui' } :
    status === 'rejected' ? { bg: '#FEE2E2', color: '#DC2626', label: 'Ditolak' } :
    { bg: '#FEF3C7', color: '#D97706', label: 'Menunggu Approval' }
  );

  const status = getStatusColor(record.status);

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Status Badge */}
      <View style={[styles.statusCard, { backgroundColor: status.bg }]}>
        <Text style={[styles.statusTitle, { color: status.color }]}>
          {status.label}
        </Text>
      </View>

      {/* Info */}
      <View style={styles.card}>
        <Text style={styles.sectionTitle}>Detail Lembur</Text>

        <View style={styles.row}>
          <Text style={styles.label}>Tanggal</Text>
          <Text style={styles.value}>
            {format(new Date(record.check_in_at), 'EEEE, dd MMMM yyyy', { locale: id })}
          </Text>
        </View>

        <View style={styles.row}>
          <Text style={styles.label}>Check In Lembur</Text>
          <Text style={styles.value}>{format(new Date(record.check_in_at), 'HH:mm')}</Text>
        </View>

        {record.check_out_at && (
          <View style={styles.row}>
            <Text style={styles.label}>Check Out Lembur</Text>
            <Text style={styles.value}>{format(new Date(record.check_out_at), 'HH:mm')}</Text>
          </View>
        )}

        {record.duration_minutes && (
          <View style={styles.row}>
            <Text style={styles.label}>Durasi</Text>
            <Text style={styles.value}>
              {Math.floor(record.duration_minutes / 60)} jam {record.duration_minutes % 60} menit
            </Text>
          </View>
        )}

        <View style={styles.row}>
          <Text style={styles.label}>Lokasi Check-In</Text>
          <Text style={styles.value}>{record.check_in_address || `${record.check_in_lat}, ${record.check_in_lng}`}</Text>
        </View>

        {record.check_out_address && (
          <View style={styles.row}>
            <Text style={styles.label}>Lokasi Check-Out</Text>
            <Text style={styles.value}>{record.check_out_address}</Text>
          </View>
        )}

        {record.rejection_reason && (
          <View style={styles.row}>
            <Text style={styles.label}>Alasan Ditolak</Text>
            <Text style={[styles.value, { color: '#DC2626' }]}>{record.rejection_reason}</Text>
          </View>
        )}
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
  sectionTitle: { fontSize: 15, fontWeight: '700', color: '#111827', marginBottom: 16 },
  row: { marginBottom: 12 },
  label: { fontSize: 12, fontWeight: '600', color: '#9CA3AF', textTransform: 'uppercase', marginBottom: 2 },
  value: { fontSize: 15, color: '#111827', lineHeight: 22 },
});
