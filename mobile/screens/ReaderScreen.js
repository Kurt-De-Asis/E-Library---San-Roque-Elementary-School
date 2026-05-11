import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  ActivityIndicator,
  Alert,
  Dimensions,
  Share,
} from 'react-native';
import { WebView } from 'react-native-webview';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL } from '../config/api';
import api from '../src/api';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

export default function ReaderScreen({ route, navigation }) {
  const { book } = route.params;
  const [token, setToken] = useState('');
  const [progress, setProgress] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const webViewRef = useRef(null);

  const isPdf = book.file_path?.toLowerCase().endsWith('.pdf') || book.content_type === 'book';
  const isVideo = book.file_path?.toLowerCase().match(/\.(mp4|webm)$/);
  const isPpt = book.file_path?.toLowerCase().match(/\.(ppt|pptx)$/);

  useEffect(() => {
    init();
  }, []);

  const init = async () => {
    try {
      const storedToken = await AsyncStorage.getItem('token');
      setToken(storedToken || '');

      const res = await api.post('/ebooks.php', {
        action: 'get_progress',
        ebook_id: book.ebook_id,
      });
      if (res.data.success && res.data.progress) {
        setProgress(res.data.progress);
      }
    } catch (e) {
      console.error('Init error:', e);
    } finally {
      setLoading(false);
    }
  };

  const saveProgress = async () => {
    try {
      await api.post('/ebooks.php', {
        action: 'mark_read',
        ebook_id: book.ebook_id,
        page: 1,
        total_pages: 1,
      });
    } catch (e) {
      console.error('Save progress error:', e);
    }
  };

  const handleClose = () => {
    saveProgress();
    navigation.goBack();
  };

  const handleShare = async () => {
    try {
      await Share.share({
        message: `Check out this book: ${book.title} by ${book.author}`,
      });
    } catch (e) {
      console.error('Share error:', e);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.centerContent}>
          <ActivityIndicator size="large" color="#228B22" />
          <Text style={styles.loadingText}>Loading reader...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.centerContent}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.closeBtn} onPress={handleClose}>
            <Text style={styles.closeBtnText}>Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  if (isVideo) {
    const videoUrl = `${API_BASE_URL.replace('/api', '')}/api/serve.php?token=${token}&id=${book.ebook_id}`;
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={handleClose} style={styles.headerBtn}>
            <Text style={styles.headerBtnText}>← Close</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle} numberOfLines={1}>{book.title}</Text>
          <TouchableOpacity onPress={handleShare} style={styles.headerBtn}>
            <Text style={styles.headerBtnText}>Share</Text>
          </TouchableOpacity>
        </View>
        <WebView
          source={{ html: `
            <html><body style="margin:0;background:#000;display:flex;align-items:center;justify-content:center;height:100vh;">
              <video controls style="max-width:100%;max-height:100%;" src="${videoUrl}"></video>
            </body></html>
          ` }}
          style={styles.videoContainer}
          allowsInlineMediaPlayback
        />
      </SafeAreaView>
    );
  }

  if (isPpt) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={handleClose} style={styles.headerBtn}>
            <Text style={styles.headerBtnText}>← Close</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle} numberOfLines={1}>{book.title}</Text>
        </View>
        <View style={styles.centerContent}>
          <Text style={styles.placeholderIcon}>📊</Text>
          <Text style={styles.infoText}>This is a PowerPoint file</Text>
          <Text style={styles.infoSubtext}>{book.title}</Text>
          <Text style={styles.infoSubtext}>by {book.author}</Text>
          <TouchableOpacity style={styles.downloadBtn} onPress={handleShare}>
            <Text style={styles.downloadBtnText}>Share this file</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  // PDF reader via WebView
  const pdfUrl = `${API_BASE_URL.replace('/api', '')}/api/serve.php?token=${token}&id=${book.ebook_id}`;

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={handleClose} style={styles.headerBtn}>
          <Text style={styles.headerBtnText}>← Close</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{book.title}</Text>
        <TouchableOpacity onPress={handleShare} style={styles.headerBtn}>
          <Text style={styles.headerBtnText}>Share</Text>
        </TouchableOpacity>
      </View>

      <WebView
        ref={webViewRef}
        source={{ uri: pdfUrl }}
        style={styles.webview}
        originWhitelist={['*']}
        javaScriptEnabled
        domStorageEnabled
        startInLoadingState
        renderLoading={() => (
          <View style={styles.webviewLoading}>
            <ActivityIndicator size="large" color="#228B22" />
            <Text style={styles.loadingText}>Loading PDF...</Text>
          </View>
        )}
        onError={() => setError('Failed to load the document. Please try again.')}
        onHttpError={(syntheticEvent) => {
          const { statusCode } = syntheticEvent.nativeEvent;
          if (statusCode === 401) setError('Session expired. Please login again.');
          else if (statusCode === 404) setError('File not found.');
          else setError(`Failed to load (HTTP ${statusCode})`);
        }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  centerContent: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 10,
    paddingVertical: 12,
    backgroundColor: '#228B22',
  },
  headerBtn: {
    padding: 8,
    minWidth: 60,
  },
  headerBtnText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
  },
  headerTitle: {
    flex: 1,
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
    marginHorizontal: 5,
  },
  webview: {
    flex: 1,
    backgroundColor: '#fff',
  },
  webviewLoading: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  videoContainer: {
    flex: 1,
    backgroundColor: '#000',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  errorText: {
    fontSize: 18,
    color: '#c00',
    textAlign: 'center',
    marginBottom: 20,
  },
  closeBtn: {
    backgroundColor: '#228B22',
    paddingHorizontal: 30,
    paddingVertical: 12,
    borderRadius: 8,
  },
  closeBtnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  placeholderIcon: {
    fontSize: 64,
    marginBottom: 15,
  },
  infoText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 10,
  },
  infoSubtext: {
    fontSize: 16,
    color: '#666',
    marginBottom: 5,
    textAlign: 'center',
  },
  downloadBtn: {
    backgroundColor: '#228B22',
    paddingHorizontal: 30,
    paddingVertical: 12,
    borderRadius: 8,
    marginTop: 20,
  },
  downloadBtnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
});
