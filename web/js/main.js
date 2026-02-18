// Main JavaScript for E-Library System
// San Roque Elementary School

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', async function() {
    console.log('E-Library: Initializing dashboard...');
    
    try {
        // Check authentication status
        const sessionCheck = await checkSession();
        console.log('E-Library: Session check result:', sessionCheck);

        if (!sessionCheck.success || !sessionCheck.logged_in) {
            console.log('E-Library: Not logged in, redirecting to login');
            // Not logged in, redirect to login
            window.location.replace('login.php');
            return;
        }

        console.log('E-Library: User logged in:', sessionCheck.user);
        
        // Logged in, initialize the dashboard
        initializeDashboard(sessionCheck.user);
        
    } catch (error) {
        console.error('E-Library: Authentication check failed:', error);
        // On error, redirect to login
        window.location.replace('login.php');
    }
});

// Initialize dashboard
function initializeDashboard(user) {
    // Update user info in header
    const userNameElement = document.getElementById('userName');
    const welcomeUserElement = document.getElementById('welcomeUser');
    const gradeLevelInfoElement = document.getElementById('gradeLevelInfo');

    if (userNameElement) {
        userNameElement.textContent = user.full_name;
    }

    if (welcomeUserElement) {
        welcomeUserElement.textContent = user.full_name;
    }

    if (gradeLevelInfoElement) {
        if (user.user_type === 'student') {
            const gradeText = user.grade_level.charAt(0).toUpperCase() + user.grade_level.slice(1).replace(/(\d)/, ' $1');
            gradeLevelInfoElement.textContent = `Showing books for ${gradeText} students`;
        } else {
            gradeLevelInfoElement.textContent = 'Showing all books and materials';
        }
    }

    // Show/hide navigation items based on user type
    const teacherNav = document.getElementById('teacherNav');
    const adminNav = document.getElementById('adminNav');

    if (teacherNav && user.user_type === 'teacher') {
        teacherNav.style.display = 'flex';
    }

    if (adminNav && user.user_type === 'admin') {
        adminNav.style.display = 'flex';
    }

    // Initialize dashboard functionality
    initializeNavigation();
    initializeSearch();
    loadDashboardData();
}

// Initialize navigation
function initializeNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const section = this.getAttribute('onclick')?.match(/showSection\('(.+?)'\)/)?.[1];
            if (section) {
                showSection(section);
            }
        });
    });
}

// Show section
function showSection(sectionName) {
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });

    const activeNav = document.querySelector(`[onclick="showSection('${sectionName}')"]`);
    if (activeNav) {
        activeNav.classList.add('active');
    }

    // Update content sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });

    const activeSection = document.getElementById(`${sectionName}-section`);
    if (activeSection) {
        activeSection.classList.add('active');
    }

    // Load section data
    loadSectionData(sectionName);
}

// Load section data
async function loadSectionData(section) {
    switch (section) {
        case 'home':
            await loadHomeData();
            break;
        case 'browse':
            await loadBrowseData();
            break;
        case 'categories':
            await loadCategoriesData();
            break;
        case 'my-books':
            await loadMyBooksData();
            break;
    }
}

// Load home dashboard data
async function loadHomeData() {
    try {
        // Load featured books
        const featuredResponse = await fetch('api/ebooks.php?action=get_featured');
        const featuredResult = await featuredResponse.json();

        if (featuredResult.success) {
            renderBooksGrid('featuredBooks', featuredResult.books);
        }

        // Load recent books
        const recentResponse = await fetch('api/ebooks.php?action=get_recent');
        const recentResult = await recentResponse.json();

        if (recentResult.success) {
            renderBooksGrid('recentBooks', recentResult.books);
        }

    } catch (error) {
        console.error('Error loading home data:', error);
        showMessage('Failed to load books', 'error');
    }
}

// Load browse data
async function loadBrowseData() {
    try {
        const response = await fetch('api/ebooks.php?action=get_all');
        const result = await response.json();

        if (result.success) {
            renderBooksGrid('allBooks', result.books);
        }
    } catch (error) {
        console.error('Error loading books:', error);
        showMessage('Failed to load books', 'error');
    }
}

