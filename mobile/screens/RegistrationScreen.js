import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import api from '../src/api';

const GRADE_LEVELS = [
  'Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3',
  'Grade 4', 'Grade 5', 'Grade 6',
];

const USER_TYPES = [
  { label: 'Student', value: 'student' },
  { label: 'Parent', value: 'parent' },
];

export default function RegistrationScreen({ navigation }) {
  const [step, setStep] = useState(1);
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [userType, setUserType] = useState('student');
  const [gradeLevel, setGradeLevel] = useState('');
  const [sectionId, setSectionId] = useState('');
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (gradeLevel) loadSections();
  }, [gradeLevel]);

  const loadSections = async () => {
    try {
      const res = await api.get(`/ebooks.php?action=get_sections&grade_level=${encodeURIComponent(gradeLevel)}`);
      if (res.data.success) setSections(res.data.sections || []);
    } catch (e) {
      console.error('Error loading sections:', e);
    }
  };

  const validateStep1 = () => {
    if (!username || !email || !password || !fullName) {
      Alert.alert('Error', 'Please fill in all required fields');
      return false;
    }
    if (password.length < 6) {
      Alert.alert('Error', 'Password must be at least 6 characters');
      return false;
    }
    if (password !== confirmPassword) {
      Alert.alert('Error', 'Passwords do not match');
      return false;
    }
    if (!/\S+@\S+\.\S+/.test(email)) {
      Alert.alert('Error', 'Please enter a valid email address');
      return false;
    }
    return true;
  };

  const handleNext = () => {
    if (step === 1 && validateStep1()) setStep(2);
  };

  const handleRegister = async () => {
    setLoading(true);
    try {
      const formData = new FormData();
      formData.append('action', 'register');
      formData.append('username', username);
      formData.append('email', email);
      formData.append('password', password);
      formData.append('full_name', fullName);
      formData.append('user_type', userType);
      formData.append('grade_level', gradeLevel);
      formData.append('section_id', sectionId || '');

      const response = await api.post('/auth.php?action=register', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (response.data.success) {
        Alert.alert('Success', response.data.message || 'Registration successful!', [
          { text: 'OK', onPress: () => navigation.goBack() },
        ]);
      } else {
        Alert.alert('Error', response.data.message || 'Registration failed');
      }
    } catch (error) {
      const msg = error.response?.data?.message || error.message || 'Network error';
      Alert.alert('Error', msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.keyboardView}
      >
        <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <Text style={styles.backBtnText}>← Back to Login</Text>
          </TouchableOpacity>

          <Text style={styles.title}>Create Account</Text>
          <Text style={styles.subtitle}>Join the San Roque E-Library</Text>

          {step === 1 && (
            <View style={styles.form}>
              <TextInput
                style={styles.input}
                placeholder="Full Name *"
                value={fullName}
                onChangeText={setFullName}
                autoCorrect={false}
              />
              <TextInput
                style={styles.input}
                placeholder="Username *"
                value={username}
                onChangeText={setUsername}
                autoCapitalize="none"
                autoCorrect={false}
              />
              <TextInput
                style={styles.input}
                placeholder="Email *"
                value={email}
                onChangeText={setEmail}
                autoCapitalize="none"
                keyboardType="email-address"
                autoCorrect={false}
              />
              <TextInput
                style={styles.input}
                placeholder="Password * (min 6 characters)"
                value={password}
                onChangeText={setPassword}
                secureTextEntry
                autoCapitalize="none"
              />
              <TextInput
                style={styles.input}
                placeholder="Confirm Password *"
                value={confirmPassword}
                onChangeText={setConfirmPassword}
                secureTextEntry
                autoCapitalize="none"
              />
              <TouchableOpacity style={styles.button} onPress={handleNext}>
                <Text style={styles.buttonText}>Next</Text>
              </TouchableOpacity>
            </View>
          )}

          {step === 2 && (
            <View style={styles.form}>
              <Text style={styles.fieldLabel}>Account Type</Text>
              <View style={styles.pickerWrapper}>
                <Picker selectedValue={userType} onValueChange={setUserType} style={styles.picker}>
                  {USER_TYPES.map((t) => (
                    <Picker.Item key={t.value} label={t.label} value={t.value} />
                  ))}
                </Picker>
              </View>

              <Text style={styles.fieldLabel}>Grade Level</Text>
              <View style={styles.pickerWrapper}>
                <Picker selectedValue={gradeLevel} onValueChange={setGradeLevel} style={styles.picker}>
                  <Picker.Item label="Select Grade Level" value="" />
                  {GRADE_LEVELS.map((g) => (
                    <Picker.Item key={g} label={g} value={g.toLowerCase().replace(' ', '')} />
                  ))}
                </Picker>
              </View>

              {sections.length > 0 && (
                <>
                  <Text style={styles.fieldLabel}>Section (Optional)</Text>
                  <View style={styles.pickerWrapper}>
                    <Picker selectedValue={sectionId} onValueChange={setSectionId} style={styles.picker}>
                      <Picker.Item label="No Section" value="" />
                      {sections.map((s) => (
                        <Picker.Item key={s.section_id} label={s.section_name} value={String(s.section_id)} />
                      ))}
                    </Picker>
                  </View>
                </>
              )}

              <TouchableOpacity
                style={[styles.button, loading && styles.buttonDisabled]}
                onPress={handleRegister}
                disabled={loading}
              >
                <Text style={styles.buttonText}>
                  {loading ? 'Registering...' : 'Create Account'}
                </Text>
              </TouchableOpacity>

              <TouchableOpacity style={styles.backStepBtn} onPress={() => setStep(1)}>
                <Text style={styles.backStepText}>← Back</Text>
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  keyboardView: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    flexGrow: 1,
  },
  backBtn: {
    marginBottom: 20,
  },
  backBtnText: {
    color: '#228B22',
    fontSize: 16,
    fontWeight: 'bold',
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 30,
  },
  form: {
    width: '100%',
  },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    paddingHorizontal: 15,
    paddingVertical: 12,
    fontSize: 16,
    marginBottom: 15,
  },
  fieldLabel: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
    marginTop: 10,
  },
  pickerWrapper: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    backgroundColor: '#fff',
    marginBottom: 15,
  },
  picker: {
    height: 50,
  },
  button: {
    backgroundColor: '#228B22',
    borderRadius: 8,
    paddingVertical: 15,
    alignItems: 'center',
    marginTop: 20,
  },
  buttonDisabled: {
    backgroundColor: '#ccc',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  backStepBtn: {
    alignItems: 'center',
    marginTop: 15,
  },
  backStepText: {
    color: '#228B22',
    fontSize: 14,
  },
});
