// E-Book Reader JavaScript for E-Library System
// San Roque Elementary School

let currentBook = null;
let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1;
let readingProgress = 0;
let autoFillEnabled = false; // Auto-fill mode
let autoSaveEnabled = true;
let isRendering = false; // Prevent page skip due to rapid clicks

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
        // If offline and book is saved locally, allow reading without session
        const offlineBooks = JSON.parse(localStorage.getItem('offlineBooks') || '{}');
        if (!offlineBooks[bookId]) {
            window.location.href = 'login.php';
            return;
        }
        console.log('Offline mode: reading cached book without session');
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

    // Orientation change re-render
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            if (pdfDoc) {
                renderPage(currentPage);
            }
        }, 300);
    });

    window.addEventListener('resize', function() {
        if (document.fullscreenElement && pdfDoc) {
            renderPage(currentPage);
        }
    });
}

// Load book details and content
async function loadBook(bookId) {
    try {
        // Show loading
        showLoading('Loading book...');

        // Initialize PDF.js worker FIRST before any PDF operations
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            console.log('PDF.js worker initialized');
        } else {
            console.warn('PDF.js library not found, will retry...');
            // Wait a bit and try again
            await new Promise(resolve => setTimeout(resolve, 500));
            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }
        }

        // Load book details - try API first, fall back to localStorage for offline
        let response;
        try {
            response = await fetch(`api/ebooks.php?action=get_book&id=${bookId}`);
        } catch (e) {
            response = null;
        }

        if (response && response.ok) {
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Failed to load book');
            }
            currentBook = result.book;
        } else {
            const offlineBooks = JSON.parse(localStorage.getItem('offlineBooks') || '{}');
            const offlineBook = offlineBooks[bookId];
            if (offlineBook) {
                currentBook = offlineBook;
                showMessage('You are viewing a saved offline copy of this book.', 'info');
            } else {
                throw new Error('Book not available offline. Please connect to the internet and try again.');
            }
        }

        // Update UI
        updateBookInfo();

        // Determine file type from extension
        const filePath = currentBook.file_path || '';
        
        if (!filePath) {
            throw new Error('No book file available for this book');
        }
        
        const fileExt = filePath.split('.').pop().toLowerCase();
        console.log('Loading book file:', filePath, 'Extension:', fileExt);

        // Load book content
        if (fileExt === 'pdf') {
            await loadPDF('uploads/books/' + filePath);
        } else if (fileExt === 'epub') {
            await loadEPUB('uploads/books/' + filePath);
        } else if (['mp4', 'webm', 'ogg'].includes(fileExt)) {
            await loadVideo('uploads/books/' + filePath);
        } else if (['ppt', 'pptx'].includes(fileExt)) {
            await loadOffice('uploads/books/' + filePath);
        } else {
            // Try loading as PDF by default
            await loadPDF('uploads/books/' + filePath);
        }

        // Load reading progress
        await loadReadingProgress();

        // Load bookmarks
        await loadBookmarks();

        // Save immediately that user opened this book (for reading history)
        await saveReadingProgress();

    } catch (error) {
        console.error('Error loading book:', error);
        showError(error.message);
    } finally {
        hideLoading();
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
    const videoViewer = document.getElementById('videoViewer');
    const officeViewer = document.getElementById('officeViewer');

    console.log('Loading PDF from:', filePath);

    pdfViewer.style.display = 'block';
    epubViewer.style.display = 'none';
    videoViewer.style.display = 'none';
    officeViewer.style.display = 'none';

    try {
        if (typeof pdfjsLib === 'undefined') {
            throw new Error('PDF.js library not loaded. Please refresh the page.');
        }

        // Try loading with relative path first
        let pdfData = null;
        
        try {
            const loadingTask = pdfjsLib.getDocument(filePath);
            pdfDoc = await loadingTask.promise;
        } catch (loadError) {
            // If relative path fails, try with absolute path
            console.log('Relative path failed, trying absolute path...');
            const absolutePath = window.location.origin + '/e-library/web/' + filePath;
            console.log('Trying absolute path:', absolutePath);
            
            const loadingTask = pdfjsLib.getDocument(absolutePath);
            pdfDoc = await loadingTask.promise;
        }

        totalPages = pdfDoc.numPages;
        console.log('PDF loaded successfully, total pages:', totalPages);

        // Update page controls
        updatePageControls();

        // Render first page
        await renderPage(currentPage);

    } catch (error) {
        console.error('Error loading PDF:', error);
        throw new Error('Failed to load PDF file. The file may be corrupted or not accessible. Error: ' + error.message);
    }
}

