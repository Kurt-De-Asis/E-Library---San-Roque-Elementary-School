import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  Image,
  SafeAreaView,
  ActivityIndicator,
  TextInput,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8080/e-library/web/api';

export default function BrowseScreen({ navigation }) {
  const [books, setBooks] = useState([]);
  const [filteredBooks, setFilteredBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [subjectFilter, setSubjectFilter] = useState('');
  const [contentTypeFilter, setContentTypeFilter] = useState('');

  useEffect(() => {
    loadBooks();
  }, []);

  useEffect(() => {
    filterBooks();
  }, [books, searchQuery, subjectFilter, contentTypeFilter]);

  const loadBooks = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/ebooks.php?action=get_all`);
      if (response.data.success) {
        setBooks(response.data.books);
        setFilteredBooks(response.data.books);
      }
    } catch (error) {
      console.error('Error loading books:', error);
    } finally {
      setLoading(false);
    }
  };

  const filterBooks = () => {
    let filtered = books;

    if (searchQuery) {
      filtered = filtered.filter(book =>
        book.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        book.author.toLowerCase().includes(searchQuery.toLowerCase()) ||
        book.category.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }

    if (subjectFilter) {
      filtered = filtered.filter(book => book.category === subjectFilter);
    }

    if (contentTypeFilter) {
      filtered = filtered.filter(book => book.content_type === contentTypeFilter);
    }

    setFilteredBooks(filtered);
  };

  const renderBookItem = ({ item }) => (
    <TouchableOpacity
      style={styles.bookCard}
      onPress={() => navigation.navigate('BookDetail', { book: item })}
    >
      <Image
        source={{
          uri: `http://localhost:8080/e-library/web/uploads/covers/${item.cover_image}` ||
               'https://via.placeholder.com/120x160/cccccc/666666?text=No+Cover'
        }}
        style={styles.bookCover}
        resizeMode="cover"
      />
      <View style={styles.bookInfo}>
        <Text style={styles.bookTitle} numberOfLines={2}>
          {item.title}
        </Text>
        <Text style={styles.bookAuthor} numberOfLines={1}>
          by {item.author}
        </Text>
        <Text style={styles.bookCategory}>
          {item.category} • {item.grade_level}
        </Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#4A90E2" />
          <Text style={styles.loadingText}>Loading books...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Browse All Books</Text>
        <Text style={styles.subtitle}>{filteredBooks.length} books available</Text>
      </View>

      <View style={styles.filtersContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder="Search books, authors, or subjects..."
          value={searchQuery}
          onChangeText={setSearchQuery}
        />

        <View style={styles.filterRow}>
          <View style={styles.pickerContainer}>
            <Text style={styles.filterLabel}>Subject:</Text>
            <View style={styles.pickerWrapper}>
              <Picker
                selectedValue={subjectFilter}
                onValueChange={setSubjectFilter}
                style={styles.picker}
              >
                <Picker.Item label="All Subjects" value="" />
                <Picker.Item label="English" value="English" />
                <Picker.Item label="Mathematics" value="Mathematics" />
                <Picker.Item label="Science" value="Science" />
                <Picker.Item label="Filipino" value="Filipino" />
                <Picker.Item label="Araling Panlipunan" value="Araling Panlipunan" />
                <Picker.Item label="MAPEH" value="MAPEH" />
              </Picker>
            </View>
          </View>

          <View style={styles.pickerContainer}>
            <Text style={styles.filterLabel}>Type:</Text>
            <View style={styles.pickerWrapper}>
              <Picker
                selectedValue={contentTypeFilter}
                onValueChange={setContentTypeFilter}
                style={styles.picker}
              >
                <Picker.Item label="All Types" value="" />
                <Picker.Item label="Books" value="book" />
                <Picker.Item label="Modules" value="module" />
                <Picker.Item label="Lessons" value="lesson" />
                <Picker.Item label="Reference" value="reference" />
              </Picker>
            </View>
          </View>
        </View>
      </View>

      <FlatList
        data={filteredBooks}
        keyExtractor={(item) => item.ebook_id.toString()}
        renderItem={renderBookItem}
        numColumns={2}
        contentContainerStyle={styles.booksContainer}
        showsVerticalScrollIndicator={false}
      />
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
  header: {
    padding: 20,
    backgroundColor: '#4A90E2',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#fff',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 16,
    color: '#E8F4FD',
  },
  filtersContainer: {
    backgroundColor: '#fff',
    padding: 15,
    marginHorizontal: 15,
    marginTop: -10,
    borderRadius: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  searchInput: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    paddingHorizontal: 15,
    paddingVertical: 12,
    fontSize: 16,
    marginBottom: 15,
  },
  filterRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  pickerContainer: {
    flex: 1,
    marginHorizontal: 5,
  },
  filterLabel: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  pickerWrapper: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    backgroundColor: '#f9f9f9',
  },
  picker: {
    height: 50,
  },
  booksContainer: {
    padding: 15,
  },
  bookCard: {
    backgroundColor: '#fff',
    borderRadius: 8,
    margin: 5,
    flex: 1,
    maxWidth: '48%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  bookCover: {
    width: '100%',
    height: 150,
    borderTopLeftRadius: 8,
    borderTopRightRadius: 8,
  },
  bookInfo: {
    padding: 10,
  },
  bookTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  bookAuthor: {
    fontSize: 12,
    color: '#666',
    marginBottom: 3,
  },
  bookCategory: {
    fontSize: 11,
    color: '#4A90E2',
    fontWeight: '500',
  },
});
