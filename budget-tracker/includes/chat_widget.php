<!-- Global Chat Widget -->
<div id="aiChatWidget" class="card shadow-lg border-0" style="position: fixed; bottom: 20px; right: 20px; width: 350px; height: 500px; z-index: 1050; display: none; transition: all 0.3s ease;">
    <!-- Header -->
    <div class="card-header text-white d-flex justify-content-between align-items-center py-3" 
         style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border-radius: 0.5rem 0.5rem 0 0;">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-robot"></i>
            <h6 class="mb-0 fw-bold">AI Budget Assistant</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="toggleChatWidget()"></button>
    </div>

    <!-- Body -->
    <div class="card-body p-3 overflow-auto bg-light" id="widgetChatContainer" style="height: calc(100% - 130px);">
        <div class="d-flex flex-column gap-3" id="widgetChatMessages">
            <div class="text-center text-muted mt-5" id="widgetChatPlaceholder">
                <i class="fas fa-comments fa-3x mb-3 text-secondary opacity-50"></i>
                <p class="small">How can I help you with your budget today?</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer bg-white border-top p-3">
        <form id="widgetChatForm" class="d-flex gap-2">
            <input type="text" id="widgetUserMessage" class="form-control form-control-sm" placeholder="Type your question..." required autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none;">
                <i class="fas fa-paper-plane text-white" style="font-size: 0.8rem;"></i>
            </button>
        </form>
    </div>
</div>

<script>
// Toggle Widget Visibility
function toggleChatWidget() {
    const widget = document.getElementById('aiChatWidget');
    if (widget.style.display === 'none') {
        widget.style.display = 'block';
        // Check if we need to load initial history (optional, or just show empty/welcome)
        // loadWidgetHistory(); 
        setTimeout(() => document.getElementById('widgetUserMessage').focus(), 100);
    } else {
        widget.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('widgetChatForm');
    const userMessageInput = document.getElementById('widgetUserMessage');
    const chatContainer = document.getElementById('widgetChatContainer');
    const chatMessagesDiv = document.getElementById('widgetChatMessages');
    const chatPlaceholder = document.getElementById('widgetChatPlaceholder');

    // Load history on init if you want persistence across page loads
    // fetchChatHistory(); 

    function fetchChatHistory() {
        if(!chatMessagesDiv) return;
        fetch('api/chat_history.php?mode=widget')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    if(chatPlaceholder) chatPlaceholder.remove();
                    // Clear existing to avoid dupes if called multiple times
                    chatMessagesDiv.innerHTML = ''; 
                    data.data.forEach(msg => {
                        appendMessageToWidget(msg.message, msg.sender, false);
                    });
                    scrollToBottom();
                }
            })
            .catch(err => console.error('Error fetching chat:', err));
    }
    
    // Call it immediately to populate if there's history
    fetchChatHistory();

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = userMessageInput.value.trim();
        if (!message) return;

        appendMessageToWidget(message, 'user');
        userMessageInput.value = '';
        
        const btn = chatForm.querySelector('button');
        const icon = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-white" style="font-size: 0.8rem;"></i>';

        fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        })
        .then(res => res.json())
        .then(result => {
            if (result.success && result.data) {
                appendMessageToWidget(result.data.message, 'bot');
                
                // --- Dispatch AI Action Event ---
                if (result.data.action_performed) {
                    const actionDetail = {
                        actionType: result.data.action_type
                    };
                    
                    // Dispatch to current window
                    window.dispatchEvent(new CustomEvent('aiActionCompleted', { detail: actionDetail }));
                    
                    // Update localStorage to trigger 'storage' event in other tabs
                    localStorage.setItem('budget_tracker_ai_action', JSON.stringify({
                        type: result.data.action_type,
                        timestamp: new Date().getTime()
                    }));
                }
            } else {
                appendMessageToWidget('Error: ' + result.message, 'bot');
            }
        })
        .catch(err => appendMessageToWidget('System Error', 'bot'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = icon;
            scrollToBottom();
        });
    });

    function appendMessageToWidget(text, sender, animate = true) {
        if(chatPlaceholder) chatPlaceholder.remove();

        const isUser = sender === 'user';
        const align = isUser ? 'align-self-end text-end' : 'align-self-start text-start';
        // Using gradients/colors matching the theme
        const bg = isUser ? 'text-white' : 'bg-white border text-dark';
        const style = isUser ? 'background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);' : '';
        const radius = isUser ? '15px 15px 0 15px' : '15px 15px 15px 0';
        
        const msgDiv = document.createElement('div');
        msgDiv.className = align;
        msgDiv.style.maxWidth = '85%';
        msgDiv.innerHTML = `
            <div class="p-2 px-3 shadow-sm ${bg}" style="border-radius: ${radius}; ${style} font-size: 0.9rem;">
                ${isUser ? text.replace(/\n/g, '<br>') : parseMarkdown(text)}
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.65rem;">
                ${isUser ? 'You' : 'AI'}
            </div>
        `;

        if (animate) {
            msgDiv.style.opacity = '0';
            msgDiv.style.transform = 'translateY(10px)';
            msgDiv.style.transition = 'all 0.3s ease';
        }

        chatMessagesDiv.appendChild(msgDiv);

        if (animate) {
            requestAnimationFrame(() => {
                msgDiv.style.opacity = '1';
                msgDiv.style.transform = 'translateY(0)';
            });
        }
        
        scrollToBottom();
    }

    function parseMarkdown(text) {
        let html = text
            // Escape HTML (basic)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            // Bold (**text**)
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Italic (*text*)
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            // Unordered List (* item) - handle newlines first
            .replace(/^\s*\*\s+(.*)/gm, '<li>$1</li>');
        
        // Wrap lists if any exist
        if (html.includes('<li>')) {
            html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        }

        // Line breaks (convert remaining newlines to <br>, but try not to break lists)
        // Simple approach: just replace \n with <br> if not near list tags? 
        // Or simpler: just replace all \n with <br> and let CSS handle the mess, or better regex.
        // Let's stick to simple <br> for non-list items.
        
        return html.replace(/\n/g, '<br>');
    }


    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
});
</script>
