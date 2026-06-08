import { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  Alert,
  Platform,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useLeaveStore } from '@/stores/leaveStore';
import { useEffect } from 'react';

const LEAVE_TYPES = [
  { value: 'tahunan', label: 'Cuti Tahunan' },
  { value: 'sakit', label: 'Cuti Sakit' },
  { value: 'darurat', label: 'Cuti Darurat' },
  { value: 'lainnya', label: 'Lainnya' },
] as const;

export default function LeaveScreen() {
  const profile = useAuthStore((s) => s.profile);
  const { myLeaves, remainingQuota, submitLeave, loadMyLeaves, loadRemainingQuota } =
    useLeaveStore();
  const [leaveType, setLeaveType] = useState<'tahunan' | 'sakit' | 'darurat' | 'lainnya'>('tahunan');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  useEffect(() => {
    if (profile?.id) {
      loadMyLeaves(profile.id);
      loadRemainingQuota(profile.id);
    }
  }, [profile?.id]);

  const handleSubmit = async () => {
    if (!startDate || !endDate || !reason.trim()) {
      Alert.alert('Error', 'Semua field wajib diisi');
      return;
    }

    const start = new Date(startDate);
    const end = new Date(endDate);
    if (end < start) {
      Alert.alert('Error', 'Tanggal selesai tidak boleh sebelum tanggal mulai');
      return;
    }

    const totalDays = Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24)) + 1;
    if (totalDays > remainingQuota) {
      Alert.alert('Error', `Kuota cuti tidak cukup. Sisa: ${remainingQuota} hari`);
      return;
    }

    setLoading(true);
    try {
      await submitLeave({
        leaveType,
        startDate,
        endDate,
        totalDays,
        reason: reason.trim(),
      });
      Alert.alert('Berhasil', 'Pengajuan cuti berhasil dikirim. Menunggu approval.');
      setStartDate('');
      setEndDate('');
      setReason('');
    } catch (err: any) {
      Alert.alert('Gagal', err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Quota Info */}
      <View style={styles.quotaCard}>
        <Text style={styles.quotaLabel}>Sisa Kuota Cuti</Text>
        <Text style={styles.quotaValue}>{remainingQuota} hari</Text>
      </View>

      {/* Form */}
      <View style={styles.formCard}>
        <Text style={styles.formTitle}>Form Pengajuan Cuti</Text>

        <Text style={styles.label}>Jenis Cuti</Text>
        <View style={styles.typeGrid}>
          {LEAVE_TYPES.map((t) => (
            <TouchableOpacity
              key={t.value}
              style={[styles.typeChip, leaveType === t.value && styles.typeChipActive]}
              onPress={() => setLeaveType(t.value)}
            >
              <Text
                style={[styles.typeText, leaveType === t.value && styles.typeTextActive]}
              >
                {t.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        <Text style={styles.label}>Tanggal Mulai</Text>
        <TextInput
          style={styles.input}
          placeholder="YYYY-MM-DD"
          value={startDate}
          onChangeText={setStartDate}
        />

        <Text style={styles.label}>Tanggal Selesai</Text>
        <TextInput
          style={styles.input}
          placeholder="YYYY-MM-DD"
          value={endDate}
          onChangeText={setEndDate}
        />

        <Text style={styles.label}>Alasan</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          placeholder="Jelaskan alasan cuti..."
          multiline
          numberOfLines={3}
          value={reason}
          onChangeText={setReason}
        />

        <TouchableOpacity
          style={[styles.submitBtn, loading && styles.btnDisabled]}
          onPress={handleSubmit}
          disabled={loading}
          activeOpacity={0.8}
        >
          <Text style={styles.submitText}>
            {loading ? 'Mengirim...' : 'Ajukan Cuti'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* History */}
      {myLeaves.length > 0 && (
        <View style={styles.historyCard}>
          <Text style={styles.historyTitle}>Riwayat Cuti</Text>
          {myLeaves.slice(0, 5).map((item) => (
            <TouchableOpacity
              key={item.id}
              style={styles.historyItem}
              onPress={() => router.push(`/(app)/leave/${item.id}`)}
            >
              <View style={{ flex: 1 }}>
                <Text style={styles.historyType}>
                  {item.leave_type === 'tahunan'
                    ? 'Cuti Tahunan'
                    : item.leave_type === 'sakit'
                    ? 'Cuti Sakit'
                    : item.leave_type === 'darurat'
                    ? 'Cuti Darurat'
                    : 'Cuti Lainnya'}
                </Text>
                <Text style={styles.historyDate}>
                  {new Date(item.start_date).toLocaleDateString('id-ID')} -{' '}
                  {new Date(item.end_date).toLocaleDateString('id-ID')}
                  {' • '}{item.total_days} hari
                </Text>
              </View>
              <Text
                style={[
                  styles.historyStatus,
                  {
                    color:
                      item.status === 'approved'
                        ? '#10B981'
                        : item.status === 'rejected'
                        ? '#EF4444'
                        : '#F59E0B',
                  },
                ]}
              >
                {item.status === 'approved'
                  ? 'Disetujui'
                  : item.status === 'rejected'
                  ? 'Ditolak'
                  : 'Pending'}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  content: { padding: 16, gap: 16 },
  quotaCard: {
    backgroundColor: '#EFF6FF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  quotaLabel: { fontSize: 13, color: '#2563EB', fontWeight: '600' },
  quotaValue: { fontSize: 28, fontWeight: '800', color: '#1E40AF', marginTop: 4 },
  formCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 20,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  formTitle: { fontSize: 17, fontWeight: '700', color: '#111827', marginBottom: 16 },
  label: { fontSize: 14, fontWeight: '600', color: '#374151', marginBottom: 6, marginTop: 12 },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
  },
  textArea: { minHeight: 80, textAlignVertical: 'top' },
  typeGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  typeChip: {
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 20,
    borderWidth: 2,
    borderColor: '#D1D5DB',
  },
  typeChipActive: { borderColor: '#2563EB', backgroundColor: '#EFF6FF' },
  typeText: { fontSize: 13, fontWeight: '600', color: '#6B7280' },
  typeTextActive: { color: '#2563EB' },
  submitBtn: {
    backgroundColor: '#2563EB',
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 20,
  },
  btnDisabled: { opacity: 0.6 },
  submitText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  historyCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    marginBottom: 32,
  },
  historyTitle: { fontSize: 15, fontWeight: '700', color: '#111827', marginBottom: 12 },
  historyItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: '#F3F4F6',
  },
  historyType: { fontSize: 14, fontWeight: '600', color: '#374151' },
  historyDate: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  historyStatus: { fontSize: 12, fontWeight: '700' },
});
