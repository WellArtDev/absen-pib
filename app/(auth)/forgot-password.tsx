import { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
} from 'react-native';
import { Link } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const resetPassword = useAuthStore((s) => s.resetPassword);

  const handleReset = async () => {
    if (!email.trim()) {
      Alert.alert('Error', 'Masukkan email Anda');
      return;
    }
    setLoading(true);
    try {
      await resetPassword(email.trim());
      setSent(true);
    } catch (err: any) {
      Alert.alert('Gagal', err.message || 'Gagal mengirim reset password');
    } finally {
      setLoading(false);
    }
  };

  if (sent) {
    return (
      <View style={styles.container}>
        <View style={styles.centered}>
          <Text style={styles.checkIcon}>📧</Text>
          <Text style={styles.title}>Cek Email Anda</Text>
          <Text style={styles.description}>
            Link reset password telah dikirim ke {email}. Silakan cek inbox dan folder spam Anda.
          </Text>
          <Link href="/(auth)/login" style={styles.backLink}>
            Kembali ke Login
          </Link>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.centered}>
        <Text style={styles.title}>Lupa Password</Text>
        <Text style={styles.description}>
          Masukkan email terdaftar Anda. Kami akan mengirim link untuk mereset password.
        </Text>

        <Text style={styles.label}>Email</Text>
        <TextInput
          style={styles.input}
          placeholder="email@perusahaan.com"
          keyboardType="email-address"
          autoCapitalize="none"
          value={email}
          onChangeText={setEmail}
        />

        <TouchableOpacity
          style={[styles.button, loading && styles.buttonDisabled]}
          onPress={handleReset}
          disabled={loading}
          activeOpacity={0.8}
        >
          <Text style={styles.buttonText}>
            {loading ? 'Mengirim...' : 'Kirim Reset Link'}
          </Text>
        </TouchableOpacity>

        <Link href="/(auth)/login" style={styles.backLink}>
          Kembali ke Login
        </Link>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB', padding: 24, justifyContent: 'center' },
  centered: { alignItems: 'center' },
  checkIcon: { fontSize: 64, marginBottom: 16 },
  title: { fontSize: 24, fontWeight: '800', color: '#111827', marginBottom: 8 },
  description: { fontSize: 15, color: '#6B7280', textAlign: 'center', marginBottom: 32, lineHeight: 22 },
  label: { fontSize: 14, fontWeight: '600', color: '#374151', alignSelf: 'flex-start', marginBottom: 4 },
  input: {
    width: '100%',
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 16,
    marginBottom: 16,
  },
  button: {
    width: '100%',
    backgroundColor: '#2563EB',
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { color: '#fff', fontSize: 17, fontWeight: '700' },
  backLink: { color: '#2563EB', fontSize: 14, fontWeight: '500', marginTop: 20 },
});
