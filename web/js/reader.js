// E-Book Reader JavaScript for E-Library System
// San Roque Elementary School

let currentBook = null;
let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1;
let readingProgress = 0;
let autoSaveEnabled = true;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', async function() {
    // Get book ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const bookId = urlParams.get('id');

    if (!bookId) {
        showError('No book specified');
        return;
    }

    // Check authentication
    const sessionCheck = await checkSession();
    if (!sessionCheck.logged_in) {
        window.location.href = 'login.php';
        return;
    }

    // Load book
    await loadBook(bookId);

    // Initialize reader
    initializeReader();
});

// Initialize reader
function initializeReader() {
    // Initialize controls
    initializeNavigation();
    initializeZoom();
    initializeBookmarks();
    initializeSettings();

    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboard);

    // Auto-save reading progress
    setInterval(saveReadingProgress, 30000); // Save every 30 seconds
}

// Load book details and content
async function loadBook(bookId) {
    try {
        // Show loading
        showLoading('Loading book...');

        // Load book details
        const response = await fetch(`api/ebooks.php?action=get_book&id=${bookId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load book');
        }

        currentBook = result.book;

        // Update UI
        updateBookInfo();

        // Determine file type from extension
        const filePath = currentBook.file_path || '';
        const fileExt = filePath.split('.').pop().toLowerCase();

        // Load book content
        if (fileExt === 'pdf') {
            await loadPDF('uploads/books/' + filePath);
        } else if (fileExt === 'epub') {
            await loadEPUB('uploads/books/' + filePath);
        } else if (filePath) {
            // Try loading as PDF by default
            await loadPDF('uploads/books/' + filePath);
        } else {
            throw new Error('No book file available');
        }

        // Load reading progress
        await loadReadingProgress();

        // Load bookmarks
        await loadBookmarks();
        
        // Save immediately that user opened this book (for reading history)
        await saveReadingProgress();

        hideLoading();

    } catch (error) {
        console.error('Error loading book:', error);
        showError(error.message);
    }
}

// Update book information in UI
function updateBookInfo() {
    const titleElement = document.getElementById('bookTitle');
    const authorElement = document.getElementById('bookAuthor');
    const downloadBtn = document.querySelector('[onclick="downloadBook()"]');

    if (titleElement) titleElement.textContent = currentBook.title;
    if (authorElement) authorElement.textContent = 'By: ' + (currentBook.author || 'Unknown');
    
    // Hide download button if content_type is 'book', show for 'module' and 'lesson'
    if (downloadBtn) {
        const contentType = currentBook.content_type || 'book';
        if (contentType === 'book') {
            downloadBtn.style.display = 'none';
        } else {
            downloadBtn.style.display = 'inline-flex';
        }
    }
}

// Load PDF book
async function loadPDF(filePath) {
    const pdfViewer = document.getElementById('pdfViewer');
    const epubViewer = document.getElementById('epubViewer');

    pdfViewer.style.display = 'block';
    epubViewer.style.display = 'none';

    try {
        // Load PDF using PDF.js
        const loadingTask = pdfjsLib.getDocument(filePath);
        pdfDoc = await loadingTask.promise;

        totalPages = pdfDoc.numPages;

        // Update page controls
        updatePageControls();

        // Render first page
        await renderPage(currentPage);

    } catch (error) {
        console.error('Error loading PDF:', error);
        throw new Error('Failed to load PDF file');
    }
}

// Load EPUB book (placeholder for future implementation)
async function loadEPUB(filePath) {
    const pdfViewer = document.getElementById('pdfViewer');
    const epubViewer = document.getElementById('epubViewer');

    pdfViewer.style.display = 'none';
    epubViewer.style.display = 'block';

    // For now, show a message that EPUB reading is not yet implemented
    epubViewer.innerHTML = `
        <div class="epub-placeholder">
            <i class="fas fa-book-open fa-3x"></i>
            <h3>EPUB Reader</h3>
            <p>EPUB reading functionality is coming soon!</p>
            <p>This book is available for download instead.</p>
            <button onclick="downloadBook()" class="btn btn-primary">Download EPUB</button>
        </div>
    `;

    totalPages = 1; // Placeholder
    updatePageControls();
}

// Render PDF page
async function renderPage(pageNum) {
    try {
        const page = await pdfDoc.getPage(pageNum);
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        const viewport = page.getViewport({ scale: scale });

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderContext = {
            canvasContext: context,
            viewport: viewport
        };

        await page.render(renderContext).promise;

        // Clear previous content and add new canvas
        const pdfViewer = document.getElementById('pdfViewer');
        pdfViewer.innerHTML = '';
        pdfViewer.appendChild(canvas);

        // Update progress
        updateReadingProgress();

    } catch (error) {
        console.error('Error rendering page:', error);
        showError('Failed to render page');
    }
}

// Update page controls
function updatePageControls() {
    const currentPageInput = document.getElementById('currentPageInput');
    const totalPagesElement = document.getElementById('totalPages');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (currentPageInput) currentPageInput.value = currentPage;
    if (totalPagesElement) totalPagesElement.textContent = totalPages;

    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
}

// Navigation functions
function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
        updatePageControls();
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        renderPage(currentPage);
        updatePageControls();
    }
}

function goToPage(pageNum) {
    pageNum = parseInt(pageNum);
    if (pageNum >= 1 && pageNum <= totalPages) {
        currentPage = pageNum;
        renderPage(currentPage);
        updatePageControls();
    }
}

// Zoom functions
function zoomIn() {
    scale = Math.min(scale + 0.25, 3);
    updateZoomControls();
    renderPage(currentPage);
}

function zoomOut() {
    scale = Math.max(scale - 0.25, 0.5);
    updateZoomControls();
    renderPage(currentPage);
}

function setZoom(value) {
    scale = parseFloat(value);
    updateZoomControls();
    renderPage(currentPage);
}

function updateZoomControls() {
    const zoomSelect = document.getElementById('zoomSelect');
    if (zoomSelect) {
        zoomSelect.value = scale;
    }
}

// Initialize navigation
function initializeNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const currentPageInput = document.getElementById('currentPageInput');

    if (prevBtn) prevBtn.addEventListener('click', prevPage);
    if (nextBtn) nextBtn.addEventListener('click', nextPage);
    if (currentPageInput) currentPageInput.addEventListener('change', (e) => goToPage(e.target.value));
}

// Initialize zoom controls
function initializeZoom() {
    const zoomInBtn = document.querySelector('[onclick="zoomIn()"]');
    const zoomOutBtn = document.querySelector('[onclick="zoomOut()"]');
    const zoomSelect = document.getElementById('zoomSelect');

    if (zoomInBtn) zoomInBtn.addEventListener('click', zoomIn);
    if (zoomOutBtn) zoomOutBtn.addEventListener('click', zoomOut);
    if (zoomSelect) zoomSelect.addEventListener('change', (e) => setZoom(e.target.value));
}

// Initialize bookmarks
function initializeBookmarks() {
    const bookmarkBtn = document.getElementById('bookmarkBtn');
    const addBookmarkBtn = document.getElementById('addBookmarkBtn');

    if (bookmarkBtn) bookmarkBtn.addEventListener('click', toggleBookmarks);
    if (addBookmarkBtn) addBookmarkBtn.addEventListener('click', addBookmark);
}

// Initialize settings
function initializeSettings() {
    const settingsBtn = document.getElementById('settingsBtn');
    const tocBtn = document.getElementById('tocBtn');

    if (settingsBtn) settingsBtn.addEventListener('click', toggleSettings);
    if (tocBtn) tocBtn.addEventListener('click', toggleTOC);
}

// Keyboard shortcuts
function handleKeyboard(e) {
    // Prevent default browser behavior for our shortcuts
    switch (e.key) {
        case 'ArrowLeft':
            e.preventDefault();
            prevPage();
            break;
        case 'ArrowRight':
            e.preventDefault();
            nextPage();
            break;
        case '+':
        case '=':
            e.preventDefault();
            zoomIn();
            break;
        case '-':
            e.preventDefault();
            zoomOut();
            break;
        case 'b':
        case 'B':
            e.preventDefault();
            toggleBookmark();
            break;
        case 'Escape':
            e.preventDefault();
            closeAllPanels();
            break;
    }
}

// Toggle functions
function toggleFullscreen() {
    const readerContent = document.querySelector('.reader-content');

    if (!document.fullscreenElement) {
        readerContent.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

function toggleBookmark() {
    const bookmarksPanel = document.getElementById('bookmarksPanel');
    const bookmarkBtn = document.getElementById('bookmarkBtn');

    if (bookmarksPanel.style.display === 'block') {
        bookmarksPanel.style.display = 'none';
        bookmarkBtn.classList.remove('active');
    } else {
        closeAllPanels();
        bookmarksPanel.style.display = 'block';
        bookmarkBtn.classList.add('active');
    }
}

function toggleTOC() {
    const tocSidebar = document.getElementById('tocSidebar');
    const tocBtn = document.getElementById('tocBtn');

    if (tocSidebar.style.display === 'block') {
        tocSidebar.style.display = 'none';
        tocBtn.classList.remove('active');
    } else {
        closeAllPanels();
        tocSidebar.style.display = 'block';
        tocBtn.classList.add('active');
    }
}

function toggleSettings() {
    const settingsPanel = document.getElementById('settingsPanel');
    const settingsBtn = document.getElementById('settingsBtn');

    if (settingsPanel.style.display === 'block') {
        settingsPanel.style.display = 'none';
        settingsBtn.classList.remove('active');
    } else {
        closeAllPanels();
        settingsPanel.style.display = 'block';
        settingsBtn.classList.add('active');
    }
}

function closeAllPanels() {
    const panels = ['tocSidebar', 'settingsPanel', 'bookmarksPanel'];
    const buttons = ['tocBtn', 'settingsBtn', 'bookmarkBtn'];

    panels.forEach(panelId => {
        const panel = document.getElementById(panelId);
        if (panel) panel.style.display = 'none';
    });

    buttons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) btn.classList.remove('active');
    });
}

// Reading progress functions
async function loadReadingProgress() {
    if (!currentBook) return;

    try {
        const response = await fetch('api/ebooks.php?action=get_progress', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ebook_id: currentBook.ebook_id })
        });

        const result = await response.json();

        if (result.success && result.progress) {
            currentPage = result.progress.last_page || 1;
            readingProgress = result.progress.progress_percentage || 0;
            updatePageControls();
            renderPage(currentPage);
        }
    } catch (error) {
        console.error('Error loading reading progress:', error);
    }
}

async function saveReadingProgress() {
    if (!currentBook || !autoSaveEnabled) return;

    const progress = {
        ebook_id: currentBook.ebook_id,
        page: currentPage,
        total_pages: totalPages,
        progress: readingProgress
    };

    try {
        await fetch('api/ebooks.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(progress)
        });
    } catch (error) {
        console.error('Error saving reading progress:', error);
    }
}

function updateReadingProgress() {
    if (totalPages > 0) {
        readingProgress = (currentPage / totalPages) * 100;
        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = readingProgress + '%';
        }
    }
}

// Bookmark functions
async function loadBookmarks() {
    if (!currentBook) return;

    try {
        const response = await fetch('api/ebooks.php?action=get_bookmarks', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ebook_id: currentBook.ebook_id })
        });

        const result = await response.json();

        if (result.success) {
            renderBookmarks(result.bookmarks);
        }
    } catch (error) {
        console.error('Error loading bookmarks:', error);
    }
}

function renderBookmarks(bookmarks) {
    const container = document.getElementById('bookmarksList');
    if (!container) return;

    container.innerHTML = '';

    if (!bookmarks || bookmarks.length === 0) {
        container.innerHTML = '<div class="no-bookmarks">No bookmarks yet</div>';
        return;
    }

    bookmarks.forEach(bookmark => {
        const bookmarkItem = document.createElement('div');
        bookmarkItem.className = 'bookmark-item';
        bookmarkItem.onclick = () => goToPage(bookmark.page_number);

        const date = new Date(bookmark.created_at).toLocaleDateString();

        bookmarkItem.innerHTML = `
            <div class="bookmark-info">
                <span class="bookmark-page">Page ${bookmark.page_number}</span>
                <span class="bookmark-date">${date}</span>
            </div>
            ${bookmark.note ? `<div class="bookmark-note">${bookmark.note}</div>` : ''}
            <button onclick="deleteBookmark(${bookmark.bookmark_id}); event.stopPropagation();" class="btn btn-sm btn-danger">
                <i class="fas fa-trash"></i>
            </button>
        `;

        container.appendChild(bookmarkItem);
    });
}

async function addBookmark() {
    const note = prompt('Add a note for this bookmark (optional):');

    if (note === null) return; // User cancelled

    try {
        const response = await fetch('api/ebooks.php?action=add_bookmark', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ebook_id: currentBook.ebook_id,
                page_number: currentPage,
                note: note
            })
        });

        const result = await response.json();

        if (result.success) {
            await loadBookmarks(); // Reload bookmarks
            showMessage('Bookmark added successfully', 'success');
        } else {
            showMessage('Failed to add bookmark', 'error');
        }
    } catch (error) {
        console.error('Error adding bookmark:', error);
        showMessage('Error adding bookmark', 'error');
    }
}

async function deleteBookmark(bookmarkId) {
    if (!confirm('Delete this bookmark?')) return;

    try {
        const response = await fetch('api/ebooks.php?action=delete_bookmark', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bookmark_id: bookmarkId })
        });

        const result = await response.json();

        if (result.success) {
            await loadBookmarks(); // Reload bookmarks
            showMessage('Bookmark deleted', 'success');
        } else {
            showMessage('Failed to delete bookmark', 'error');
        }
    } catch (error) {
        console.error('Error deleting bookmark:', error);
        showMessage('Error deleting bookmark', 'error');
    }
}

// Utility functions
function goBack() {
    window.history.back();
}

async function downloadBook() {
    if (!currentBook) return;
    
    // Check if download is allowed
    const contentType = currentBook.content_type || 'book';
    if (contentType === 'book') {
        showMessage('Download is not available for this content', 'error');
        return;
    }

    try {
        // Get file extension from file_path
        const filePath = currentBook.file_path || '';
        const fileExt = filePath.split('.').pop().toLowerCase() || 'pdf';
        
        // Use direct link for download
        const downloadUrl = `api/ebooks.php?action=download_book&id=${currentBook.ebook_id}`;
        
        // Create a link and click it
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = currentBook.title.replace(/[^a-zA-Z0-9\-\_\.]/g, '_') + '.' + fileExt;
        a.target = '_blank';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        showMessage('Download started', 'success');
    } catch (error) {
        console.error('Download error:', error);
        showMessage('Download failed', 'error');
    }
}

// UI feedback functions
function showLoading(message = 'Loading...') {
    const overlay = document.getElementById('loadingOverlay');
    const text = overlay.querySelector('p');

    if (text) text.textContent = message;
    if (overlay) overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = 'none';
}

function showError(message) {
    const modal = document.getElementById('errorModal');
    const messageElement = document.getElementById('errorMessage');

    if (messageElement) messageElement.textContent = message;
    if (modal) modal.style.display = 'block';
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    if (modal) modal.style.display = 'none';
}

function showMessage(message, type = 'info') {
    // Create a temporary message element
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`;
    messageElement.textContent = message;
    messageElement.style.position = 'fixed';
    messageElement.style.top = '20px';
    messageElement.style.right = '20px';
    messageElement.style.zIndex = '1001';
    messageElement.style.maxWidth = '300px';

    document.body.appendChild(messageElement);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (messageElement.parentNode) {
            messageElement.parentNode.removeChild(messageElement);
        }
    }, 3000);
}

