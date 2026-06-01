<?php
require_once 'api/config.php';

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check offline reading setting
$conn = getDBConnection();
$enable_offline_reading = 1;
if ($conn) {
    $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'enable_offline_reading'");
    if ($result && $row = $result->fetch_assoc()) {
        $enable_offline_reading = (int)$row['setting_value'];
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#228B22">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="SRES E-Library Reader">
    <title>E-Book Reader - San Roque Elementary School E-Library</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/logos/school-logo.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="shortcut icon" href="assets/logos/school-logo.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="apple-touch-icon" sizes="192x192" href="assets/icons/icon-192x192.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="apple-touch-startup-image" href="assets/icons/apple-touch-icon.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="stylesheet" href="css/style.css?v=<?php echo CACHE_BUSTER; ?>">
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
                    <?php if ($enable_offline_reading): ?>
                    <button onclick="saveForOffline()" id="offlineBtn" class="btn btn-success" title="Save for Offline Reading">
                        <i class="fas fa-cloud-download-alt"></i> <span class="offline-text">Save Offline</span>
                    </button>
                    <?php endif; ?>
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

    <!-- Navigation Buttons -->
    <div id="readerNav" class="reader-navigation">
        <button id="readerPrevBtn" class="nav-btn nav-btn-left" title="Previous Page">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button id="readerNextBtn" class="nav-btn nav-btn-right" title="Next Page">
            <i class="fas fa-chevron-right"></i>
        </button>
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

                <!-- Video Player (hidden by default) -->
                <div id="videoViewer" class="video-viewer" style="display: none;">
                    <video id="mainVideo" controls controlsList="nodownload">
                        Your browser does not support the video tag.
                    </video>
                </div>

                <!-- PPT / Office Viewer (hidden by default) -->
                <div id="officeViewer" class="office-viewer" style="display: none;">
                    <div id="officeContent" class="office-content">
                        <div class="unsupported-viewer">
                            <i class="fas fa-file-powerpoint" style="font-size: 5rem; color: #d24726; margin-bottom: 1rem;"></i>
                            <h3>Presentation Loaded</h3>
                            <p>This file format is best viewed by downloading and opening with Microsoft PowerPoint.</p>
                            <button onclick="downloadBook()" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-download"></i> Download to View
                            </button>
                        </div>
                        <iframe id="officeIframe" src="" width="100%" height="600px" frameborder="0" style="display: none;"></iframe>
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

    <!-- Exit Fullscreen Button -->
    <button id="exitFullscreenBtn" class="exit-fullscreen-btn" onclick="toggleFullscreen()" title="Exit Fullscreen">
        <i class="fas fa-times"></i>
    </button>
</main>

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

    <script src="js/reader.js?v=<?php echo CACHE_BUSTER; ?>"></script>
    <script>
        // Register Service Worker for offline support
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js?v=<?php echo APP_VERSION; ?>')
                .then(registration => {
                    if (registration.waiting && navigator.serviceWorker.controller) {
                        registration.waiting.postMessage({ action: 'skipWaiting' });
                        window.location.reload();
                    }
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                window.location.reload();
                            }
                        });
                    });
                })
                .catch(error => {
                    console.log('Service Worker registration failed:', error);
                });

            // Listen for messages from Service Worker
            navigator.serviceWorker.addEventListener('message', event => {
                // Clear offline save timeout if set
                if (window.__offlineTimeout) {
                    clearTimeout(window.__offlineTimeout);
                    window.__offlineTimeout = null;
                }
                if (event.data.type === 'bookCached') {
                    updateOfflineButton(true);
                    if (window.currentBook) {
                        saveBookMetadataOffline(window.currentBook);
                    }
                    showMessage('Book saved for offline reading!', 'success');
                } else if (event.data.type === 'bookCacheFailed') {
                    updateOfflineButton(false);
                    showMessage('Failed to save book for offline reading. Please try again.', 'error');
                }
            });
        }

        // Add offline/online listeners
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);

        function updateNetworkStatus() {
            const isOffline = !navigator.onLine;
            let banner = document.getElementById('offlineBanner');
            
            if (isOffline) {
                if (!banner) {
                    banner = document.createElement('div');
                    banner.id = 'offlineBanner';
                    banner.className = 'offline-banner';
                    banner.innerHTML = '<i class="fas fa-wifi-slash"></i> You are currently offline. Viewing cached book.';
                    document.body.prepend(banner);
                }
            } else {
                if (banner) {
                    banner.remove();
                }
            }
        }

        // Run once on load
        document.addEventListener('DOMContentLoaded', updateNetworkStatus);
    </script>
</body>
</html>
