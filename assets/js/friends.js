window.sendFriendAction = function (action, targetId) {
    const requestItem = document.querySelector(`.request-item:has(#friend-btn-accept-${targetId})`);
    if (requestItem) requestItem.remove();

    const requestsList = document.querySelector('.requests-list');
    if (requestsList && !requestsList.children.length) {
        const friendRequests = document.querySelector('.friend-requests');
        if (friendRequests) friendRequests.remove();
    }

    fetch('../social/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&friend_id=${targetId}`
    });
};