// Settings functions
function changeTheme(theme) {
    const body = document.body;

    // Remove existing theme classes
    body.classList.remove('light-theme', 'dark-theme', 'sepia-theme');

    // Add new theme class
    body.classList.add(`${theme}-theme`);

    // Save preference
    localStorage.setItem('reader-theme', theme);
}

function changeFontSize(size) {
    const pdfViewer = document.getElementById('pdfViewer');

    // Remove existing font size classes
    pdfViewer.classList.remove('font-small', 'font-medium', 'font-large', 'font-xlarge');

    // Add new font size class
    pdfViewer.classList.add(`font-${size}`);

    // Save preference
    localStorage.setItem('reader-font-size', size);
}

function toggleNightMode(enabled) {
    // This would affect the PDF rendering - for now just save preference
    localStorage.setItem('reader-night-mode', enabled);
}

function toggleAutoSave(enabled) {
    autoSaveEnabled = enabled;
    localStorage.setItem('reader-auto-save', enabled);
}

// Load saved preferences on startup
function loadPreferences() {
    const theme = localStorage.getItem('reader-theme') || 'light';
    const fontSize = localStorage.getItem('reader-font-size') || 'medium';
    const nightMode = localStorage.getItem('reader-night-mode') === 'true';
    const autoSave = localStorage.getItem('reader-auto-save') !== 'false'; // Default to true

    changeTheme(theme);
    changeFontSize(fontSize);
    toggleNightMode(nightMode);
    toggleAutoSave(autoSave);

    // Update UI elements
    const themeSelect = document.getElementById('themeSelect');
    const fontSizeSelect = document.getElementById('fontSizeSelect');
    const nightModeCheckbox = document.getElementById('nightMode');
    const autoSaveCheckbox = document.getElementById('autoSaveProgress');

    if (themeSelect) themeSelect.value = theme;
    if (fontSizeSelect) fontSizeSelect.value = fontSize;
    if (nightModeCheckbox) nightModeCheckbox.checked = nightMode;
    if (autoSaveCheckbox) autoSaveCheckbox.checked = autoSave;
}

