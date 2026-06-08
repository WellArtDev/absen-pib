import { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  Alert,
} from 'react-native';
import { supabase } from '@/services/supabase';

export default function ReportsScreen() {
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [exporting, setExporting] = useState(false);

  const handleExport = async () => {
    if (!startDate || !endDate) {
      Alert.alert('Error', 'Pilih tanggal mulai dan selesai');
      return;
    }

    setExporting(true);
    try {
      const { data, error } = await supabase
        .from('attendances')
        .select('*, profiles(full_name, nip)')
        .gte('server_timestamp', `${startDate}T00:00:00Z`)
        .lte('server_timestamp', `${endDate}T23:59:59Z`)
        .order('server_timestamp', { ascending: false });

      if (error) throw error;

      // Build CSV
      const headers = 'NIP,Nama,Tanggal,Check In,Check Out,Status,Lokasi,Suspicion Score\n';
      const rows = (data as any[])
        .map((r) => {
          const date = new Date(r.server_timestamp).toLocaleDateString('id-ID');
          const time = new Date(r.server_timestamp).toLocaleTimeString('id-ID');
          const type = r.type === 'check_in' ? 'IN' : 'OUT';
          const status = r.is_late ? 'Terlambat' : r.is_suspect ? 'Mencurigakan' : 'Tepat Waktu';
          return `${r.profiles?.nip || '-'},${r.profiles?.full_name || '-'},${date},${type === 'IN' ? time : '-'},${type === 'OUT' ? time : '-'},${status},"${r.address || '-'}",${r.suspicion_score}`;
        })
        .join('\n');

      const csv = headers + rows;

      // In production: use expo-file-system + share dialog
      // For now, show preview length
      Alert.alert('Export CSV', `Data berhasil diexport.\n${data.length} records.\nUkuran: ${csv.length} bytes`, [
        { text: 'OK' },
      ]);
    } catch (err: any) {
      Alert.alert('Gagal', err.message);
    } finally {
      setExporting(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <View style={styles.card}>
        <Text style={styles.title}>Export Laporan Absensi</Text>

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

        <Text style={styles.info}>
          Format: CSV dengan kolom NIP, Nama, Tanggal, Check In, Check Out, Status, Lokasi, Suspicion Score
        </Text>

        <TouchableOpacity
          style={[styles.exportBtn, exporting && styles.btnDisabled]}
          onPress={handleExport}
          disabled={exporting}
          activeOpacity={0.8}
        >
          <Text style={styles.exportText}>
            {exporting ? 'Mengexport...' : 'Export CSV'}
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
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
  },
  info: { fontSize: 12, color: '#6B7280', marginTop: 12, lineHeight: 18 },
  exportBtn: {
    backgroundColor: '#059669',
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 20,
  },
  btnDisabled: { opacity: 0.6 },
  exportText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
