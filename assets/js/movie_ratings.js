document.addEventListener('DOMContentLoaded', () => {
    const reviewListEl = document.getElementById('review-list');
    const avgDisplay = document.getElementById('average-rating');
    const form = document.getElementById('review-form');
    
    if (!reviewListEl) return;
    
    const movieId = reviewListEl.dataset.movieId;
    if (!movieId) {
        console.error('No movie ID found');
        return;
    }

    const starElements = document.querySelectorAll('.star-rating .star');
    const stars = Array.from(starElements);
    
    if (stars && stars.length > 0) {
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                document.getElementById('selected-rating').value = rating;
                
                stars.forEach(s => {
                    if (parseInt(s.dataset.rating) <= rating) {
                        s.classList.add('selected');
                        s.textContent = '★';
                    } else {
                        s.classList.remove('selected');
                        s.textContent = '☆';
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach(s => {
                    if (parseInt(s.dataset.rating) <= rating) {
                        s.textContent = '★';
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const selectedRating = document.getElementById('selected-rating').value;
                stars.forEach(s => {
                    if (!s.classList.contains('selected')) {
                        s.textContent = '☆';
                    }
                });
            });
        });
    }

    function loadReviews() {
        if (!reviewListEl) return;
        
        reviewListEl.innerHTML = '<div class="loading-spinner">Loading reviews...</div>';
        
        fetch(`/atlas/movies/movie_reviews.php?movie_id=${movieId}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('Review data received:', data);
                
                if (!data || !data.success) {
                    throw new Error(data?.message || 'Failed to load reviews');
                }
                
                reviewListEl.innerHTML = '';
                
                if (avgDisplay) {
                    const avg = data.average_rating || 'N/A';
                    const count = data.count || 0;
                    avgDisplay.innerHTML = `Atlas User Rating: <strong>${avg}</strong>/5 <span class="review-count">(${count} ${count === 1 ? 'review' : 'reviews'})</span>`;
                }
                
                if (!data.reviews || !Array.isArray(data.reviews) || data.reviews.length === 0) {
                    reviewListEl.innerHTML = '<p class="no-reviews">No reviews yet. Be the first to review this movie!</p>';
                    return;
                }
                
                const fragment = document.createDocumentFragment();
                
                data.reviews.forEach(review => {
                    if (!review) return;
                    
                    const reviewEl = document.createElement('div');
                    reviewEl.className = 'review-item';
                    
                    try {
                        const reviewDate = new Date(review.created_at);
                        const formattedDate = reviewDate.toLocaleDateString('en-US', {
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric'
                        });
                        
                        const rating = parseInt(review.rating);
                        const starsHtml = Array.from({length: 5}, (_, i) => {
                            const isFilled = i < rating;
                            return `<span class="star ${isFilled ? 'filled' : ''}">${isFilled ? '★' : '☆'}</span>`;
                        }).join('');
                        
                        reviewEl.innerHTML = `
                            <div class="review-header">
                                <div class="reviewer">${review.username || 'Anonymous'}</div>
                                <div class="review-date">${formattedDate}</div>
                            </div>
                            <div class="review-rating">${starsHtml}</div>
                            <div class="review-text">${(review.review || '').replace(/\n/g, '<br>')}</div>
                            ${review.is_own_review ? `
                                <div class="review-actions">
                                    <button class="delete-review" data-movie-id="${movieId}">Delete Review</button>
                                </div>
                            ` : ''}
                        `;
                        
                        if (review.is_own_review) {
                            const deleteBtn = reviewEl.querySelector('.delete-review');
                            deleteBtn.addEventListener('click', function(e) {
                                e.preventDefault();
                                fetch('/atlas/movies/movie_reviews.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `action=delete&movie_id=${movieId}`
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        reviewEl.remove();
                                        notifications.show('Review deleted successfully', 'success');
                                        loadReviews();
                                        
                                        if (form) {
                                            form.reset();
                                            const starElements = document.querySelectorAll('.star-rating .star');
                                            Array.from(starElements).forEach(star => {
                                                star.classList.remove('selected');
                                                star.textContent = '☆';
                                            });
                                            document.getElementById('selected-rating').value = '';
                                        }
                                    } else {
                                        notifications.show(data.message || 'Failed to delete review', 'error');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error deleting review:', err);
                                    notifications.show('An error occurred while trying to delete the review', 'error');
                                });
                            });
                        }
                        
                        fragment.appendChild(reviewEl);
                    } catch (err) {
                        console.error('Error creating review element:', err);
                    }
                });
                
                reviewListEl.appendChild(fragment);
            })
            .catch(err => {
                console.error('Failed to load reviews:', err);
                reviewListEl.innerHTML = `<p class="error">Failed to load reviews: ${err.message}</p>`;
                notifications.show('Failed to load reviews', 'error');
            });
    }
    
    loadReviews();

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const rating = document.getElementById('selected-rating').value;
            const comment = form.querySelector('textarea[name="comment"]').value;
            
            if (!rating || !comment.trim()) {
                notifications.show('Please provide both a rating and a review', 'error');
                return;
            }
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            fetch('/atlas/movies/movie_reviews.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&movie_id=${movieId}&rating=${rating}&comment=${encodeURIComponent(comment)}`
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to submit review');
                }
                
                form.reset();
                const starElements = document.querySelectorAll('.star-rating .star');
                Array.from(starElements).forEach(star => {
                    star.classList.remove('selected');
                    star.textContent = '☆';
                });
                document.getElementById('selected-rating').value = '';
                
                loadReviews();
                
                notifications.show('Your review has been submitted!', 'success');
            })
            .catch(err => {
                console.error('Review submission error:', err);
                notifications.show(err.message || 'An error occurred while submitting your review', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});
  