// Load EPUB book (placeholder for future implementation)
async function loadEPUB(filePath) {
    const pdfViewer = document.getElementById('pdfViewer');
    const epubViewer = document.getElementById('epubViewer');
    const videoViewer = document.getElementById('videoViewer');
    const officeViewer = document.getElementById('officeViewer');

    pdfViewer.style.display = 'none';
    videoViewer.style.display = 'none';
    officeViewer.style.display = 'none';
    epubViewer.style.display = 'block';

    epubViewer.innerHTML = `
        <div class="empty-state" style="text-align: center; padding: 3rem;">
            <i class="fas fa-book-open" style="font-size: 4rem; color: var(--primary-blue); opacity: 0.5; margin-bottom: 1rem;"></i>
            <h3>EPUB Viewer</h3>
            <p>EPUB support is coming soon! You can download the file to read it on your device.</p>
            <button onclick="downloadBook()" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-download"></i> Download EPUB
            </button>
        </div>
    `;
    
    totalPages = 1;
    currentPage = 1;
    updatePageControls();
}

// Load Video content
async function loadVideo(filePath) {
    const pdfViewer = document.getElementById('pdfViewer');
    const epubViewer = document.getElementById('epubViewer');
    const videoViewer = document.getElementById('videoViewer');
    const officeViewer = document.getElementById('officeViewer');
    const video = document.getElementById('mainVideo');

    pdfViewer.style.display = 'none';
    epubViewer.style.display = 'none';
    officeViewer.style.display = 'none';
    videoViewer.style.display = 'block';

    const zoomControls = document.querySelector('.zoom-controls');
    const pagination = document.querySelector('.reader-navigation');
    if (zoomControls) zoomControls.style.display = 'none';
    if (pagination) pagination.style.display = 'none';

    // Pre-check the video source URL (skip when offline — cached video still works)
    var videoUrl = 'api/serve.php?id=' + currentBook.ebook_id;
    if (navigator.onLine) {
        try {
            var headResp = await fetch(videoUrl, { method: 'HEAD' });
            if (!headResp.ok) {
                console.warn('Video HEAD check returned ' + headResp.status + ', trying anyway');
            } else {
                var contentType = headResp.headers.get('Content-Type');
                if (contentType && !contentType.includes('video') && !contentType.includes('octet')) {
                    console.warn('Unexpected Content-Type:', contentType);
                }
            }
        } catch (e) {
            console.warn('Video HEAD check failed (' + e.message + '), trying anyway');
        }
    }

    video.src = videoUrl;
    video.onerror = function() {
        var errCode = video.error ? video.error.code : '?';
        var errMsg = video.error ? video.error.message : 'Unknown error';
        fetch(videoUrl).then(function(r) {
            showError('Video failed (code ' + errCode + '). Server returned HTTP ' + r.status + '. Try downloading the file instead.');
        }).catch(function() {
            showError('Video failed (code ' + errCode + ': ' + errMsg + '). Network may be blocking the connection.');
        });
    };
    video.load();
    
    totalPages = 1;
    currentPage = 1;
    updatePageControls();
}

// Load Office (PPT) content
async function loadOffice(filePath) {
    const pdfViewer = document.getElementById('pdfViewer');
    const epubViewer = document.getElementById('epubViewer');
    const videoViewer = document.getElementById('videoViewer');
    const officeViewer = document.getElementById('officeViewer');

    pdfViewer.style.display = 'none';
    epubViewer.style.display = 'none';
    videoViewer.style.display = 'none';
    officeViewer.style.display = 'block';

    // We can try to use Google Docs Viewer for PPTs if they are publicly accessible
    // For local development, we show the download fallback
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const iframe = document.getElementById('officeIframe');
    const unsupported = document.querySelector('.unsupported-viewer');

    if (!isLocal) {
        const fullUrl = window.location.origin + '/e-library/web/' + filePath;
        iframe.src = `https://docs.google.com/viewer?url=${encodeURIComponent(fullUrl)}&embedded=true`;
        iframe.style.display = 'block';
        if (unsupported) unsupported.style.display = 'none';
    } else {
        if (iframe) iframe.style.display = 'none';
        if (unsupported) unsupported.style.display = 'block';
    }
    
    totalPages = 1;
    currentPage = 1;
    updatePageControls();
}

