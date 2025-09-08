// Like toggle functionality
function toggleLike(fundId, buttonElement) {
    
    const wasLiked = buttonElement.classList.contains('liked');
    const heartIcon = buttonElement.querySelector('i');
    const countSpan = buttonElement.querySelector('.count');
    
    // Optimistic update
    buttonElement.disabled = true;
    if (wasLiked) {
        buttonElement.classList.remove('liked');
        heartIcon.className = 'far fa-heart';
        countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) - 1);
    } else {
        buttonElement.classList.add('liked');
        heartIcon.className = 'fas fa-heart';
        countSpan.textContent = parseInt(countSpan.textContent) + 1;
    }
    
    fetch('../../shared/ajax/toggle_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fund_id: fundId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update with actual count from server
            countSpan.textContent = data.likes_count;
            showNotification(data.message, 'success');
        } else {
            // Revert optimistic update
            if (wasLiked) {
                buttonElement.classList.add('liked');
                heartIcon.className = 'fas fa-heart';
            } else {
                buttonElement.classList.remove('liked');
                heartIcon.className = 'far fa-heart';
            }
            countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) + (wasLiked ? 1 : -1));
            showNotification(data.error || 'Failed to update like', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert optimistic update
        if (wasLiked) {
            buttonElement.classList.add('liked');
            heartIcon.className = 'fas fa-heart';
        } else {
            buttonElement.classList.remove('liked');
            heartIcon.className = 'far fa-heart';
        }
        countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) + (wasLiked ? 1 : -1));
        showNotification('Network error occurred', 'error');
    })
    .finally(() => {
        buttonElement.disabled = false;
    });
}

// Notification system
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');
    const searchForm = document.getElementById('searchForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const campaignsGrid = document.getElementById('campaignsGrid');
    const paginationContainer = document.getElementById('paginationContainer');
    const resultsCount = document.getElementById('resultsCount');
    const totalCount = document.getElementById('totalCount');
    const pageInfo = document.getElementById('pageInfo');
    const loadingOverlay = document.getElementById('loadingOverlay');

    let currentPage = 1;

    // Initialize from URL parameters
    function initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search') || '';
        const category = urlParams.get('category') || '';
        const sort = urlParams.get('sort') || 'featured';
        const page = parseInt(urlParams.get('page')) || 1;

        searchInput.value = search;
        categoryFilter.value = category;
        sortFilter.value = sort;
        currentPage = page;

        // Only load campaigns via AJAX if we have active filters
        // Otherwise, let the PHP-rendered page show (with featured section)
        if (search || category || sort !== 'featured' || page > 1) {
            loadCampaigns();
        }
    }

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state) {
            searchInput.value = event.state.search || '';
            categoryFilter.value = event.state.category || '';
            sortFilter.value = event.state.sort || 'featured';
            currentPage = event.state.page || 1;
            loadCampaigns();
        }
    });

    // Initialize on page load
    initializeFromURL();

    // Search form submission (Enter key or Search button)
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadCampaigns();
    });

    // Auto-filter on category/sort change
    categoryFilter.addEventListener('change', function() {
        currentPage = 1;
        loadCampaigns();
    });

    sortFilter.addEventListener('change', function() {
        currentPage = 1;
        loadCampaigns();
    });

    // Clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            // Redirect to clean URL to show featured section
            window.location.href = window.location.pathname;
        });
    }

    // Pagination click handler
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-number[data-page], .page-btn[data-page]')) {
            e.preventDefault();
            currentPage = parseInt(e.target.getAttribute('data-page'));
            loadCampaigns();
        }
    });

    function loadCampaigns() {
        const search = searchInput.value.trim();
        const category = categoryFilter.value;
        const sort = sortFilter.value;

        // Build clean params object (exclude empty values)
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (category) params.set('category', category);
        if (sort && sort !== 'featured') params.set('sort', sort);
        if (currentPage > 1) params.set('page', currentPage);

        // Update URL - use clean URL if no params
        const newUrl = params.toString() ? 
            window.location.pathname + '?' + params.toString() : 
            window.location.pathname;
        window.history.pushState({search, category, sort, page: currentPage}, '', newUrl);

        // For AJAX call, include all params even if default
        const ajaxParams = new URLSearchParams({
            search: search,
            category: category,
            sort: sort,
            page: currentPage
        });

        // Show loading
        loadingOverlay.style.display = 'flex';

        fetch('../ajax/browse_campaigns.php?' + ajaxParams.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update campaigns grid
                    campaignsGrid.innerHTML = data.campaignsHtml;

                    // Update pagination
                    paginationContainer.innerHTML = data.paginationHtml;

                    // Update results summary
                    resultsCount.textContent = data.resultsCount;
                    totalCount.textContent = data.totalFunds;
                    
                    if (data.totalPages > 1) {
                        pageInfo.textContent = `Page ${data.currentPage} of ${data.totalPages}`;
                        pageInfo.style.display = 'inline';
                    } else {
                        pageInfo.style.display = 'none';
                    }

                    // Update clear button visibility
                    const hasFilters = search || category || sort !== 'featured';
                    if (clearFiltersBtn) {
                        clearFiltersBtn.style.display = hasFilters ? 'inline-flex' : 'none';
                    }

                    // Log search information for debugging
                    if (data.searchInfo) {
                        console.log('Search Info:', data.searchInfo);
                    }
                    
                    // Log debug information
                    if (data.debug) {
                        console.log('Debug Info:', data.debug);
                    }

                    // Scroll to top of results
                    document.querySelector('.browse-section').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                } else {
                    console.error('Error loading campaigns:', data.error);
                    campaignsGrid.innerHTML = `
                        <div class="no-campaigns">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Error Loading Campaigns</h3>
                            <p>${data.error}</p>
                            <button onclick="loadCampaigns()" class="btn btn-primary">
                                <i class="fas fa-refresh"></i> Try Again
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                campaignsGrid.innerHTML = `
                    <div class="no-campaigns">
                        <i class="fas fa-wifi"></i>
                        <h3>Connection Error</h3>
                        <p>Please check your internet connection and try again.</p>
                        <button onclick="loadCampaigns()" class="btn btn-primary">
                            <i class="fas fa-refresh"></i> Retry
                        </button>
                    </div>
                `;
            })
            .finally(() => {
                // Hide loading
                loadingOverlay.style.display = 'none';
            });
    }

    // Make loadCampaigns available globally for error buttons
    window.loadCampaigns = loadCampaigns;
});