class NotificationSystem {
    constructor() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        document.body.appendChild(this.container);
    }

    show(message, type = 'success', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icon = document.createElement('span');
        icon.className = 'notification-icon';
        icon.innerHTML = this.getIcon(type);
        
        const content = document.createElement('div');
        content.className = 'notification-content';
        
        const title = document.createElement('div');
        title.className = 'notification-title';
        title.textContent = this.getTitle(type);
        
        const messageEl = document.createElement('div');
        messageEl.className = 'notification-message';
        messageEl.textContent = message;
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'notification-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = () => this.hide(notification);
        
        content.appendChild(title);
        content.appendChild(messageEl);
        
        notification.appendChild(icon);
        notification.appendChild(content);
        notification.appendChild(closeBtn);
        
        this.container.appendChild(notification);
        
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        });
        
        if (duration > 0) {
            setTimeout(() => this.hide(notification), duration);
        }
        
        return notification;
    }
    
    hide(notification) {
        notification.classList.add('hiding');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300); 
    }
    
    getIcon(type) {
        switch(type) {
            case 'success':
                return '✓';
            case 'error':
                return '✕';
            case 'info':
                return 'ℹ';
            default:
                return '!';
        }
    }
    
    getTitle(type) {
        switch(type) {
            case 'success':
                return 'Success';
            case 'error':
                return 'Error';
            case 'info':
                return 'Information';
            default:
                return 'Notification';
        }
    }
}

window.notifications = new NotificationSystem(); 