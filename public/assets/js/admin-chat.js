// Facebook-style Chat Widget for Admin
class ChatWidget {
    constructor() {
        this.openChats = new Map();
        this.maxChats = 3;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.unreadCount = 0;
        this.init();
    }

    init() {
        this.createWidget();
        this.loadChats();
        this.startPolling();
    }

    createWidget() {
        const widget = document.createElement('div');
        widget.className = 'chat-widget';
        widget.innerHTML = `
            <button class="chat-list-toggle" id="chatListToggle">
                💬
                <span class="unread-badge" id="unreadBadge" style="display: none;">0</span>
            </button>
            <div class="chat-list-panel" id="chatListPanel">
                <div class="chat-list-header">Pesan</div>
                <div class="chat-list-body" id="chatListBody">
                    <div class="no-chats-message">Memuat...</div>
                </div>
            </div>
        `;
        document.body.appendChild(widget);

        document.getElementById('chatListToggle').addEventListener('click', () => {
            this.toggleChatList();
        });

        // Close chat list when clicking outside
        document.addEventListener('click', (e) => {
            const panel = document.getElementById('chatListPanel');
            const toggle = document.getElementById('chatListToggle');
            if (!panel.contains(e.target) && !toggle.contains(e.target)) {
                panel.classList.remove('active');
            }
        });
    }

    toggleChatList() {
        const panel = document.getElementById('chatListPanel');
        panel.classList.toggle('active');
        if (panel.classList.contains('active')) {
            this.loadChats();
        }
    }

