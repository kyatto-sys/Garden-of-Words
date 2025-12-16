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