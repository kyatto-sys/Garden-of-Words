// Connector function for API calls
async function apiConnector(endpoint, method = 'GET', data = null) {
    const url = `/garden-of-words/api/${endpoint}`;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API Connector Error:', error);
        throw error;
    }
}

// Example usage functions
async function likeBook(bookId) {
    try {
        const result = await apiConnector('like.php', 'POST', { book_id: bookId });
        console.log('Liked book:', result);
        // Update UI here
    } catch (error) {
        console.error('Error liking book:', error);
    }
}

async function dislikeBook(bookId) {
    try {
        const result = await apiConnector('dislike.php', 'POST', { book_id: bookId });
        console.log('Disliked book:', result);
        // Update UI here
    } catch (error) {
        console.error('Error disliking book:', error);
    }
}




    // Handle like/dislike reactions
document.addEventListener('DOMContentLoaded', function() {
const reactionButtons = document.querySelectorAll('.reaction-btn');

reactionButtons.forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const manuscriptId = this.dataset.manuscriptId;
        const action = this.dataset.action;
        
        // Disable button temporarily to prevent double clicks
        this.disabled = true;
        
        try {
            const response = await fetch('api/react.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    manuscript_id: manuscriptId,
                    action: action
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update counts
                const card = this.closest('.manuscript-card');
                const likeBtn = card.querySelector('.like-btn');
                const dislikeBtn = card.querySelector('.dislike-btn');
                
                likeBtn.querySelector('.count').textContent = data.like_count;
                dislikeBtn.querySelector('.count').textContent = data.dislike_count;
                
                // Update active states
                likeBtn.classList.remove('active');
                dislikeBtn.classList.remove('active');
                
                if (data.user_reaction === 'like') {
                    likeBtn.classList.add('active');
                } else if (data.user_reaction === 'dislike') {
                    dislikeBtn.classList.add('active');
                }
                
                // Add a little animation feedback
                this.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            } else {
                console.error('Error:', data.error);
                alert('Failed to update reaction. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        } finally {
            // Re-enable button
            this.disabled = false;
        }
    });
});
});

// Optional: Add hover tooltips
document.addEventListener('DOMContentLoaded', function() {
const reactionButtons = document.querySelectorAll('.reaction-btn');

reactionButtons.forEach(btn => {
    const action = btn.dataset.action;
    const tooltip = action === 'like' ? 'Like this manuscript' : 'Dislike this manuscript';
    btn.setAttribute('title', tooltip);
});
});

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuToggle && navLinks) {
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenuToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
        });

        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            });
        });
    }
});