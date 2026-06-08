import { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  Alert,
  Switch,
} from 'react-native';
import { supabase } from '@/services/supabase';

export default function OfficeConfigScreen() {
  const [name, setName] = useState('');
  const [latitude, setLatitude] = useState('');
  const [longitude, setLongitude] = useState('');
  const [radius, setRadius] = useState('200');
  const [workStart, setWorkStart] = useState('08:00');
  const [workEnd, setWorkEnd] = useState('17:00');
  const [enforceGeofence, setEnforceGeofence] = useState(true);
  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    if (!name.trim() || !latitude || !longitude) {
      Alert.alert('Error', 'Nama, latitude, dan longitude wajib diisi');
      return;
    }

    setSaving(true);
    try {
      const { data: existing } = await supabase
        .from('office_config')
        .select('id')
        .limit(1)
        .single();

      if (existing) {
        const { error } = await supabase
          .from('office_config')
          .update({
            name: name.trim(),
            latitude: parseFloat(latitude),
            longitude: parseFloat(longitude),
            radius_meters: parseInt(radius, 10),
            work_start: workStart,
            work_end: workEnd,
            enforce_geofence: enforceGeofence,
          })
          .eq('id', existing.id);
        if (error) throw error;
      } else {
        const { error } = await supabase
          .from('office_config')
          .insert({
            name: name.trim(),
            latitude: parseFloat(latitude),
            longitude: parseFloat(longitude),
            radius_meters: parseInt(radius, 10),
            work_start: workStart,
            work_end: workEnd,
            enforce_geofence: enforceGeofence,
          });
        if (error) throw error;
      }

      Alert.alert('Berhasil', 'Konfigurasi kantor disimpan');
    } catch (err: any) {
      Alert.alert('Gagal', err.message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <View style={styles.card}>
        <Text style={styles.title}>Konfigurasi Kantor</Text>

        <Text style={styles.label}>Nama Kantor</Text>
        <TextInput
          style={styles.input}
          placeholder="Kantor Pusat"
          value={name}
          onChangeText={setName}
        />

        <Text style={styles.label}>Latitude</Text>
        <TextInput
          style={styles.input}
          placeholder="-6.2088"
          keyboardType="decimal-pad"
          value={latitude}
          onChangeText={setLatitude}
        />

        <Text style={styles.label}>Longitude</Text>
        <TextInput
          style={styles.input}
          placeholder="106.8456"
          keyboardType="decimal-pad"
          value={longitude}
          onChangeText={setLongitude}
        />

        <Text style={styles.label}>Radius Geofence (meter)</Text>
        <TextInput
          style={styles.input}
          placeholder="200"
          keyboardType="number-pad"
          value={radius}
          onChangeText={setRadius}
        />

        <Text style={styles.label}>Jam Masuk</Text>
        <TextInput
          style={styles.input}
          placeholder="08:00"
          value={workStart}
          onChangeText={setWorkStart}
        />

        <Text style={styles.label}>Jam Pulang</Text>
        <TextInput
          style={styles.input}
          placeholder="17:00"
          value={workEnd}
          onChangeText={setWorkEnd}
        />

        <View style={styles.switchRow}>
          <View style={{ flex: 1 }}>
            <Text style={styles.label}>Wajib Geofence</Text>
            <Text style={styles.hint}>Karyawan kantor harus absen dalam radius</Text>
          </View>
          <Switch
            value={enforceGeofence}
            onValueChange={setEnforceGeofence}
            trackColor={{ false: '#D1D5DB', true: '#93C5FD' }}
            thumbColor={enforceGeofence ? '#2563EB' : '#F3F4F6'}
          />
        </View>

        <TouchableOpacity
          style={[styles.saveBtn, saving && styles.btnDisabled]}
          onPress={handleSave}
          disabled={saving}
          activeOpacity={0.8}
        >
          <Text style={styles.saveText}>
            {saving ? 'Menyimpan...' : 'Simpan Konfigurasi'}
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },
  content: { padding: 16 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 20,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  title: { fontSize: 17, fontWeight: '700', color: '#111827', marginBottom: 16 },
  label: { fontSize: 14, fontWeight: '600', color: '#374151', marginBottom: 6, marginTop: 12 },
  hint: { fontSize: 12, color: '#9CA3AF', marginTop: 2 },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
  },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 16,
    paddingVertical: 8,
  },
  saveBtn: {
    backgroundColor: '#2563EB',
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 24,
  },
  btnDisabled: { opacity: 0.6 },
  saveText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
