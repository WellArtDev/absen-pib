import { useEffect, useState } from 'react';
import {
  View,
  Text,
  Image,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Dimensions,
} from 'react-native';
import { useLocalSearchParams } from 'expo-router';
import { getAttendanceById } from '@/services/attendance';
import type { AttendanceRecord } from '@/services/attendance';
import { format } from 'date-fns';
import { id } from 'date-fns/locale/id';

const { width } = Dimensions.get('window');

export default function AttendanceDetailScreen() {
  const { id: attendanceId } = useLocalSearchParams<{ id: string }>();
  const [record, setRecord] = useState<AttendanceRecord | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (attendanceId) {
      getAttendanceById(attendanceId)
        .then(setRecord)
        .catch(() => {})
        .finally(() => setLoading(false));
    }
  }, [attendanceId]);

  if (loading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  if (!record) {
    return (
      <View style={styles.loading}>
        <Text style={styles.errorText}>Data tidak ditemukan</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Photo */}
      <View style={styles.photoCard}>
        <Image
          source={{ uri: record.photo_url }}
          style={styles.photo}
          resizeMode="cover"
        />
        <View style={styles.photoBadge}>
          <Text style={styles.photoBadgeText}>
            {record.type === 'check_in' ? '📷 Check In' : '📷 Check Out'}
          </Text>
        </View>
      </View>

      {/* Info */}
      <View style={styles.infoCard}>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Waktu</Text>
          <Text style={styles.infoValue}>
            {format(new Date(record.server_timestamp), 'EEEE, dd MMMM yyyy', { locale: id })}
          </Text>
          <Text style={styles.infoSubValue}>
            {format(new Date(record.server_timestamp), 'HH:mm:ss')} WIB
          </Text>
        </View>

        <View style={styles.divider} />

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Status</Text>
          <View style={styles.statusRow}>
            <View style={[styles.statusBadge, record.is_late ? styles.lateBadge : styles.onTimeBadge]}>
              <Text style={[styles.statusText, record.is_late ? styles.lateText : styles.onTimeText]}>
                {record.is_late ? 'Terlambat' : 'Tepat Waktu'}
              </Text>
            </View>
            {record.is_suspect && (
              <View style={styles.suspectBadge}>
                <Text style={styles.suspectText}>⚠️ Mencurigakan</Text>
              </View>
            )}
          </View>
        </View>

        <View style={styles.divider} />

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Lokasi</Text>
          <Text style={styles.infoValue}>
            {record.address || `${record.latitude}, ${record.longitude}`}
          </Text>
          <Text style={styles.infoSubValue}>
            GPS Accuracy: {record.gps_accuracy ? `${Math.round(record.gps_accuracy)}m` : '-'}
          </Text>
        </View>

        <View style={styles.divider} />

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Koordinat</Text>
          <Text style={styles.infoValue}>
            {record.latitude.toFixed(6)}, {record.longitude.toFixed(6)}
          </Text>
          {record.altitude && (
            <Text style={styles.infoSubValue}>
              Altitude: {Math.round(record.altitude)}m
            </Text>
          )}
        </View>

        {/* Anti-fake info */}
        {record.suspicion_score > 0 && (
          <>
            <View style={styles.divider} />
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Anti-Fake</Text>
              <Text style={styles.infoValue}>
                Score: {record.suspicion_score}/5
              </Text>
              {record.suspicion_flags && (
                <Text style={styles.infoSubValue}>
                  Flags: {record.suspicion_flags.join(', ')}
                </Text>
              )}
            </View>
          </>
        )}

        {/* Device */}
        <View style={styles.divider} />
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Perangkat</Text>
          {record.device_info ? (
            <>
              <Text style={styles.infoValue}>
                {String(record.device_info.brand)} {String(record.device_info.model)}
              </Text>
              <Text style={styles.infoSubValue}>
                OS: {record.device_info.os} {record.device_info.osVersion}
              </Text>
            </>
          ) : (
            <Text style={styles.infoValue}>-</Text>
          )}
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  content: { padding: 16, gap: 16, paddingBottom: 48 },
  loading: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  errorText: { color: '#9CA3AF', fontSize: 15 },
  photoCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  photo: { width: '100%', height: width - 32, backgroundColor: '#F3F4F6' },
  photoBadge: {
    padding: 12,
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
  },
  photoBadgeText: { fontSize: 16, fontWeight: '700', color: '#374151' },
  infoCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  infoRow: { marginBottom: 4 },
  infoLabel: { fontSize: 12, fontWeight: '600', color: '#9CA3AF', textTransform: 'uppercase', marginBottom: 4 },
  infoValue: { fontSize: 15, color: '#111827', fontWeight: '500', lineHeight: 22 },
  infoSubValue: { fontSize: 13, color: '#6B7280', marginTop: 2 },
  divider: { height: 1, backgroundColor: '#F3F4F6', marginVertical: 12 },
  statusRow: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  lateBadge: { backgroundColor: '#FEE2E2' },
  onTimeBadge: { backgroundColor: '#D1FAE5' },
  statusText: { fontSize: 13, fontWeight: '700' },
  lateText: { color: '#DC2626' },
  onTimeText: { color: '#059669' },
  suspectBadge: { backgroundColor: '#FEF3C7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  suspectText: { fontSize: 13, fontWeight: '700', color: '#D97706' },
});
