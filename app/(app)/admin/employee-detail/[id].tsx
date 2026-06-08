import { useEffect, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { supabase } from '@/services/supabase';
import { getAttendanceHistory } from '@/services/attendance';
import type { AttendanceRecord } from '@/services/attendance';
import { format } from 'date-fns';
import { id } from 'date-fns/locale/id';

export default function EmployeeDetailScreen() {
  const { id: userId } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [profile, setProfile] = useState<any>(null);
  const [attendances, setAttendances] = useState<AttendanceRecord[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!userId) return;

    (async () => {
      const { data: profileData } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', userId)
        .single();

      if (profileData) setProfile(profileData);

      const end = new Date().toISOString();
      const start = new Date(Date.now() - 30 * 24 * 3600000).toISOString();

      const history = await getAttendanceHistory(userId, start, end);
      setAttendances(history.filter((r: AttendanceRecord) => r.type === 'check_in'));
    })().finally(() => setLoading(false));
  }, [userId]);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Profile Header */}
      {profile && (
        <View style={styles.profileHeader}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{profile.full_name?.charAt(0)}</Text>
          </View>
          <Text style={styles.name}>{profile.full_name}</Text>
          <Text style={styles.nip}>NIP: {profile.nip}</Text>
          <Text style={styles.role}>
            {profile.role === 'admin' ? 'Admin' : profile.role === 'sales' ? 'Sales' : 'Karyawan'}
          </Text>
        </View>
      )}

      <Text style={styles.listTitle}>Riwayat Absensi (30 hari)</Text>

      <FlatList
        data={attendances}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.card}
            onPress={() => router.push(`/(app)/attendance/${item.id}`)}
          >
            <View style={styles.cardLeft}>
              <Text style={styles.cardDate}>
                {format(new Date(item.server_timestamp), 'EEE, dd MMM', { locale: id })}
              </Text>
              <Text style={styles.cardTime}>
                {format(new Date(item.server_timestamp), 'HH:mm')}
              </Text>
            </View>
            <View style={styles.cardRight}>
              {item.is_late && (
                <View style={styles.lateBadge}>
                  <Text style={styles.lateText}>Terlambat</Text>
                </View>
              )}
              {item.is_suspect && (
                <View style={styles.suspectBadge}>
                  <Text style={styles.suspectText}>⚠️</Text>
                </View>
              )}
            </View>
            <Text style={styles.arrow}>›</Text>
          </TouchableOpacity>
        )}
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyText}>Belum ada data absensi</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  profileHeader: {
    backgroundColor: '#2563EB',
    padding: 24,
    alignItems: 'center',
    paddingTop: 32,
  },
  avatar: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  avatarText: { color: '#fff', fontSize: 24, fontWeight: '800' },
  name: { color: '#fff', fontSize: 20, fontWeight: '700' },
  nip: { color: '#DBEAFE', fontSize: 13, marginTop: 4 },
  role: { color: '#BFDBFE', fontSize: 13, marginTop: 2 },
  listTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#374151',
    padding: 16,
    paddingBottom: 8,
  },
  list: { padding: 16, paddingTop: 0, gap: 8 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  cardLeft: {},
  cardDate: { fontSize: 14, fontWeight: '600', color: '#111827' },
  cardTime: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  cardRight: { flex: 1, flexDirection: 'row', justifyContent: 'flex-end', gap: 6 },
  lateBadge: { backgroundColor: '#FEE2E2', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  lateText: { fontSize: 11, fontWeight: '700', color: '#DC2626' },
  suspectBadge: { backgroundColor: '#FEF3C7', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  suspectText: { fontSize: 14 },
  arrow: { fontSize: 20, color: '#D1D5DB', marginLeft: 8 },
  empty: { padding: 48, alignItems: 'center' },
  emptyText: { color: '#9CA3AF', fontSize: 15 },
});
