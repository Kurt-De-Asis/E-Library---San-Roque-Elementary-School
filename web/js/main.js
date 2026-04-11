// Main JavaScript for E-Library System
// San Roque Elementary School

console.log('E-Library System initialized');

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
        if (user.user_type === 'student' || user.user_type === 'parent') {
            const prefix = user.user_type === 'parent' ? "Your child's " : "";
            const gradeText = user.grade_level.charAt(0).toUpperCase() + user.grade_level.slice(1).replace(/(\d)/, ' $1');
            gradeLevelInfoElement.textContent = `Showing books for ${prefix}${gradeText} students`;
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
        } else {
            document.getElementById('featuredBooks').innerHTML = '<div class="loading">Failed to load featured books.</div>';
        }

        // Load recent books
        const recentResponse = await fetch('api/ebooks.php?action=get_recent');
        const recentResult = await recentResponse.json();

        if (recentResult.success) {
            renderBooksGrid('recentBooks', recentResult.books);
        } else {
            document.getElementById('recentBooks').innerHTML = '<div class="loading">Failed to load recent books.</div>';
        }

    } catch (error) {
        console.error('Error loading home data:', error);
        document.querySelectorAll('.books-grid').forEach(grid => {
            grid.innerHTML = '<div class="loading">Error connecting to server. Please refresh.</div>';
        });
        showMessage('Failed to load books. Please check your connection.', 'error');
    }
}

// Load browse data
async function loadBrowseData() {
    try {
        const response = await fetch('api/ebooks.php?action=get_all');
        const result = await response.json();

        if (result.success) {
            setAllBooksData(result.books);
        }
    } catch (error) {
        console.error('Error loading books:', error);
        showMessage('Failed to load books', 'error');
    }
}

let selectedCategoryType = '';
let allCategoriesCache = [];

