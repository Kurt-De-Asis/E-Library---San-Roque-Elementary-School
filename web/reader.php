<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4A90E2">
    <title>E-Book Reader - San Roque Elementary School E-Library</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- PDF.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
</head>
<body class="reader-page">
    <!-- Reader Header -->
    <header class="reader-header">
        <div class="header-container">
            <div class="header-left">
                <button onclick="goBack()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Library
                </button>
                <div class="book-info">
                    <h2 id="bookTitle">Loading...</h2>
                    <p id="bookAuthor">By: Loading...</p>
                </div>
            </div>
            <div class="header-right">
                <div class="reader-controls">
                    <button onclick="saveForOffline()" id="offlineBtn" class="btn btn-success" title="Save for Offline Reading">
                        <i class="fas fa-cloud-download-alt"></i> <span class="offline-text">Save Offline</span>
                    </button>
                    <button onclick="toggleFullscreen()" id="fullscreenBtn" class="btn btn-icon">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button onclick="downloadBook()" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Reader Toolbar -->
    <div class="reader-toolbar">
        <div class="toolbar-container">
            <div class="toolbar-left">
                <button onclick="prevPage()" id="prevBtn" class="btn btn-icon" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="page-info">
                    Page <input type="number" id="currentPageInput" min="1" value="1" onchange="goToPage(this.value)">
                    of <span id="totalPages">0</span>
                </span>
                <button onclick="nextPage()" id="nextBtn" class="btn btn-icon">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="toolbar-center">
                <div class="zoom-controls">
                    <button onclick="zoomOut()" class="btn btn-icon">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <select id="zoomSelect" onchange="setZoom(this.value)">
                        <option value="0.5">50%</option>
                        <option value="0.75">75%</option>
                        <option value="1" selected>100%</option>
                        <option value="1.25">125%</option>
                        <option value="1.5">150%</option>
                        <option value="2">200%</option>
                    </select>
                    <button onclick="zoomIn()" class="btn btn-icon">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>
            </div>

            <div class="toolbar-right">
                <button onclick="toggleBookmark()" id="bookmarkBtn" class="btn btn-icon">
                    <i class="fas fa-bookmark"></i>
                </button>
                <button onclick="toggleTOC()" id="tocBtn" class="btn btn-icon">
                    <i class="fas fa-list"></i>
                </button>
                <button onclick="toggleSettings()" id="settingsBtn" class="btn btn-icon">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Reader Content -->
    <main class="reader-content">
        <div class="content-container">
            <!-- Table of Contents Sidebar -->
            <div id="tocSidebar" class="toc-sidebar">
                <h3>Table of Contents</h3>
                <div id="tocContent" class="toc-content">
                    <div class="loading">Loading table of contents...</div>
                </div>
            </div>

            <!-- Main Reading Area -->
            <div class="reading-area">
                <div id="pdfViewer" class="pdf-viewer">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading book...</p>
                    </div>
                </div>

                <!-- EPUB Reader (hidden by default, shown if EPUB) -->
                <div id="epubViewer" class="epub-viewer" style="display: none;">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading EPUB reader...</p>
                    </div>
                </div>
            </div>

            <!-- Settings Panel -->
            <div id="settingsPanel" class="settings-panel">
                <h4>Reading Settings</h4>
                <div class="setting-group">
                    <label for="themeSelect">Theme:</label>
                    <select id="themeSelect" onchange="changeTheme(this.value)">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="sepia">Sepia</option>
                    </select>
                </div>

                <div class="setting-group">
                    <label for="fontSizeSelect">Font Size:</label>
                    <select id="fontSizeSelect" onchange="changeFontSize(this.value)">
                        <option value="small">Small</option>
                        <option value="medium" selected>Medium</option>
                        <option value="large">Large</option>
                        <option value="xlarge">Extra Large</option>
                    </select>
                </div>

                <div class="setting-group">
                    <label>
                        <input type="checkbox" id="autoSaveProgress" checked onchange="toggleAutoSave(this.checked)">
                        Auto-save reading progress
                    </label>
                </div>

                <div class="setting-group">
                    <label>
                        <input type="checkbox" id="nightMode" onchange="toggleNightMode(this.checked)">
                        Night mode (reduces blue light)
                    </label>
                </div>
            </div>
        </div>
    </main>

    <!-- Reading Progress Bar -->
    <div class="reading-progress">
        <div id="progressBar" class="progress-bar"></div>
    </div>

    <!-- Bookmarks Panel -->
    <div id="bookmarksPanel" class="bookmarks-panel">
        <h4>Your Bookmarks</h4>
        <div id="bookmarksList" class="bookmarks-list">
            <div class="loading">Loading bookmarks...</div>
        </div>
        <button onclick="addBookmark()" class="btn btn-success">
            <i class="fas fa-plus"></i> Add Bookmark
        </button>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeErrorModal()">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Error</h3>
            <p id="errorMessage">An error occurred while loading the book.</p>
            <div class="modal-actions">
                <button onclick="closeErrorModal()" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <script src="js/reader.js"></script>
    <script>
        // Register Service Worker for offline support
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(registration => {
                    console.log('Service Worker registered');
                })
                .catch(error => {
                    console.log('Service Worker registration failed:', error);
                });

            // Listen for messages from Service Worker
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data.type === 'bookCached') {
                    updateOfflineButton(true);
                    showMessage('Book saved for offline reading!', 'success');
                }
            });
        }
    </script>
</body>
</html>