// Render PDF page
async function renderPage(pageNum) {
    try {
        const page = await pdfDoc.getPage(pageNum);
        
        // Auto-fit scale on first render if scale is 1
        if (pageNum === currentPage && scale === 1) {
            var isFullscreen = document.fullscreenElement || document.body.classList.contains('reader-fullscreen-active');
            var containerWidth;
            var containerHeight;

            if (isFullscreen) {
                containerWidth = window.innerWidth;
                containerHeight = window.innerHeight;
            } else {
                var readingArea = document.querySelector('.reading-area');
                containerWidth = readingArea ? readingArea.clientWidth : 0;
                containerHeight = readingArea ? readingArea.clientHeight : 0;
            }

            var viewport1 = page.getViewport({ scale: 1 });
            
            if (containerWidth > 0 && viewport1.width > 0) {
                scale = (containerWidth - 40) / viewport1.width;
                // Also constrain by height
                if (containerHeight > 0 && viewport1.height > 0) {
                    var heightScale = (containerHeight - 40) / viewport1.height;
                    if (heightScale < scale) scale = heightScale;
                }
            } else {
                scale = 1;
            }
            
            if (scale > 2) scale = 2; // Maximum scale
            if (scale < 0.2) scale = 0.2; // Minimum scale
            updateZoomControls();
        }

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        const dpr = window.devicePixelRatio || 1;
        const viewport = page.getViewport({ scale: scale });

        canvas.width = viewport.width * dpr;
        canvas.height = viewport.height * dpr;
        canvas.style.width = viewport.width + 'px';
        canvas.style.height = viewport.height + 'px';
        context.scale(dpr, dpr);

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
    const readerPrevBtn = document.getElementById('readerPrevBtn');
    const readerNextBtn = document.getElementById('readerNextBtn');
    if (currentPageInput) currentPageInput.value = currentPage;
    if (totalPagesElement) totalPagesElement.textContent = totalPages;

    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    if (readerPrevBtn) readerPrevBtn.disabled = currentPage <= 1;
    if (readerNextBtn) readerNextBtn.disabled = currentPage >= totalPages;
}

// Navigation functions
function prevPage() {
    if (isRendering) return; // Prevent rapid clicks
    if (currentPage > 1) {
        isRendering = true;
        currentPage--;
        renderPage(currentPage).then(() => {
            updatePageControls();
            isRendering = false;
        }).catch(() => {
            isRendering = false;
        });
    }
}

function nextPage() {
    if (isRendering) return; // Prevent rapid clicks
    if (currentPage < totalPages) {
        isRendering = true;
        currentPage++;
        renderPage(currentPage).then(() => {
            updatePageControls();
            isRendering = false;
        }).catch(() => {
            isRendering = false;
        });
    }
}

function goToPage(pageNum) {
    if (isRendering) return; // Prevent rapid clicks
    pageNum = parseInt(pageNum);
    if (pageNum >= 1 && pageNum <= totalPages) {
        isRendering = true;
        currentPage = pageNum;
        renderPage(currentPage).then(() => {
            updatePageControls();
            isRendering = false;
        }).catch(() => {
            isRendering = false;
        });
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

    // Add event listeners for navigation buttons
    const readerPrevBtn = document.getElementById('readerPrevBtn');
    const readerNextBtn = document.getElementById('readerNextBtn');

    if (readerPrevBtn) readerPrevBtn.addEventListener('click', prevPage);
    if (readerNextBtn) readerNextBtn.addEventListener('click', nextPage);

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
    const body = document.body;
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    if (!body.classList.contains('reader-fullscreen-active')) {
        body.classList.add('reader-fullscreen-active');
        readerContent.classList.add('auto-fill');
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
        }
    } else {
        body.classList.remove('reader-fullscreen-active');
        readerContent.classList.remove('auto-fill');
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        }
    }
}

// Check auto-fill mode
function checkAutoFillMode() {
    const urlParams = new URLSearchParams(window.location.search);
    const autoFill = urlParams.get('autoFill');

    if (autoFill === 'true') {
        enableAutoFill();
    }
}

// Enable auto-fill mode
function enableAutoFill() {
    const readerContent = document.querySelector('.reader-content');
    const body = document.body;
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    if (readerContent) {
        body.classList.add('reader-fullscreen-active');
        readerContent.classList.add('auto-fill');
        autoFillEnabled = true;

        // Update fullscreen button icon
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
        }

    }
}

// Disable auto-fill mode
function disableAutoFill() {
    const readerContent = document.querySelector('.reader-content');
    const body = document.body;
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    if (readerContent) {
        body.classList.remove('reader-fullscreen-active');
        readerContent.classList.remove('auto-fill');
        autoFillEnabled = false;

        // Update fullscreen button icon
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        }
    }
}

// Toggle auto-fill mode
function toggleAutoFill() {
    if (autoFillEnabled) {
        disableAutoFill();
    } else {
        enableAutoFill();
    }
}

