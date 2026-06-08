import { View, Text, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { useLeaveStore } from '@/stores/leaveStore';
import { useEffect } from 'react';

export default function ProfileScreen() {
  const { profile, logout, isLoggedIn } = useAuthStore((s) => ({
    profile: s.profile,
    logout: s.logout,
    isLoggedIn: s.isLoggedIn,
  }));
  const remainingQuota = useLeaveStore((s) => s.remainingQuota);
  const loadRemainingQuota = useLeaveStore((s) => s.loadRemainingQuota);
  const router = useRouter();

  useEffect(() => {
    if (profile?.id) {
      loadRemainingQuota(profile.id);
    }
  }, [profile?.id]);

  const handleLogout = () => {
    Alert.alert('Keluar', 'Anda yakin ingin keluar?', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Keluar',
        style: 'destructive',
        onPress: async () => {
          await logout();
          router.replace('/(auth)/login');
        },
      },
    ]);
  };

  const roleLabel = (role: string) => {
    switch (role) {
      case 'admin': return 'Administrator';
      case 'sales': return 'Sales (Mobile)';
      default: return 'Karyawan';
    }
  };

  return (
    <View style={styles.container}>
      {/* Profile Card */}
      <View style={styles.card}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>
            {profile?.full_name?.charAt(0) || '?'}
          </Text>
        </View>
        <Text style={styles.name}>{profile?.full_name || '-'}</Text>
        <Text style={styles.role}>{roleLabel(profile?.role || 'karyawan')}</Text>
        <Text style={styles.nip}>NIP: {profile?.nip || '-'}</Text>
      </View>

      {/* Quota */}
      <View style={styles.quotaCard}>
        <Text style={styles.quotaTitle}>Sisa Cuti Tahunan</Text>
        <Text style={styles.quotaValue}>
          {remainingQuota} / {profile?.leave_quota_total || 12} hari
        </Text>
      </View>

      {/* Menu */}
      <View style={styles.menuSection}>
        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/overtime')}
        >
          <Text style={styles.menuIcon}>⏰</Text>
          <Text style={styles.menuLabel}>Lembur</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.menuItem}
          onPress={() => router.push('/(app)/leave')}
        >
          <Text style={styles.menuIcon}>🏖️</Text>
          <Text style={styles.menuLabel}>Cuti</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>

        {profile?.role === 'admin' && (
          <>
            <TouchableOpacity
              style={styles.menuItem}
              onPress={() => router.push('/(app)/admin/dashboard')}
            >
              <Text style={styles.menuIcon}>📊</Text>
              <Text style={styles.menuLabel}>Dashboard Admin</Text>
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
          </>
        )}
      </View>

      {/* Logout */}
      <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout} activeOpacity={0.8}>
        <Text style={styles.logoutText}>Keluar</Text>
      </TouchableOpacity>

      <Text style={styles.version}>AbsenPIB v1.0</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6', padding: 16 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    marginBottom: 12,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#2563EB',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  avatarText: { color: '#fff', fontSize: 28, fontWeight: '800' },
  name: { fontSize: 20, fontWeight: '700', color: '#111827' },
  role: { fontSize: 14, color: '#6B7280', marginTop: 4 },
  nip: { fontSize: 13, color: '#9CA3AF', marginTop: 4 },
  quotaCard: {
    backgroundColor: '#EFF6FF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#BFDBFE',
    marginBottom: 12,
  },
  quotaTitle: { fontSize: 13, color: '#2563EB', fontWeight: '600' },
  quotaValue: { fontSize: 24, fontWeight: '800', color: '#1E40AF', marginTop: 4 },
  menuSection: {
    backgroundColor: '#fff',
    borderRadius: 12,
    marginBottom: 12,
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
  logoutBtn: {
    backgroundColor: '#FEF2F2',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  logoutText: { color: '#EF4444', fontSize: 16, fontWeight: '700' },
  version: { textAlign: 'center', color: '#D1D5DB', fontSize: 12, marginTop: 16 },
});
