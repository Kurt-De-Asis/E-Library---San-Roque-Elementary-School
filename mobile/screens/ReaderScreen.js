import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  Alert,
} from 'react-native';
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8080/e-library/web/api';

export default function ReaderScreen({ route, navigation }) {
  const { book } = route.params;
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(book.total_pages || 100);

  useEffect(() => {
    // Load reading progress
    loadReadingProgress();
  }, []);

  const loadReadingProgress = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/ebooks.php?action=get_progress&ebook_id=${book.ebook_id}`);
      if (response.data.success && response.data.progress) {
        setCurrentPage(response.data.progress.current_page || 1);
      }
    } catch (error) {
      console.error('Error loading progress:', error);
    }
  };

  const saveReadingProgress = async (page) => {
    try {
      await axios.post(`${API_BASE_URL}/ebooks.php`, {
        action: 'mark_read',
        ebook_id: book.ebook_id,
        page: page,
        total_pages: totalPages,
      });
    } catch (error) {
      console.error('Error saving progress:', error);
    }
  };

  const handlePageChange = (direction) => {
    let newPage = currentPage;
    if (direction === 'next' && currentPage < totalPages) {
      newPage = currentPage + 1;
    } else if (direction === 'prev' && currentPage > 1) {
      newPage = currentPage - 1;
    }

    if (newPage !== currentPage) {
      setCurrentPage(newPage);
      saveReadingProgress(newPage);
    }
  };

  const handleClose = () => {
    navigation.goBack();
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={handleClose} style={styles.closeButton}>
          <Text style={styles.closeButtonText}>← Close</Text>
        </TouchableOpacity>
        <View style={styles.bookInfo}>
          <Text style={styles.bookTitle} numberOfLines={1}>
            {book.title}
          </Text>
          <Text style={styles.pageInfo}>
            Page {currentPage} of {totalPages}
          </Text>
        </View>
      </View>

      <View style={styles.readerContainer}>
        <Text style={styles.placeholderText}>
          📖 PDF Reader
        </Text>
        <Text style={styles.instructionText}>
          This is a placeholder for the PDF reader.
        </Text>
        <Text style={styles.instructionText}>
          In a full implementation, you would integrate a PDF viewing library like react-native-pdf or expo-document-picker.
        </Text>
        <Text style={styles.bookDetails}>
          Currently reading: {book.title}
        </Text>
        <Text style={styles.bookDetails}>
          Author: {book.author}
        </Text>
      </View>

      <View style={styles.controls}>
        <TouchableOpacity
          style={[styles.controlButton, currentPage <= 1 && styles.controlButtonDisabled]}
          onPress={() => handlePageChange('prev')}
          disabled={currentPage <= 1}
        >
          <Text style={styles.controlButtonText}>Previous</Text>
        </TouchableOpacity>

        <View style={styles.pageIndicator}>
          <Text style={styles.pageText}>{currentPage} / {totalPages}</Text>
        </View>

        <TouchableOpacity
          style={[styles.controlButton, currentPage >= totalPages && styles.controlButtonDisabled]}
          onPress={() => handlePageChange('next')}
          disabled={currentPage >= totalPages}
        >
          <Text style={styles.controlButtonText}>Next</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 15,
    backgroundColor: '#4A90E2',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  closeButton: {
    padding: 5,
  },
  closeButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  bookInfo: {
    flex: 1,
    alignItems: 'center',
  },
  bookTitle: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  pageInfo: {
    color: '#E8F4FD',
    fontSize: 12,
  },
  readerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  placeholderText: {
    fontSize: 48,
    marginBottom: 20,
  },
  instructionText: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 10,
    lineHeight: 24,
  },
  bookDetails: {
    fontSize: 14,
    color: '#333',
    marginTop: 10,
  },
  controls: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  controlButton: {
    backgroundColor: '#4A90E2',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    minWidth: 100,
    alignItems: 'center',
  },
  controlButtonDisabled: {
    backgroundColor: '#ccc',
  },
  controlButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
  },
  pageIndicator: {
    alignItems: 'center',
  },
  pageText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
});