    async loadChats() {
        try {
            const response = await fetch('api/chat.php?action=get_chats');
            const data = await response.json();
            
            if (data.success) {
                this.renderChatList(data.chats);
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Failed to load chats:', error);
        }
    }

    renderChatList(chats) {
        const listBody = document.getElementById('chatListBody');
        
        if (chats.length === 0) {
            listBody.innerHTML = '<div class="no-chats-message">Tidak ada pesan</div>';
            return;
        }

        listBody.innerHTML = chats.map(chat => `
            <div class="chat-list-item ${chat.unread_count > 0 ? 'unread' : ''}" 
                 data-report-id="${chat.id}" 
                 onclick="chatWidget.openChat(${chat.id})">
                <div class="chat-item-avatar">${chat.tracking_code.substring(0, 2)}</div>
                <div class="chat-item-content">
                    <div class="chat-item-title">
                        ${chat.tracking_code}
                        <span style="font-size: 11px; color: #6b7280;">${chat.category_name}</span>
                    </div>
                    <div class="chat-item-preview">${this.escapeHtml(chat.last_message || 'Tidak ada pesan')}</div>
                </div>
                ${chat.unread_count > 0 ? `<div class="chat-item-unread">${chat.unread_count}</div>` : ''}
            </div>
        `).join('');
    }

    async openChat(reportId) {
        // Close chat list
        document.getElementById('chatListPanel').classList.remove('active');

        // Check if already open
        if (this.openChats.has(reportId)) {
            const chatBox = document.getElementById(`chat-${reportId}`);
            chatBox.classList.remove('minimized');
            return;
        }

        // Check max chats
        if (this.openChats.size >= this.maxChats) {
            alert('Maksimal 3 chat dapat dibuka bersamaan');
            return;
        }

        try {
            const response = await fetch(`api/chat.php?action=get_messages&report_id=${reportId}`);
            const data = await response.json();
            
            if (data.success) {
                this.createChatBox(reportId, data.report, data.messages);
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
        }
    }

    createChatBox(reportId, report, messages) {
        const chatBox = document.createElement('div');
        chatBox.className = 'chat-box';
        chatBox.id = `chat-${reportId}`;
        
        const urgencyColors = {
            'normal': '#059669',
            'high': '#d97706',
            'emergency': '#dc2626'
        };

        chatBox.innerHTML = `
            <div class="chat-box-header" onclick="chatWidget.toggleMinimize(${reportId})">
                <div class="chat-box-title">
                    <span>${report.tracking_code}</span>
                    <span class="chat-box-subtitle">${report.category_name}</span>
                </div>
                <div class="chat-box-actions">
                    <button class="chat-box-action" onclick="event.stopPropagation(); chatWidget.closeChat(${reportId})" title="Tutup">×</button>
                </div>
            </div>
            <div class="chat-box-report-info">
                <strong>Status:</strong> ${this.getStatusLabel(report.status)} | 
                <strong style="color: ${urgencyColors[report.urgency_level]}">Urgensi:</strong> ${this.getUrgencyLabel(report.urgency_level)}
            </div>
            <div class="chat-box-messages" id="messages-${reportId}">
                ${this.renderMessages(messages)}
            </div>
            <div class="chat-box-input">
                <textarea id="input-${reportId}" placeholder="Ketik pesan..." rows="1"></textarea>
                <button class="chat-box-send" onclick="chatWidget.sendMessage(${reportId})">➤</button>
            </div>
        `;

        document.querySelector('.chat-widget').insertBefore(
            chatBox, 
            document.getElementById('chatListToggle')
        );

        this.openChats.set(reportId, { report, messages });

        // Auto-resize textarea
        const textarea = document.getElementById(`input-${reportId}`);
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });

        // Send on Enter (Shift+Enter for new line)
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage(reportId);
            }
        });

        // Scroll to bottom
        this.scrollToBottom(reportId);
    }

    renderMessages(messages) {
        if (messages.length === 0) {
            return '<div style="text-align: center; color: #9ca3af; padding: 20px;">Belum ada pesan</div>';
        }

        return messages.map(msg => `
            <div class="chat-message ${msg.sender}">
                <div class="chat-message-bubble">
                    ${this.escapeHtml(msg.message).replace(/\n/g, '<br>')}
                </div>
                <div class="chat-message-time">
                    ${this.formatTime(msg.created_at)}
                    ${msg.sender === 'admin' ? ' - ' + this.escapeHtml(msg.admin_name || 'Admin') : ''}
                </div>
            </div>
        `).join('');
    }

    async sendMessage(reportId) {
        const input = document.getElementById(`input-${reportId}`);
        const message = input.value.trim();

        if (!message) return;

        const sendBtn = input.nextElementSibling;
        sendBtn.disabled = true;
        input.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('report_id', reportId);
            formData.append('message', message);
            formData.append('csrf_token', this.csrfToken);

            const response = await fetch('api/chat.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Add message to chat
                const chatData = this.openChats.get(reportId);
                chatData.messages.push(data.message);
                
                // Re-render messages
                const messagesContainer = document.getElementById(`messages-${reportId}`);
                messagesContainer.innerHTML = this.renderMessages(chatData.messages);
                
                // Clear input
                input.value = '';
                input.style.height = 'auto';
                
                // Scroll to bottom
                this.scrollToBottom(reportId);
            } else {
                alert('Gagal mengirim pesan');
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            alert('Gagal mengirim pesan');
        } finally {
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    toggleMinimize(reportId) {
        const chatBox = document.getElementById(`chat-${reportId}`);
        chatBox.classList.toggle('minimized');
    }

    closeChat(reportId) {
        const chatBox = document.getElementById(`chat-${reportId}`);
        chatBox.remove();
        this.openChats.delete(reportId);
    }

    scrollToBottom(reportId) {
        setTimeout(() => {
            const messagesContainer = document.getElementById(`messages-${reportId}`);
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }, 100);
    }

    async updateUnreadCount() {
        try {
            const response = await fetch('api/chat.php?action=get_unread_count');
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.unread_count;
                const badge = document.getElementById('unreadBadge');
                
                if (this.unreadCount > 0) {
                    badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Failed to update unread count:', error);
        }
    }

    startPolling() {
        // Poll for new messages every 2 seconds for real-time feel
        setInterval(() => {
            this.updateUnreadCount();
            
            // Refresh open chats
            this.openChats.forEach((chatData, reportId) => {
                this.refreshChat(reportId);
            });
        }, 2000);
    }

    async refreshChat(reportId) {
        try {
            const response = await fetch(`api/chat.php?action=get_messages&report_id=${reportId}`);
            const data = await response.json();
            
            if (data.success) {
                const chatData = this.openChats.get(reportId);
                const oldLength = chatData.messages.length;
                chatData.messages = data.messages;
                
                // Only update if new messages
                if (data.messages.length > oldLength) {
                    const messagesContainer = document.getElementById(`messages-${reportId}`);
                    messagesContainer.innerHTML = this.renderMessages(data.messages);
                    this.scrollToBottom(reportId);
                }
            }
        } catch (error) {
            console.error('Failed to refresh chat:', error);
        }
    }

    getStatusLabel(status) {
        const labels = {
            'new': 'Baru',
            'reviewed': 'Ditinjau',
            'escalated': 'Eskalasi',
            'resolved': 'Selesai',
            'closed': 'Ditutup'
        };
        return labels[status] || status;
    }

    getUrgencyLabel(urgency) {
        const labels = {
            'normal': 'Normal',
            'high': 'Tinggi',
            'emergency': 'Darurat'
        };
        return labels[urgency] || urgency;
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        // Less than 1 minute
        if (diff < 60000) {
            return 'Baru saja';
        }
        
        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} menit lalu`;
        }
        
        // Less than 24 hours
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} jam lalu`;
        }
        
        // Format as date
        return date.toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat widget when DOM is ready
let chatWidget;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        chatWidget = new ChatWidget();
    });
} else {
    chatWidget = new ChatWidget();
}