// Load categories data
async function loadCategoriesData() {
    try {
        const response = await fetch('api/ebooks.php?action=get_categories');
        const result = await response.json();

        if (result.success) {
            renderCategoriesGrid(result.categories);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        showMessage('Failed to load categories', 'error');
    }
}

// Load my books data
async function loadMyBooksData() {
    const container = document.getElementById('myBooks');
    if (container) {
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading your reading history...</div>';
    }
    
    try {
        const response = await fetch('api/ebooks.php?action=get_my_books');
        const result = await response.json();

        if (result.success) {
            if (result.books && result.books.length > 0) {
                renderMyBooksGrid('myBooks', result.books);
            } else {
                // Show empty state
                if (container) {
                    container.innerHTML = `
                        <div class="empty-state" style="text-align: center; padding: 3rem; color: var(--text-gray);">
                            <i class="fas fa-book-open" style="font-size: 4rem; color: var(--primary-blue); opacity: 0.5; margin-bottom: 1rem;"></i>
                            <h3 style="color: var(--primary-purple);">No Reading History Yet</h3>
                            <p>Start reading books and they will appear here!</p>
                            <button onclick="showSection('browse')" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-search"></i> Browse Books
                            </button>
                        </div>
                    `;
                }
            }
        } else {
            if (container) {
                container.innerHTML = '<div class="loading">Unable to load reading history.</div>';
            }
        }
    } catch (error) {
        console.error('Error loading my books:', error);
        if (container) {
            container.innerHTML = '<div class="loading">Failed to load your books.</div>';
        }
    }
}

// Render my books grid with progress info
function renderMyBooksGrid(containerId, books) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';

    books.forEach(book => {
        const card = document.createElement('div');
        card.className = 'book-card';
        card.onclick = () => openBook(book.ebook_id);

        const coverUrl = book.cover_image ? `uploads/covers/${book.cover_image}` : 'assets/images/default-book.png';

        const gradeText = book.grade_level === 'all' ? 'All Grades' :
            book.grade_level.charAt(0).toUpperCase() + book.grade_level.slice(1).replace(/(\d)/, ' $1');
        
        const progress = book.progress ? Math.round(book.progress) : 0;
        const progressBar = progress > 0 ? `
            <div class="reading-progress-bar" style="height: 4px; background: #E1E8ED; border-radius: 2px; margin-top: 0.5rem;">
                <div style="width: ${progress}%; height: 100%; background: linear-gradient(90deg, var(--primary-blue), var(--primary-green)); border-radius: 2px;"></div>
            </div>
            <small style="color: var(--text-gray);">${progress}% read</small>
        ` : '';

        card.innerHTML = `
            <div class="book-cover">
                <img src="${coverUrl}" alt="${book.title}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="book-icon" style="display: none;">
                    <i class="fas fa-book"></i>
                </div>
            </div>
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">By: ${book.author || 'Unknown'}</p>
                <div class="book-meta">
                    <span class="book-category">${book.category}</span>
                    <span class="book-grade">${gradeText}</span>
                </div>
                ${progressBar}
            </div>
        `;

        container.appendChild(card);
    });
}

// Render books grid
function renderBooksGrid(containerId, books) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';

    if (!books || books.length === 0) {
        container.innerHTML = '<div class="loading">No books found.</div>';
        return;
    }

    books.forEach(book => {
        const bookCard = createBookCard(book);
        container.appendChild(bookCard);
    });
}

// Create book card element
function createBookCard(book) {
    const card = document.createElement('div');
    card.className = 'book-card';
    card.onclick = () => openBook(book.ebook_id);

    const coverUrl = book.cover_image ? `uploads/covers/${book.cover_image}` : 'assets/images/default-book.png';

    const gradeText = book.grade_level === 'all' ? 'All Grades' :
        book.grade_level.charAt(0).toUpperCase() + book.grade_level.slice(1).replace(/(\d)/, ' $1');

    card.innerHTML = `
        <div class="book-cover">
            <img src="${coverUrl}" alt="${book.title}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
            <div class="book-icon" style="display: none;">
                <i class="fas fa-book"></i>
            </div>
        </div>
        <div class="book-info">
            <h3 class="book-title">${book.title}</h3>
            <p class="book-author">By: ${book.author || 'Unknown'}</p>
            <div class="book-meta">
                <span class="book-category">${book.category}</span>
                <span class="book-grade">${gradeText}</span>
            </div>
        </div>
    `;

    return card;
}

