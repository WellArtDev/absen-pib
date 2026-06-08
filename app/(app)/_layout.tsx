import { Stack } from 'expo-router';

export default function AppLayout() {
  return (
    <Stack
      screenOptions={{
        contentStyle: { backgroundColor: '#F3F4F6' },
        animation: 'slide_from_right',
        headerStyle: { backgroundColor: '#2563EB' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
      }}
    >
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      <Stack.Screen name="admin" options={{ headerShown: false }} />
      <Stack.Screen name="overtime/index" options={{ title: 'Lembur' }} />
      <Stack.Screen name="overtime/[id]" options={{ title: 'Detail Lembur' }} />
      <Stack.Screen name="leave/index" options={{ title: 'Cuti' }} />
      <Stack.Screen name="leave/[id]" options={{ title: 'Detail Cuti' }} />
      <Stack.Screen name="attendance/[id]" options={{ title: 'Detail Absensi' }} />
    </Stack>
  );
}