// Call load preferences after DOM is ready
document.addEventListener('DOMContentLoaded', loadPreferences);

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

// Save book for offline reading
async function saveForOffline() {
    if (!currentBook) {
        showMessage('No book loaded', 'error');
        return;
    }
    
    const offlineBtn = document.getElementById('offlineBtn');
    const filePath = currentBook.file_path || '';
    
    if (!filePath) {
        showMessage('Book file not available', 'error');
        return;
    }
    
    const bookUrl = window.location.origin + '/e-library/web/uploads/books/' + filePath;
    
    // Check if Service Worker is available
    if (!('serviceWorker' in navigator)) {
        showMessage('Offline reading not supported in this browser', 'error');
        return;
    }
    
    try {
        // Update button to show saving
        offlineBtn.disabled = true;
        offlineBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="offline-text">Saving...</span>';
        
        // Send message to Service Worker to cache the book
        const registration = await navigator.serviceWorker.ready;
        registration.active.postMessage({
            action: 'cacheBook',
            bookUrl: bookUrl,
            bookId: currentBook.ebook_id
        });
        
        // Also cache book metadata in localStorage
        saveBookMetadataOffline(currentBook);
        
        // Show success after a short delay (actual caching happens async)
        setTimeout(() => {
            updateOfflineButton(true);
            showMessage('Book saved for offline reading!', 'success');
        }, 1500);
        
    } catch (error) {
        console.error('Failed to save for offline:', error);
        showMessage('Failed to save for offline', 'error');
        updateOfflineButton(false);
    }
}

