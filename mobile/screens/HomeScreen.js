import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  Image,
  SafeAreaView,
  ActivityIndicator,
  RefreshControl,
  TextInput,
  Alert,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../src/api';
import { COVERS_URL } from '../config/api';

export default function HomeScreen({ navigation }) {
  const [user, setUser] = useState(null);
  const [featuredBooks, setFeaturedBooks] = useState([]);
  const [recentBooks, setRecentBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const userData = await AsyncStorage.getItem('user');
      if (userData) setUser(JSON.parse(userData));

      const [featuredResponse, recentResponse] = await Promise.all([
        api.get('/ebooks.php?action=get_featured'),
        api.get('/ebooks.php?action=get_recent'),
      ]);

      if (featuredResponse.data.success) setFeaturedBooks(featuredResponse.data.books);
      if (recentResponse.data.success) setRecentBooks(recentResponse.data.books);
    } catch (error) {
      console.error('Error loading data:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = useCallback(() => {
    setRefreshing(true);
    loadData();
  }, []);

  const handleSearch = () => {
    if (searchQuery.trim()) {
      navigation.navigate('Browse', { search: searchQuery.trim() });
    }
  };

  const handleLogout = () => {
    Alert.alert('Logout', 'Are you sure you want to logout?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Logout',
        style: 'destructive',
        onPress: async () => {
          try { await api.get('/auth.php?action=logout'); } catch { }
          await AsyncStorage.multiRemove(['token', 'user']);
          navigation.replace('Login');
        },
      },
    ]);
  };

  const renderBookItem = ({ item }) => (
    <TouchableOpacity
      style={styles.bookCard}
      onPress={() => navigation.navigate('BookDetail', { book: item })}
    >
      <Image
        source={{
          uri: item.cover_image
            ? `${COVERS_URL}/${item.cover_image}`
            : 'https://via.placeholder.com/120x160/cccccc/666666?text=No+Cover',
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
        <Text style={styles.bookCategory}>{item.category}</Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#228B22" />
          <Text style={styles.loadingText}>Loading books...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <View style={styles.headerRow}>
          <View style={styles.headerText}>
            <Text style={styles.welcomeText}>
              Welcome, {user?.full_name || 'Student'}!
            </Text>
            <Text style={styles.subtitle}>
              {user?.grade_level
                ? `Grade ${user.grade_level}`
                : 'Explore our library'}
            </Text>
          </View>
          <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
            <Text style={styles.logoutText}>Logout</Text>
          </TouchableOpacity>
        </View>
        <View style={styles.searchContainer}>
          <TextInput
            style={styles.searchInput}
            placeholder="Search books..."
            placeholderTextColor="#ccc"
            value={searchQuery}
            onChangeText={setSearchQuery}
            onSubmitEditing={handleSearch}
            returnKeyType="search"
          />
        </View>
      </View>

      <FlatList
        data={featuredBooks}
        keyExtractor={(item) => item.ebook_id.toString()}
        renderItem={renderBookItem}
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.sectionContainer}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#228B22']} />
        }
        ListHeaderComponent={
          <Text style={styles.sectionTitle}>Featured Books</Text>
        }
        ListFooterComponent={
          <>
            <Text style={styles.sectionTitle}>Recently Added</Text>
            {recentBooks.length > 0 ? (
              <FlatList
                data={recentBooks}
                keyExtractor={(item) => `recent-${item.ebook_id}`}
                renderItem={renderBookItem}
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.sectionContainer}
              />
            ) : (
              <Text style={styles.emptyText}>No recent books available</Text>
            )}
          </>
        }
        ListEmptyComponent={
          <View style={styles.emptySection}>
            <Text style={styles.emptyText}>No featured books available</Text>
          </View>
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
    padding: 15,
    backgroundColor: '#228B22',
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  headerText: {
    flex: 1,
  },
  welcomeText: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#fff',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 14,
    color: '#E8F4E8',
  },
  logoutBtn: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 15,
    paddingVertical: 8,
    borderRadius: 6,
  },
  logoutText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
  },
  searchContainer: {
    marginTop: 12,
  },
  searchInput: {
    backgroundColor: '#fff',
    borderRadius: 8,
    paddingHorizontal: 15,
    paddingVertical: 10,
    fontSize: 16,
    color: '#333',
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
    color: '#228B22',
    fontWeight: '500',
  },
  emptyText: {
    fontSize: 14,
    color: '#999',
    textAlign: 'center',
    padding: 20,
  },
  emptySection: {
    padding: 40,
  },
});