// Load categories data
async function loadCategoriesData() {
    try {
        if (allCategoriesCache.length === 0) {
            const response = await fetch('api/ebooks.php?action=get_categories');
            const result = await response.json();
            if (result.success) {
                allCategoriesCache = result.categories;
            }
        }
        
        // Always show types first unless we are deep in a category
        if (!selectedCategoryType) {
            renderCategoryTypes();
        } else {
            renderCategoriesGrid(allCategoriesCache);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        showMessage('Failed to load categories', 'error');
    }
}

// Render the 3 main types
function renderCategoryTypes() {
    const container = document.getElementById('categoriesGrid');
    const backBtn = document.getElementById('btnBackToTypes');
    const sectionTitle = document.getElementById('categorySectionTitle');
    
    if (!container) return;
    
    selectedCategoryType = '';
    if (backBtn) backBtn.style.display = 'none';
    if (sectionTitle) sectionTitle.innerHTML = '<i class="fas fa-th"></i> Browse by Material Type';

    container.innerHTML = '';
    
    const types = [
        { id: 'book', name: 'Books', icon: 'fas fa-book', desc: 'Read digital books and modules' },
        { id: 'lesson', name: 'Powerpoint', icon: 'fas fa-file-powerpoint', desc: 'View educational presentations' },
        { id: 'video', name: 'Educational Video', icon: 'fas fa-video', desc: 'Watch educational videos' }
    ];

    types.forEach(type => {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.onclick = () => selectCategoryType(type.id, type.name);
        
        card.innerHTML = `
            <div class="category-icon">
                <i class="${type.icon}"></i>
            </div>
            <h3 class="category-title">${type.name}</h3>
            <p class="category-description">${type.desc}</p>
        `;
        container.appendChild(card);
    });
}

function selectCategoryType(typeId, typeName) {
    selectedCategoryType = typeId;
    const sectionTitle = document.getElementById('categorySectionTitle');
    const backBtn = document.getElementById('btnBackToTypes');
    
    if (sectionTitle) sectionTitle.innerHTML = `<i class="fas fa-th"></i> ${typeName} Categories`;
    if (backBtn) backBtn.style.display = 'block';
    
    renderCategoriesGrid(allCategoriesCache);
}

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
        <p class="category-description">${category.description || 'Browse items in this category'}</p>
    `;

    return card;
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

    // Set filters
    const subjectFilter = document.getElementById('subjectFilter');
    const contentTypeFilter = document.getElementById('contentTypeFilter');
    
    if (subjectFilter) subjectFilter.value = category;
    if (contentTypeFilter) contentTypeFilter.value = selectedCategoryType || '';
    
    // Load filtered books directly
    try {
        let url = `api/ebooks.php?action=get_filtered&subject=${encodeURIComponent(category)}`;
        if (selectedCategoryType) {
            url += `&content_type=${encodeURIComponent(selectedCategoryType)}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            setAllBooksData(result.books);
        } else {
            setAllBooksData([]);
        }
    } catch (error) {
        console.error('Filter error:', error);
        showMessage('Failed to load items for this category', 'error');
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
        const card = createBookCard(book);
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
    const DEFAULT_BOOK_COVER = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='120' viewBox='0 0 100 120'%3E%3Crect width='100' height='120' fill='%23e8f5e9'/%3E%3Cpath d='M20 20h60v80H20z' fill='%23c8e6c9'/%3E%3Ctext x='50' y='65' font-family='Arial' font-size='12' fill='%232e7d32' text-anchor='middle'%3ENo Cover%3C/text%3E%3C/svg%3E";
    const card = document.createElement('div');
    card.className = 'book-card';
    card.onclick = () => openBook(book.ebook_id);

    const hasCover = !!book.cover_image && book.cover_image !== '';
    const coverUrl = hasCover ? `uploads/covers/${book.cover_image}` : DEFAULT_BOOK_COVER;

    const gradeText = book.grade_level === 'all' ? 'All Grades' :
        book.grade_level.charAt(0).toUpperCase() + book.grade_level.slice(1).replace(/(\d)/, ' $1');

    // Determine icon for fallback
    let iconClass = 'fa-book';
    let typeLabel = 'BOOK';
    
    if (book.content_type === 'video') {
        iconClass = 'fa-video';
        typeLabel = 'VIDEO';
    } else if (book.content_type === 'lesson') {
        iconClass = 'fa-file-powerpoint';
        typeLabel = 'LESSON';
    } else if (book.content_type === 'module') {
        iconClass = 'fa-file-alt';
        typeLabel = 'MODULE';
    }

    // Only show the badge if there is NO cover photo
    const typeBadge = !hasCover ? `<span class="type-badge ${book.content_type}">${typeLabel}</span>` : '';

    const progress = book.progress ? Math.round(book.progress) : 0;
    const progressBar = progress > 0 ? `
        <div class="reading-progress-bar" style="height: 4px; background: #E1E8ED; border-radius: 2px; margin-top: 0.5rem;">
            <div style="width: ${progress}%; height: 100%; background: linear-gradient(90deg, var(--primary-blue), var(--primary-green)); border-radius: 2px;"></div>
        </div>
        <small style="color: var(--text-gray);">${progress}% read</small>
    ` : '';

    card.innerHTML = `
        <div class="book-cover" style="background: ${hasCover ? '#fff' : 'var(--bg-yellow)'};">
            ${typeBadge}
            <img src="${coverUrl}" alt="${book.title}" 
                 style="z-index: 2; position: relative; width: 100%; height: 100%; object-fit: contain;" 
                 onerror="this.style.display='none'; this.parentElement.querySelector('.book-icon').style.display='flex'; this.parentElement.style.background='var(--bg-yellow)'">
            
            <div class="book-icon" style="display: ${hasCover ? 'none' : 'flex'}; flex-direction: column; align-items: center; gap: 0.5rem; position: absolute; z-index: 1;">
                <i class="fas ${iconClass}" style="font-size: 3rem; opacity: 0.2; color: var(--text-dark);"></i>
                <span style="font-size: 0.8rem; font-weight: bold; opacity: 0.2; letter-spacing: 2px;">${typeLabel}</span>
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

    return card;
}

// Update renderBooksGrid to be the standard
function renderBooksList(containerId, books) {
    renderBooksGrid(containerId, books);
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

// Open book
function openBook(bookId) {
    window.location.href = `reader.php?id=${bookId}`;
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
            setAllBooksData(result.books);
            
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
            setAllBooksData(result.books);
        } else {
            setAllBooksData([]);
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

    // Arrow keys for page navigation in browse section
    const browseSection = document.getElementById('browse-section');
    if (browseSection && browseSection.classList.contains('active')) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            changePage(1);
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            changePage(-1);
        }
    }
});

// Pagination for books
let currentBooksPage = 1;
let booksPerPage = 12;
let allBooksData = [];

function setAllBooksData(books) {
    allBooksData = books || [];
    currentBooksPage = 1;
    renderBooksPage();
}

function renderBooksPage() {
    const start = (currentBooksPage - 1) * booksPerPage;
    const end = start + booksPerPage;
    const pageBooks = allBooksData.slice(start, end);
    
    renderBooksGrid('allBooks', pageBooks);
    updatePaginationControls();
}

function updatePaginationControls() {
    const totalPages = Math.ceil(allBooksData.length / booksPerPage) || 1;
    const pageInfo = document.getElementById('pageInfo');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    
    if (pageInfo) {
        pageInfo.textContent = `Page ${currentBooksPage} of ${totalPages}`;
    }
    if (prevBtn) {
        prevBtn.disabled = currentBooksPage <= 1;
    }
    if (nextBtn) {
        nextBtn.disabled = currentBooksPage >= totalPages;
    }
}

function changePage(direction) {
    const totalPages = Math.ceil(allBooksData.length / booksPerPage) || 1;
    const newPage = currentBooksPage + direction;
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentBooksPage = newPage;
        renderBooksPage();
    }
}

// Load dashboard data on page load
async function loadDashboardData() {
    // Show home section by default
    showSection('home');
}