// Render categories grid
function renderCategoriesGrid(categories) {
    const container = document.getElementById('categoriesGrid');
    if (!container) return;

    container.innerHTML = '';

    if (!categories || categories.length === 0) {
        container.innerHTML = '<div class="loading">No categories found.</div>';
        return;
    }

    categories.forEach(category => {
        const categoryCard = createCategoryCard(category);
        container.appendChild(categoryCard);
    });
}

// Create category card element
function createCategoryCard(category) {
    const card = document.createElement('div');
    card.className = 'category-card';
    card.onclick = () => browseCategory(category.category_name);

    const iconClass = category.icon || 'fas fa-book';

    card.innerHTML = `
        <div class="category-icon">
            <i class="${iconClass}"></i>
        </div>
        <h3 class="category-title">${category.category_name}</h3>
        <p class="category-description">${category.description || 'Browse books in this category'}</p>
    `;

    return card;
}

// Open book
function openBook(bookId) {
    window.location.href = `reader.php?id=${bookId}`;
}

// Browse category
async function browseCategory(category) {
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    const browseNav = document.querySelector(`[onclick="showSection('browse')"]`);
    if (browseNav) browseNav.classList.add('active');

    // Show browse section without loading all books
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    const browseSection = document.getElementById('browse-section');
    if (browseSection) browseSection.classList.add('active');

    // Set category filter and load filtered books
    const subjectFilter = document.getElementById('subjectFilter');
    if (subjectFilter) {
        subjectFilter.value = category;
    }
    
    // Load filtered books directly
    try {
        const url = `api/ebooks.php?action=get_filtered&subject=${encodeURIComponent(category)}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            renderBooksGrid('allBooks', result.books);
        } else {
            renderBooksGrid('allBooks', []);
        }
    } catch (error) {
        console.error('Filter error:', error);
        showMessage('Failed to load books for this category', 'error');
    }
}

// Search books
async function searchBooks() {
    const searchInput = document.getElementById('searchInput');
    const query = searchInput ? searchInput.value.trim() : '';

    // Hide suggestions when searching
    hideSuggestions();

    if (query === '') {
        // No search query, reload current section
        const activeSection = document.querySelector('.content-section.active');
        const sectionName = activeSection?.id?.replace('-section', '');
        if (sectionName) {
            loadSectionData(sectionName);
        }
        return;
    }

    try {
        const response = await fetch(`api/ebooks.php?action=search&q=${encodeURIComponent(query)}`);
        const result = await response.json();

        if (result.success) {
            // Show search results in browse section
            showSection('browse');
            renderBooksGrid('allBooks', result.books);
            
            // Update section header to show search results
            const browseHeader = document.querySelector('#browse-section .section-header h2');
            if (browseHeader) {
                browseHeader.innerHTML = `<i class="fas fa-search"></i> Search Results for "${query}"`;
            }
        }
    } catch (error) {
        console.error('Search error:', error);
        showMessage('Search failed', 'error');
    }
}

// Live search with suggestions
let searchTimeout = null;
let allBooksCache = null;

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) {
        console.error('Search input not found');
        return;
    }
    
    // Add input event for live search
    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();

        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        if (query.length < 1) {
            hideSuggestions();
            return;
        }

        // Debounce search - wait 200ms after typing stops
        searchTimeout = setTimeout(() => {
            showSuggestions(query);
        }, 200);
    });
    
    // Handle Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchBooks();
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            hideSuggestions();
        }
    });
}

async function showSuggestions(query) {
    // Fetch all books if not cached
    if (!allBooksCache) {
        try {
            const response = await fetch('api/ebooks.php?action=get_all');
            const result = await response.json();
            if (result.success) {
                allBooksCache = result.books;
            }
        } catch (error) {
            console.error('Error loading books for suggestions:', error);
            return;
        }
    }

    // Filter books based on query
    const matches = allBooksCache.filter(book => {
        const title = (book.title || '').toLowerCase();
        const author = (book.author || '').toLowerCase();
        const category = (book.category || '').toLowerCase();

        return title.includes(query) ||
               author.includes(query) ||
               category.includes(query);
    }).slice(0, 6); // Limit to 6 suggestions

    const suggestionsContainer = document.getElementById('searchSuggestions');
    if (!suggestionsContainer) {
        console.error('searchSuggestions container not found');
        return;
    }

    if (matches.length === 0) {
        suggestionsContainer.innerHTML = '<div class="suggestion-item no-results">No books found</div>';
        suggestionsContainer.style.display = 'block';
        return;
    }

    suggestionsContainer.innerHTML = matches.map(book => `
        <div class="suggestion-item" onclick="openBook(${book.ebook_id})">
            <div class="suggestion-cover">
                ${book.cover_image ?
                    `<img src="uploads/covers/${book.cover_image}" alt="${book.title}" onerror="this.style.display='none'">` :
                    `<i class="fas fa-book"></i>`
                }
            </div>
            <div class="suggestion-info">
                <div class="suggestion-title">${highlightMatch(book.title, query)}</div>
                <div class="suggestion-author">${book.author || 'Unknown Author'}</div>
                <div class="suggestion-category">${book.category}</div>
            </div>
        </div>
    `).join('');

    suggestionsContainer.style.display = 'block';
}

function highlightMatch(text, query) {
    if (!text) return '';
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
}

function hideSuggestions() {
    const suggestionsContainer = document.getElementById('searchSuggestions');
    if (suggestionsContainer) {
        suggestionsContainer.style.display = 'none';
    }
}

function selectSuggestion(bookId) {
    hideSuggestions();
    openBook(bookId);
}

// Filter books
async function filterBooks() {
    const subjectFilter = document.getElementById('subjectFilter');
    const contentTypeFilter = document.getElementById('contentTypeFilter');
    const subject = subjectFilter ? subjectFilter.value : '';
    const contentType = contentTypeFilter ? contentTypeFilter.value : '';

    try {
        let url = 'api/ebooks.php?action=get_filtered';
        if (subject) url += `&subject=${encodeURIComponent(subject)}`;
        if (contentType) url += `&content_type=${encodeURIComponent(contentType)}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            renderBooksGrid('allBooks', result.books);
        } else {
            renderBooksGrid('allBooks', []);
        }
    } catch (error) {
        console.error('Filter error:', error);
        showMessage('Filter failed', 'error');
    }
}

