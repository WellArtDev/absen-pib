import { useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useOvertimeStore } from '@/stores/overtimeStore';
import { getCurrentPosition, runAntiFakeCheck, getPrimaryLocation } from '@/services/location';
import { reverseGeocode } from '@/services/nominatim';

export default function OvertimeScreen() {
  const profile = useAuthStore((s) => s.profile);
  const { activeOvertime, startOvertime, endOvertime, history } = useOvertimeStore();
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleStartOvertime = async () => {
    setLoading(true);
    try {
      const gpsData = await getCurrentPosition();
      const primary = getPrimaryLocation(gpsData);
      const address = await reverseGeocode(primary.latitude, primary.longitude);

      await startOvertime({
        photoUri: 'file:///placeholder.jpg', // Will use actual camera in production
        latitude: primary.latitude,
        longitude: primary.longitude,
        address,
      });

      Alert.alert('Berhasil', 'Lembur dimulai. Jangan lupa check-out saat selesai!');
    } catch (err: any) {
      Alert.alert('Gagal', err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleEndOvertime = async () => {
    if (!activeOvertime) return;

    setLoading(true);
    try {
      const gpsData = await getCurrentPosition();
      const primary = getPrimaryLocation(gpsData);
      const address = await reverseGeocode(primary.latitude, primary.longitude);

      await endOvertime({
        overtimeId: activeOvertime.id,
        photoUri: 'file:///placeholder.jpg',
        latitude: primary.latitude,
        longitude: primary.longitude,
        address,
      });

      Alert.alert('Berhasil', 'Lembur selesai. Menunggu approval admin.');
    } catch (err: any) {
      Alert.alert('Gagal', err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      {/* Active Overtime */}
      {activeOvertime ? (
        <View style={styles.activeCard}>
          <Text style={styles.activeTitle}>🔴 Lembur Sedang Berlangsung</Text>
          <Text style={styles.activeTime}>
            Mulai: {new Date(activeOvertime.check_in_at).toLocaleTimeString('id-ID')}
          </Text>
          <TouchableOpacity
            style={styles.endBtn}
            onPress={handleEndOvertime}
            disabled={loading}
            activeOpacity={0.8}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.endBtnText}>Selesai Lembur</Text>
            )}
          </TouchableOpacity>
        </View>
      ) : (
        <TouchableOpacity
          style={styles.startCard}
          onPress={handleStartOvertime}
          disabled={loading}
          activeOpacity={0.8}
        >
          {loading ? (
            <ActivityIndicator size="large" color="#2563EB" />
          ) : (
            <>
              <Text style={styles.startIcon}>⏰</Text>
              <Text style={styles.startTitle}>Mulai Lembur</Text>
              <Text style={styles.startHint}>
                Foto selfie + GPS akan direkam
              </Text>
            </>
          )}
        </TouchableOpacity>
      )}

      {/* Info */}
      <View style={styles.infoCard}>
        <Text style={styles.infoTitle}>ℹ️ Info Lembur</Text>
        <Text style={styles.infoText}>
          • Lembur dimulai setelah jam kerja selesai{'\n'}
          • Wajib foto selfie dan lokasi GPS{'\n'}
          • Perlu approval admin{'\n'}
          • Durasi dihitung otomatis
        </Text>
      </View>

      {/* Recent History */}
      {history.length > 0 && (
        <View style={styles.recentCard}>
          <Text style={styles.recentTitle}>Riwayat Terbaru</Text>
          {history.slice(0, 3).map((item) => (
            <TouchableOpacity
              key={item.id}
              style={styles.historyItem}
              onPress={() => router.push(`/(app)/overtime/${item.id}`)}
            >
              <Text style={styles.historyDate}>
                {new Date(item.check_in_at).toLocaleDateString('id-ID', {
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
                })}
              </Text>
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
                  : 'Menunggu'}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6', padding: 16, gap: 16 },
  activeCard: {
    backgroundColor: '#FEF2F2',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#FECACA',
    gap: 12,
  },
  activeTitle: { fontSize: 18, fontWeight: '700', color: '#DC2626' },
  activeTime: { fontSize: 15, color: '#6B7280' },
  endBtn: {
    backgroundColor: '#DC2626',
    paddingVertical: 14,
    paddingHorizontal: 32,
    borderRadius: 12,
  },
  endBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  startCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 40,
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#2563EB',
    borderStyle: 'dashed',
    gap: 8,
  },
  startIcon: { fontSize: 48 },
  startTitle: { fontSize: 20, fontWeight: '700', color: '#2563EB' },
  startHint: { fontSize: 13, color: '#6B7280' },
  infoCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  infoTitle: { fontSize: 15, fontWeight: '700', color: '#111827', marginBottom: 8 },
  infoText: { fontSize: 13, color: '#6B7280', lineHeight: 20 },
  recentCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  recentTitle: { fontSize: 15, fontWeight: '700', color: '#111827', marginBottom: 12 },
  historyItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: '#F3F4F6',
  },
  historyDate: { fontSize: 14, color: '#374151' },
  historyStatus: { fontSize: 13, fontWeight: '600' },
});
