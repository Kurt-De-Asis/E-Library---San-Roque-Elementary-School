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
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8080/e-library/web/api';

export default function HomeScreen({ navigation }) {
  const [user, setUser] = useState(null);
  const [featuredBooks, setFeaturedBooks] = useState([]);
  const [recentBooks, setRecentBooks] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadUserData();
    loadBooks();
  }, []);

  const loadUserData = async () => {
    try {
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        setUser(JSON.parse(userData));
      }
    } catch (error) {
      console.error('Error loading user data:', error);
    }
  };

  const loadBooks = async () => {
    try {
      const [featuredResponse, recentResponse] = await Promise.all([
        axios.get(`${API_BASE_URL}/ebooks.php?action=get_featured`),
        axios.get(`${API_BASE_URL}/ebooks.php?action=get_recent`),
      ]);

      if (featuredResponse.data.success) {
        setFeaturedBooks(featuredResponse.data.books);
      }
      if (recentResponse.data.success) {
        setRecentBooks(recentResponse.data.books);
      }
    } catch (error) {
      console.error('Error loading books:', error);
    } finally {
      setLoading(false);
    }
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
          {item.category}
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
        <Text style={styles.welcomeText}>
          Welcome, {user?.first_name || 'Student'}!
        </Text>
        <Text style={styles.subtitle}>
          {user?.grade_level ? `Grade ${user.grade_level}` : 'Explore our library'}
        </Text>
      </View>

      <FlatList
        data={featuredBooks}
        keyExtractor={(item) => item.ebook_id.toString()}
        renderItem={renderBookItem}
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.sectionContainer}
        ListHeaderComponent={
          <>
            <Text style={styles.sectionTitle}>Featured Books</Text>
          </>
        }
        ListFooterComponent={
          <>
            <Text style={styles.sectionTitle}>Recently Added</Text>
            <FlatList
              data={recentBooks}
              keyExtractor={(item) => `recent-${item.ebook_id}`}
              renderItem={renderBookItem}
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.sectionContainer}
            />
          </>
        }
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
  welcomeText: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#fff',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 16,
    color: '#E8F4FD',
  },
  sectionContainer: {
    padding: 15,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 15,
    marginLeft: 5,
  },
  bookCard: {
    backgroundColor: '#fff',
    borderRadius: 8,
    marginRight: 15,
    width: 140,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  bookCover: {
    width: '100%',
    height: 180,
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
