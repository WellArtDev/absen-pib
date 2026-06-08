import { useEffect, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  ScrollView,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useAttendanceStore } from '@/stores/attendanceStore';
import { checkIn, checkOut } from '@/services/attendance';
import { getCurrentPosition, runAntiFakeCheck, getPrimaryLocation } from '@/services/location';
import { reverseGeocode } from '@/services/nominatim';
import { getDeviceInfo } from '@/services/offlineQueue';

type AttendanceStep = 'idle' | 'camera' | 'location' | 'uploading' | 'done';

export default function HomeScreen() {
  const router = useRouter();
  const profile = useAuthStore((s) => s.profile);
  const { todayCheckIn, todayCheckOut, loadTodayStatus, setTodayCheckIn, setTodayCheckOut } =
    useAttendanceStore();
  const [step, setStep] = useState<AttendanceStep>('idle');

  useEffect(() => {
    if (profile?.id) {
      loadTodayStatus(profile.id);
    }
  }, [profile?.id]);

  const handleAttendance = async (type: 'check_in' | 'check_out') => {
    if (!profile) return;

    Alert.alert(
      type === 'check_in' ? 'Check In' : 'Check Out',
      'Ambil foto selfie dan lokasi Anda sekarang?',
      [
        { text: 'Batal', style: 'cancel' },
        { text: 'Lanjutkan', onPress: () => doAttendance(type) },
      ]
    );
  };

  const doAttendance = async (type: 'check_in' | 'check_out') => {
    try {
      setStep('location');
      const gpsData = await getCurrentPosition();
      const primary = getPrimaryLocation(gpsData);

      // Anti-fake check
      const antiFake = await runAntiFakeCheck(gpsData);
      const address = await reverseGeocode(primary.latitude, primary.longitude);

      setStep('uploading');
      const deviceInfo = getDeviceInfo();
      const gpsTimestamp = new Date(primary.timestamp).toISOString();

      const commonParams = {
        photoUri: 'file:///placeholder.jpg', // Will be replaced with actual camera capture
        latitude: primary.latitude,
        longitude: primary.longitude,
        altitude: primary.altitude,
        gpsAccuracy: primary.accuracy,
        address,
        gpsProviders: gpsData.map((d) => d.provider),
        gpsTimestamp,
      };

      if (type === 'check_in') {
        const record = await checkIn({ ...commonParams, deviceInfo });
        setTodayCheckIn(record);
      } else {
        const record = await checkOut(commonParams);
        setTodayCheckOut(record);
      }

      setStep('done');
      Alert.alert(
        'Berhasil!',
        `${type === 'check_in' ? 'Check In' : 'Check Out'} berhasil dicatat.\nLokasi: ${address || 'Tersimpan'}${antiFake.isSuspect ? '\n⚠️ Peringatan: lokasi mencurigakan' : ''}`,
        [{ text: 'OK' }]
      );
    } catch (err: any) {
      Alert.alert('Gagal', err.message);
    } finally {
      setStep('idle');
    }
  };

  const isLoading = step !== 'idle';
  const hasCheckedIn = !!todayCheckIn;
  const hasCheckedOut = !!todayCheckOut;

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Today's Status Card */}
      <View style={styles.statusCard}>
        <Text style={styles.greeting}>
          Selamat {new Date().getHours() < 12 ? 'Pagi' : new Date().getHours() < 15 ? 'Siang' : new Date().getHours() < 18 ? 'Sore' : 'Malam'},{' '}
          {profile?.full_name?.split(' ')[0]}!
        </Text>

        <View style={styles.statusRow}>
          <View style={styles.statusItem}>
            <View style={[styles.statusDot, hasCheckedIn && styles.statusDotActive]} />
            <Text style={styles.statusLabel}>Check In</Text>
            {todayCheckIn && (
              <Text style={styles.statusTime}>
                {new Date(todayCheckIn.server_timestamp).toLocaleTimeString('id-ID', {
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </Text>
            )}
          </View>
          <View style={styles.statusDivider} />
          <View style={styles.statusItem}>
            <View style={[styles.statusDot, hasCheckedOut && styles.statusDotActive]} />
            <Text style={styles.statusLabel}>Check Out</Text>
            {todayCheckOut && (
              <Text style={styles.statusTime}>
                {new Date(todayCheckOut.server_timestamp).toLocaleTimeString('id-ID', {
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </Text>
            )}
          </View>
        </View>
      </View>

      {/* Loading indicator */}
      {isLoading && (
        <View style={styles.loadingCard}>
          <ActivityIndicator size="large" color="#2563EB" />
          <Text style={styles.loadingText}>
            {step === 'location'
              ? 'Mendapatkan lokasi...'
              : step === 'uploading'
              ? 'Mengunggah...'
              : 'Memproses...'}
          </Text>
        </View>
      )}

      {/* Action Buttons */}
      <View style={styles.actions}>
        <TouchableOpacity
          style={[styles.actionBtn, styles.checkInBtn, (hasCheckedIn || isLoading) && styles.btnDisabled]}
          onPress={() => handleAttendance('check_in')}
          disabled={hasCheckedIn || isLoading}
          activeOpacity={0.8}
        >
          <Text style={styles.actionIcon}>✅</Text>
          <Text style={styles.actionLabel}>Check In</Text>
          <Text style={styles.actionHint}>{hasCheckedIn ? 'Sudah' : 'Tap untuk absen masuk'}</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.actionBtn, styles.checkOutBtn, (!hasCheckedIn || hasCheckedOut || isLoading) && styles.btnDisabled]}
          onPress={() => handleAttendance('check_out')}
          disabled={!hasCheckedIn || hasCheckedOut || isLoading}
          activeOpacity={0.8}
        >
          <Text style={styles.actionIcon}>🔚</Text>
          <Text style={styles.actionLabel}>Check Out</Text>
          <Text style={styles.actionHint}>
            {!hasCheckedIn ? 'Check In dulu' : hasCheckedOut ? 'Sudah' : 'Tap untuk absen pulang'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* Quick Links */}
      <View style={styles.quickLinks}>
        <TouchableOpacity
          style={styles.quickLink}
          onPress={() => router.push('/(app)/overtime')}
        >
          <Text style={styles.quickIcon}>⏰</Text>
          <Text style={styles.quickLabel}>Lembur</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.quickLink}
          onPress={() => router.push('/(app)/leave')}
        >
          <Text style={styles.quickIcon}>🏖️</Text>
          <Text style={styles.quickLabel}>Cuti</Text>
        </TouchableOpacity>

        {profile?.role === 'admin' && (
          <TouchableOpacity
            style={styles.quickLink}
            onPress={() => router.push('/(app)/admin/dashboard')}
          >
            <Text style={styles.quickIcon}>⚙️</Text>
            <Text style={styles.quickLabel}>Admin</Text>
          </TouchableOpacity>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  content: { padding: 16, gap: 16 },
  statusCard: {
    backgroundColor: '#2563EB',
    borderRadius: 16,
    padding: 20,
  },
  greeting: { color: '#fff', fontSize: 16, fontWeight: '600', marginBottom: 20 },
  statusRow: { flexDirection: 'row', alignItems: 'center' },
  statusItem: { flex: 1, flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap' },
  statusDot: { width: 12, height: 12, borderRadius: 6, backgroundColor: '#93C5FD', marginRight: 8 },
  statusDotActive: { backgroundColor: '#4ADE80' },
  statusLabel: { color: '#DBEAFE', fontSize: 14, fontWeight: '500' },
  statusTime: { color: '#fff', fontSize: 14, fontWeight: '700', marginLeft: 8 },
  statusDivider: { width: 1, height: 24, backgroundColor: '#3B82F6', marginHorizontal: 16 },
  loadingCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 32,
    alignItems: 'center',
    gap: 12,
  },
  loadingText: { color: '#6B7280', fontSize: 15 },
  actions: { flexDirection: 'row', gap: 12 },
  actionBtn: {
    flex: 1,
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    gap: 8,
  },
  checkInBtn: { backgroundColor: '#ECFDF5', borderWidth: 2, borderColor: '#6EE7B7' },
  checkOutBtn: { backgroundColor: '#FEF3C7', borderWidth: 2, borderColor: '#FCD34D' },
  btnDisabled: { opacity: 0.5, borderColor: '#E5E7EB', backgroundColor: '#F9FAFB' },
  actionIcon: { fontSize: 32 },
  actionLabel: { fontSize: 18, fontWeight: '700', color: '#111827' },
  actionHint: { fontSize: 12, color: '#6B7280' },
  quickLinks: {
    flexDirection: 'row',
    gap: 12,
    flexWrap: 'wrap',
  },
  quickLink: {
    flex: 1,
    minWidth: 100,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    gap: 6,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  quickIcon: { fontSize: 28 },
  quickLabel: { fontSize: 13, fontWeight: '600', color: '#374151' },
});
