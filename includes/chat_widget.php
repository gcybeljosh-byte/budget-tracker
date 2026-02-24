<!-- Global Chat Widget -->
<div id="aiChatWidget" class="card shadow-lg border-0" style="position: fixed; bottom: 20px; right: 20px; width: 350px; height: 500px; z-index: 1050; display: none; flex-direction: column; transition: all 0.3s ease; overflow: hidden;">
    <!-- Header -->
    <div class="card-header text-white d-flex justify-content-between align-items-center py-3"
        style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border-radius: 0.5rem 0.5rem 0 0;">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-link text-white p-0 me-1" title="View Chat History" onclick="location.href='<?php echo SITE_URL; ?>core/chat_history.php'">
                <i class="fas fa-history" style="font-size: 0.9rem;"></i>
            </button>
            <i class="fas fa-robot"></i>
            <h6 class="mb-0 fw-bold">Budget Assistant</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="toggleChatWidget()"></button>
    </div>

    <!-- Body -->
    <div class="card-body p-3 overflow-auto bg-light flex-grow-1" id="widgetChatContainer">
        <div class="d-flex flex-column gap-3" id="widgetChatMessages">
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer bg-white border-top p-3 shadow-sm">
        <form id="widgetChatForm" class="d-flex gap-2 align-items-center">
            <input type="text" id="widgetUserMessage" class="form-control form-control-sm flex-grow-1" placeholder="Type your question..." required autocomplete="off" style="border-radius: 20px;">
            <button type="submit" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none;">
                <i class="fas fa-paper-plane text-white" style="font-size: 0.85rem;"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // Toggle Widget Visibility
    function toggleChatWidget() {
        const widget = document.getElementById('aiChatWidget');
        const fab = document.querySelector('.ai-fab');
        if (widget.style.display === 'none') {
            widget.style.display = 'flex';
            if (fab) fab.style.display = 'none';
            setTimeout(() => document.getElementById('widgetUserMessage').focus(), 100);
        } else {
            widget.style.display = 'none';
            if (fab) fab.style.display = 'flex';
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
        let lastActivityTime = parseInt(localStorage.getItem('chat_last_activity') || Date.now());

        function fetchChatHistory() {
            if (!chatMessagesDiv) return;

            const now = Date.now();
            if (now - lastActivityTime > 600000) {
                chatMessagesDiv.innerHTML = '';
                localStorage.setItem('chat_last_activity', now);
                lastActivityTime = now;
            }

            fetch('<?php echo SITE_URL; ?>api/chat_history.php?mode=widget')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.length > 0) {
                            if (chatPlaceholder) chatPlaceholder.remove();
                            chatMessagesDiv.innerHTML = '';
                            data.data.forEach(msg => {
                                appendMessageToWidget(msg.message, msg.sender, false);
                            });
                        } else {
                            chatMessagesDiv.innerHTML = '';
                        }
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

            lastActivityTime = Date.now();
            localStorage.setItem('chat_last_activity', lastActivityTime);

            const btn = chatForm.querySelector('button');
            const icon = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-white" style="font-size: 0.8rem;"></i>';

            showTypingIndicator();

            fetch('<?php echo SITE_URL; ?>api/ai_assistant.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message
                    })
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error('HTTP ' + res.status + ': ' + text);
                        });
                    }
                    return res.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Invalid JSON: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(result => {
                    hideTypingIndicator();
                    if (result.success && result.data) {
                        appendMessageToWidget(result.data.message, 'bot');

                        // --- Dispatch AI Action Event ---
                        if (result.data.action_performed) {
                            const actionDetail = {
                                actionType: result.data.action_type
                            };

                            // Dispatch to current window
                            window.dispatchEvent(new CustomEvent('aiActionCompleted', {
                                detail: actionDetail
                            }));

                            // Update localStorage to trigger 'storage' event in other tabs
                            localStorage.setItem('budget_tracker_ai_action', JSON.stringify({
                                type: result.data.action_type,
                                timestamp: new Date().getTime()
                            }));
                        }
                    } else {
                        appendMessageToWidget('Error: ' + (result.message || 'Unknown'), 'bot');
                    }
                })
                .catch(err => {
                    hideTypingIndicator();
                    console.error('AI Chat Error:', err);
                    appendMessageToWidget('System Error: ' + err.message, 'bot');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = icon;
                    scrollToBottom();
                });
        });

        function showTypingIndicator() {
            if (document.getElementById('aiTypingIndicator')) return;

            const indicator = document.createElement('div');
            indicator.id = 'aiTypingIndicator';
            indicator.className = 'typing-indicator';
            indicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
            chatMessagesDiv.appendChild(indicator);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('aiTypingIndicator');
            if (indicator) indicator.remove();
        }

        function appendMessageToWidget(text, sender, animate = true) {
            if (chatPlaceholder) chatPlaceholder.remove();

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
                ${isUser ? 'You' : 'Assistant'}
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
            if (!text) return "";

            let html = text
                // Escape HTML
                .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
                // Bold (**text**)
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Italic (*text*)
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                // Dividers
                .replace(/^---$/gm, '<hr class="my-3 opacity-25">');

            // Handle Paragraphs & Lists
            const blocks = html.split(/\n\n+/);
            html = blocks.map(block => {
                // Unordered Lists (- or *)
                if (block.match(/^\s*[-*•]\s+/m)) {
                    return '<ul class="ps-3 mb-2">' + block.split(/\n/).map(line => {
                        const match = line.match(/^\s*[-*•]\s+(.*)/);
                        return match ? `<li>${match[1]}</li>` : line;
                    }).join('') + '</ul>';
                }
                // Ordered Lists (1.)
                if (block.match(/^\s*\d+\.\s+/m)) {
                    return '<ol class="ps-3 mb-2">' + block.split(/\n/).map(line => {
                        const match = line.match(/^\s*\d+\.\s+(.*)/);
                        return match ? `<li>${match[1]}</li>` : line;
                    }).join('') + '</ol>';
                }
                // Standard Paragraph
                return `<p class="mb-2">${block.replace(/\n/g, '<br>')}</p>`;
            }).join('');

            return html;
        }


        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        scrollToBottom();
    });
</script>