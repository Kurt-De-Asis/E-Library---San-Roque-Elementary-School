import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  Image,
  SafeAreaView,
  ActivityIndicator,
  Alert,
} from 'react-native';
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8080/e-library/web/api';

export default function BookDetailScreen({ route, navigation }) {
  const { book } = route.params;
  const [bookDetails, setBookDetails] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadBookDetails();
  }, []);

  const loadBookDetails = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/ebooks.php?action=get_book&id=${book.ebook_id}`);
      if (response.data.success) {
        setBookDetails(response.data.book);
      }
    } catch (error) {
      console.error('Error loading book details:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleReadBook = () => {
    navigation.navigate('Reader', { book: bookDetails || book });
  };

  const handleDownloadBook = async () => {
    try {
      // For now, just show an alert. In a real app, you'd handle downloading
      Alert.alert('Download', 'Download functionality would be implemented here');
    } catch (error) {
      console.error('Download error:', error);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#4A90E2" />
          <Text style={styles.loadingText}>Loading book details...</Text>
        </View>
      </SafeAreaView>
    );
  }

  const displayBook = bookDetails || book;

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView style={styles.scrollView}>
        <View style={styles.bookHeader}>
          <Image
            source={{
              uri: `http://localhost:8080/e-library/web/uploads/covers/${displayBook.cover_image}` ||
                   'https://via.placeholder.com/200x300/cccccc/666666?text=No+Cover'
            }}
            style={styles.bookCover}
            resizeMode="cover"
          />
          <View style={styles.bookBasicInfo}>
            <Text style={styles.bookTitle}>{displayBook.title}</Text>
            <Text style={styles.bookAuthor}>by {displayBook.author}</Text>
            <Text style={styles.bookCategory}>{displayBook.category}</Text>
            <Text style={styles.bookGrade}>Grade Level: {displayBook.grade_level}</Text>
            <Text style={styles.bookType}>Type: {displayBook.content_type}</Text>
          </View>
        </View>

        {displayBook.description && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Description</Text>
            <Text style={styles.description}>{displayBook.description}</Text>
          </View>
        )}

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Details</Text>
          <View style={styles.detailsGrid}>
            <View style={styles.detailItem}>
              <Text style={styles.detailLabel}>Subject:</Text>
              <Text style={styles.detailValue}>{displayBook.subject || 'N/A'}</Text>
            </View>
            <View style={styles.detailItem}>
              <Text style={styles.detailLabel}>Published:</Text>
              <Text style={styles.detailValue}>
                {displayBook.created_at ? new Date(displayBook.created_at).getFullYear() : 'N/A'}
              </Text>
            </View>
            <View style={styles.detailItem}>
              <Text style={styles.detailLabel}>ISBN:</Text>
              <Text style={styles.detailValue}>{displayBook.isbn || 'N/A'}</Text>
            </View>
            <View style={styles.detailItem}>
              <Text style={styles.detailLabel}>Pages:</Text>
              <Text style={styles.detailValue}>{displayBook.total_pages || 'N/A'}</Text>
            </View>
          </View>
        </View>

        <View style={styles.actionsContainer}>
          <TouchableOpacity style={styles.readButton} onPress={handleReadBook}>
            <Text style={styles.readButtonText}>Read Book</Text>
          </TouchableOpacity>

          {displayBook.content_type !== 'book' && (
            <TouchableOpacity style={styles.downloadButton} onPress={handleDownloadBook}>
              <Text style={styles.downloadButtonText}>Download</Text>
            </TouchableOpacity>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  scrollView: {
    flex: 1,
  },
  bookHeader: {
    backgroundColor: '#fff',
    padding: 20,
    flexDirection: 'row',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  bookCover: {
    width: 120,
    height: 180,
    borderRadius: 8,
    marginRight: 20,
  },
  bookBasicInfo: {
    flex: 1,
  },
  bookTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 8,
  },
  bookAuthor: {
    fontSize: 16,
    color: '#666',
    marginBottom: 8,
  },
  bookCategory: {
    fontSize: 14,
    color: '#4A90E2',
    fontWeight: 'bold',
    marginBottom: 4,
  },
  bookGrade: {
    fontSize: 14,
    color: '#666',
    marginBottom: 4,
  },
  bookType: {
    fontSize: 14,
    color: '#666',
  },
  section: {
    backgroundColor: '#fff',
    marginTop: 15,
    padding: 20,
    marginHorizontal: 15,
    borderRadius: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 15,
  },
  description: {
    fontSize: 16,
    color: '#666',
    lineHeight: 24,
  },
  detailsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  detailItem: {
    width: '50%',
    marginBottom: 15,
  },
  detailLabel: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  detailValue: {
    fontSize: 14,
    color: '#666',
  },
  actionsContainer: {
    padding: 20,
    backgroundColor: '#fff',
    marginTop: 15,
    marginBottom: 20,
    marginHorizontal: 15,
    borderRadius: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  readButton: {
    backgroundColor: '#4A90E2',
    paddingVertical: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 10,
  },
  readButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  downloadButton: {
    backgroundColor: '#28a745',
    paddingVertical: 15,
    borderRadius: 8,
    alignItems: 'center',
  },
  downloadButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
});
