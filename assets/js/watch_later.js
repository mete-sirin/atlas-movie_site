document.addEventListener('DOMContentLoaded', () => {
    const watchLaterBtn = document.getElementById('watch-later-btn');
    if (!watchLaterBtn) return;

    watchLaterBtn.addEventListener('click', async () => {
        const movieId = watchLaterBtn.dataset.movieId;
        const isInWatchlist = watchLaterBtn.classList.contains('in-watchlist');
        
        try {
            const response = await fetch('../movies/handle_watch_later.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    movie_id: movieId,
                    action: isInWatchlist ? 'remove' : 'add'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                watchLaterBtn.classList.toggle('in-watchlist');
                const btnText = watchLaterBtn.querySelector('.btn-text');
                btnText.textContent = isInWatchlist ? 'Add to Watch Later' : 'Remove from Watch Later';
            } else {
                alert(data.message || 'Failed to update watch later status');
            }
        } catch (error) {
            console.error('Error updating watch later status:', error);
            alert('Failed to update watch later status');
        }
    });
}); 