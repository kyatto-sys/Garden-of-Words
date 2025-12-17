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

document.querySelectorAll('.reaction-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const manuscriptId = this.dataset.manuscriptId;
                const action = this.dataset.action;
                
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
                        card.querySelector('.like-btn .count').textContent = data.like_count;
                        card.querySelector('.dislike-btn .count').textContent = data.dislike_count;
                        
                        // Update active states
                        card.querySelectorAll('.reaction-btn').forEach(b => b.classList.remove('active'));
                        if (data.user_reaction) {
                            card.querySelector(`.${data.user_reaction}-btn`).classList.add('active');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });