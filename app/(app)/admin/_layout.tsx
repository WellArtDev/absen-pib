import { Stack } from 'expo-router';

export default function AdminLayout() {
  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: '#F3F4F6' },
      }}
    >
      <Stack.Screen name="dashboard" options={{ title: 'Dashboard Admin' }} />
      <Stack.Screen name="employees" options={{ title: 'Karyawan' }} />
      <Stack.Screen name="employee-detail/[id]" options={{ title: 'Detail Karyawan' }} />
      <Stack.Screen name="overtime-approvals" options={{ title: 'Approval Lembur' }} />
      <Stack.Screen name="leave-approvals" options={{ title: 'Approval Cuti' }} />
      <Stack.Screen name="reports" options={{ title: 'Laporan' }} />
      <Stack.Screen name="config" options={{ title: 'Konfigurasi' }} />
    </Stack>
  );
}
