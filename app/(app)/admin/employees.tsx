import { useEffect, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
} from 'react-native';
import { useRouter } from 'expo-router';
import { supabase } from '@/services/supabase';
import type { Profile } from '@/services/leave';

export default function EmployeesScreen() {
  const router = useRouter();
  const [employees, setEmployees] = useState<Profile[]>([]);
  const [refreshing, setRefreshing] = useState(false);

  const loadEmployees = async () => {
    const { data, error } = await supabase
      .from('profiles')
      .select('*')
      .order('full_name');

    if (data && !error) {
      setEmployees(data as Profile[]);
    }
  };

  useEffect(() => {
    loadEmployees();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadEmployees();
    setRefreshing(false);
  };

  const renderItem = ({ item }: { item: Profile }) => (
    <TouchableOpacity
      style={styles.employeeCard}
      onPress={() => router.push(`/(app)/admin/employee-detail/${item.id}`)}
    >
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>{item.full_name.charAt(0)}</Text>
      </View>
      <View style={styles.info}>
        <Text style={styles.employeeName}>{item.full_name}</Text>
        <Text style={styles.employeeNip}>NIP: {item.nip}</Text>
        <View style={styles.roleBadge}>
          <Text style={styles.roleText}>
            {item.role === 'admin' ? 'Admin' : item.role === 'sales' ? 'Sales' : 'Karyawan'}
          </Text>
        </View>
      </View>
      <Text style={styles.arrow}>›</Text>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={employees}
        keyExtractor={(item) => item.id}
        renderItem={renderItem}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyText}>Belum ada data karyawan</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  list: { padding: 16, gap: 8 },
  employeeCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#2563EB',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  avatarText: { color: '#fff', fontSize: 18, fontWeight: '700' },
  info: { flex: 1 },
  employeeName: { fontSize: 15, fontWeight: '600', color: '#111827' },
  employeeNip: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  roleBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#F3F4F6',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    marginTop: 4,
  },
  roleText: { fontSize: 11, fontWeight: '600', color: '#6B7280' },
  arrow: { fontSize: 20, color: '#D1D5DB' },
  empty: { padding: 48, alignItems: 'center' },
  emptyText: { color: '#9CA3AF', fontSize: 15 },
});