// Add auto-fill button to toolbar
function addAutoFillButton() {
    const toolbarRight = document.querySelector('.toolbar-right');

    if (toolbarRight) {
        const autoFillBtn = document.createElement('button');
        autoFillBtn.id = 'autoFillBtn';
        autoFillBtn.className = 'btn btn-icon';
        autoFillBtn.title = 'Auto-fill Mode';
        autoFillBtn.innerHTML = '<i class="fas fa-expand"></i>';
        autoFillBtn.onclick = toggleAutoFill;

        toolbarRight.insertBefore(autoFillBtn, toolbarRight.firstChild);
    }
}

// Add auto-fill button when DOM is loaded
document.addEventListener('DOMContentLoaded', addAutoFillButton);

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

// Toggle bookmarks panel
function toggleBookmarks() {
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
            if (pdfDoc) {
                renderPage(currentPage);
            }
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
    if (navigator.onLine) {
        window.location.href = 'index.php';
    } else {
        window.location.href = 'offline.html';
    }
}

async function downloadBook() {
    if (!currentBook) return;

    const contentType = currentBook.content_type || 'book';
    if (contentType === 'book') {
        showMessage('Download is not available for this content', 'error');
        return;
    }

    try {
        // Log download asynchronously (don't wait for response)
        fetch(`api/ebooks.php?action=log_download&id=${currentBook.ebook_id}`).catch(() => {});

        // Use serve.php with download=1 for chunked streaming (no PHP timeout)
        const downloadUrl = `api/serve.php?id=${currentBook.ebook_id}&download=1`;

        const a = document.createElement('a');
        a.href = downloadUrl;
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

    const basePath = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
    const fileExt = filePath.split('.').pop().toLowerCase();
    const isVideo = ['mp4', 'webm', 'ogg'].includes(fileExt);
    const bookUrl = isVideo
        ? basePath + 'api/serve.php?id=' + currentBook.ebook_id
        : basePath + 'uploads/books/' + filePath;

    // Check if Service Worker is available
    if (!('serviceWorker' in navigator)) {
        showMessage('Offline reading not supported in this browser', 'error');
        return;
    }

    try {
        // Check storage quota if API available
        if (navigator.storage && navigator.storage.estimate) {
            const estimate = await navigator.storage.estimate();
            // If less than 50MB available, warn the user
            if (estimate.quota && estimate.usage && (estimate.quota - estimate.usage < 50 * 1024 * 1024)) {
                showMessage('Warning: Device storage is almost full', 'error');
                return;
            }
        }

        // Update button to show saving
        offlineBtn.disabled = true;
        offlineBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="offline-text">Saving...</span>';

        // Save metadata to localStorage immediately (ensures offline page shows it)
        saveBookMetadataOffline(currentBook);

        // Cache book metadata API response for offline access
        try {
            const apiResponse = await fetch(`api/ebooks.php?action=get_book&id=${currentBook.ebook_id}`);
            if (apiResponse.ok) {
                const apiCache = await caches.open('elibrary-books-v4');
                const apiRequest = new Request(`api/ebooks.php?action=get_book&id=${currentBook.ebook_id}`);
                await apiCache.put(apiRequest, apiResponse);
            }
        } catch (e) {
            console.warn('Failed to cache book metadata for offline:', e);
        }

        // Send message to Service Worker to cache the book
        const registration = await navigator.serviceWorker.ready;
        if (registration.active) {
            registration.active.postMessage({
                action: 'cacheBook',
                bookUrl: bookUrl,
                bookId: currentBook.ebook_id
            });

            // Timeout to prevent hanging on "Saving..." (e.g., large video files)
            const SW_TIMEOUT = 60000;
            window.__offlineTimeout = setTimeout(() => {
                window.__offlineTimeout = null;
                updateOfflineButton(false);
                showMessage('Offline save timed out. The file may be too large.', 'error');
            }, SW_TIMEOUT);
        } else {
            // Fallback if worker not active yet
            const response = await fetch(bookUrl);
            if (response.ok) {
                const cache = await caches.open('elibrary-books-v4');
                await cache.put(bookUrl, response);
                saveBookMetadataOffline(currentBook);
                updateOfflineButton(true);
                showMessage('Book saved for offline reading!', 'success');
            } else {
                throw new Error('Failed to fetch book for caching');
            }
        }
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

// Check if current book is saved offline (localStorage only)
async function checkIfBookCached() {
    if (!currentBook || !currentBook.file_path) return;

    try {
        const offlineBooks = JSON.parse(localStorage.getItem('offlineBooks') || '{}');
        if (offlineBooks[currentBook.ebook_id]) {
            updateOfflineButton(true);
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