// Update offline button state
function updateOfflineButton(isCached) {
    const offlineBtn = document.getElementById('offlineBtn');
    if (!offlineBtn) return;
    
    offlineBtn.disabled = false;
    
    if (isCached) {
        offlineBtn.innerHTML = '<i class="fas fa-check-circle"></i> <span class="offline-text">Saved Offline</span>';
        offlineBtn.classList.remove('btn-success');
        offlineBtn.classList.add('btn-secondary');
    } else {
        offlineBtn.innerHTML = '<i class="fas fa-cloud-download-alt"></i> <span class="offline-text">Save Offline</span>';
        offlineBtn.classList.remove('btn-secondary');
        offlineBtn.classList.add('btn-success');
    }
}

// Save book metadata to localStorage for offline access
function saveBookMetadataOffline(book) {
    try {
        let offlineBooks = JSON.parse(localStorage.getItem('offlineBooks') || '{}');
        offlineBooks[book.ebook_id] = {
            ebook_id: book.ebook_id,
            title: book.title,
            author: book.author,
            category: book.category,
            file_path: book.file_path,
            cover_image: book.cover_image,
            saved_at: new Date().toISOString()
        };
        localStorage.setItem('offlineBooks', JSON.stringify(offlineBooks));
    } catch (error) {
        console.error('Failed to save book metadata:', error);
    }
}

// Check if current book is cached
async function checkIfBookCached() {
    if (!currentBook || !currentBook.file_path) return;
    
    try {
        // Check localStorage first
        const offlineBooks = JSON.parse(localStorage.getItem('offlineBooks') || '{}');
        if (offlineBooks[currentBook.ebook_id]) {
            updateOfflineButton(true);
            return;
        }
        
        // Also check with Service Worker
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            const messageChannel = new MessageChannel();
            messageChannel.port1.onmessage = (event) => {
                const cachedUrls = event.data;
                const bookUrl = '/e-library/web/uploads/books/' + currentBook.file_path;
                if (cachedUrls.some(url => url.includes(bookUrl))) {
                    updateOfflineButton(true);
                }
            };
            
            navigator.serviceWorker.controller.postMessage(
                { action: 'getCachedBooks' },
                [messageChannel.port2]
            );
        }
    } catch (error) {
        console.error('Error checking cache:', error);
    }
}

// Initialize offline check when book loads
document.addEventListener('DOMContentLoaded', () => {
    // Check if book is cached after a short delay
    setTimeout(checkIfBookCached, 2000);
});