// Logout function
async function logout() {
    try {
        const response = await fetch('api/auth.php?action=logout');
        const result = await response.json();

        if (result.success) {
            // Clear any cached data
            localStorage.clear();
            sessionStorage.clear();

            // Redirect to login
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Logout error:', error);
        // Force redirect even if logout fails
        window.location.href = 'login.php';
    }
}

// Show message
function showMessage(message, type = 'info') {
    // Create message element if it doesn't exist
    let messageElement = document.getElementById('mainMessage');
    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.id = 'mainMessage';
        messageElement.className = 'message';
        document.querySelector('.main-content').prepend(messageElement);
    }

    messageElement.innerHTML = message;
    messageElement.className = `message ${type}`;
    messageElement.style.display = 'block';

    // Auto hide after 3 seconds
    setTimeout(() => {
        messageElement.style.display = 'none';
    }, 3000);
}

// Check session status
async function checkSession() {
    try {
        const response = await fetch('api/auth.php?action=check-session');
        const result = await response.json();

        return result;
    } catch (error) {
        console.error('Session check error:', error);
        return { success: true, logged_in: false };
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bookModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }

    // Escape to close modals
    if (e.key === 'Escape') {
        const modal = document.getElementById('bookModal');
        if (modal && modal.style.display === 'block') {
            modal.style.display = 'none';
        }
    }
});

// Load dashboard data on page load
async function loadDashboardData() {
    // Show home section by default
    showSection('home');
}
