<!-- Global Chat Widget -->
<div id="aiChatWidget" class="card shadow-lg border-0" style="position: fixed; bottom: 20px; right: 20px; width: 350px; height: 500px; z-index: 1050; display: none; flex-direction: column; transition: all 0.3s ease; overflow: hidden; <?php echo (defined('AI_MAINTENANCE_MODE') && AI_MAINTENANCE_MODE && ($_SESSION['role'] ?? '') !== 'superadmin') ? 'filter: grayscale(0.5);' : ''; ?>">
    <?php if (defined('AI_MAINTENANCE_MODE') && AI_MAINTENANCE_MODE && ($_SESSION['role'] ?? '') !== 'superadmin'): ?>
        <!-- Maintenance Overlay -->
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px;">
            <div class="bg-warning-subtle text-warning p-3 rounded-circle mb-3 shadow-sm">
                <i class="fas fa-tools fa-2x"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Under Maintenance</h5>
            <p class="text-secondary small mb-3">Our AI Assistant is currently undergoing scheduled improvements to serve you better. We'll be back shortly!</p>
            <button onclick="toggleChatWidget()" class="btn btn-sm btn-outline-secondary rounded-pill px-4">Close Hub</button>
        </div>
    <?php endif; ?>


    <!-- Header -->
    <div class="card-header text-white d-flex justify-content-between align-items-center py-3"
        style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border-radius: 0.5rem 0.5rem 0 0;">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-link text-white p-0 me-1" title="View Chat History" onclick="location.href='<?php echo SITE_URL; ?>core/history_log.php'">
                <i class="fas fa-history" style="font-size: 0.9rem;"></i>
            </button>
            <i class="fas fa-robot"></i>
            <h6 class="mb-0 fw-bold">Budget Assistant</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="promptLimitBadge" class="badge rounded-pill bg-white text-primary px-2" style="font-size: 0.65rem; opacity: 0.9; cursor: help;" title="Free daily AI prompts remaining">-- / 10</span>
            <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="toggleChatWidget()"></button>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body p-3 overflow-auto bg-light flex-grow-1" id="widgetChatContainer">
        <div class="d-flex flex-column gap-3" id="widgetChatMessages">
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer bg-white border-top p-3 shadow-sm">
        <form id="widgetChatForm" class="d-flex flex-column gap-2">
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="widgetUserMessage" class="form-control form-control-sm flex-grow-1" placeholder="<?php echo (defined('AI_MAINTENANCE_MODE') && AI_MAINTENANCE_MODE && ($_SESSION['role'] ?? '') !== 'superadmin') ? 'System offline...' : 'Type your question...'; ?>" required autocomplete="off" style="border-radius: 20px;" <?php echo (defined('AI_MAINTENANCE_MODE') && AI_MAINTENANCE_MODE && ($_SESSION['role'] ?? '') !== 'superadmin') ? 'disabled' : ''; ?>>
                <button type="submit" id="widgetSendBtn" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none;" <?php echo (defined('AI_MAINTENANCE_MODE') && AI_MAINTENANCE_MODE && ($_SESSION['role'] ?? '') !== 'superadmin') ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane text-white" style="font-size: 0.85rem;"></i>
                </button>
            </div>
            <div id="aiLimitReminder" class="text-center" style="font-size: 0.65rem; color: #94a3b8;">
                <i class="fas fa-info-circle me-1"></i> Limit: 10 daily prompts. <span id="promptsRemainingText">--</span> remaining.
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle Widget Visibility
    function toggleChatWidget(saveState = true) {
        const widget = document.getElementById('aiChatWidget');
        const fab = document.querySelector('.ai-fab');
        if (widget.style.display === 'none') {
            widget.style.display = 'flex';
            if (fab) fab.style.display = 'none';
            if (saveState) localStorage.setItem('chat_widget_open', 'true');
            // Only focus when input is enabled (not in maintenance mode for non-superadmin)
            setTimeout(() => {
                const inp = document.getElementById('widgetUserMessage');
                if (inp && !inp.disabled) inp.focus();
            }, 100);
        } else {
            widget.style.display = 'none';
            if (fab) fab.style.display = 'flex';
            if (saveState) localStorage.setItem('chat_widget_open', 'false');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Restore state
        if (localStorage.getItem('chat_widget_open') === 'true') {
            toggleChatWidget(false);
        }

        const chatForm = document.getElementById('widgetChatForm');
        const userMessageInput = document.getElementById('widgetUserMessage');
        const chatContainer = document.getElementById('widgetChatContainer');
        const chatMessagesDiv = document.getElementById('widgetChatMessages');
        const chatPlaceholder = document.getElementById('widgetChatPlaceholder');

        // Load history on init if you want persistence across page loads
        let lastActivityTime = parseInt(localStorage.getItem('chat_last_activity') || Date.now());

        function fetchChatHistory() {
            if (!chatMessagesDiv) return;

            const now = Date.now();
            // 5-minute browser-side timeout (300,000 ms)
            if (now - lastActivityTime > 300000) {
                chatMessagesDiv.innerHTML = '';
                localStorage.setItem('chat_last_activity', now);
                lastActivityTime = now;
            }

            fetch('<?php echo SITE_URL; ?>api/history_log.php?mode=widget')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        chatMessagesDiv.innerHTML = '';
                        if (data.data.length > 0) {
                            if (chatPlaceholder) chatPlaceholder.remove();
                            data.data.forEach(pair => {
                                // 1. Render User Message
                                appendMessageToWidget(pair.message, 'user', false);
                                // 2. Render Bot Response
                                if (pair.response) {
                                    appendMessageToWidget(pair.response, 'bot', false);
                                }
                            });
                        }
                        scrollToBottom();
                    }
                })
                .catch(err => console.error('Error fetching chat:', err));
        }

        function updatePromptLimit() {
            const badge = document.getElementById('promptLimitBadge');
            const reminderText = document.getElementById('promptsRemainingText');
            if (!badge || !reminderText) return;

            fetch('<?php echo SITE_URL; ?>api/ai_assistant.php?action=count_prompts')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const count = data.count || 0;
                        const remaining = Math.max(0, 10 - count);
                        badge.textContent = `${remaining} / 10`;
                        reminderText.textContent = remaining;

                        // Visual feedback if limit is close
                        if (remaining <= 2) {
                            badge.classList.remove('text-primary');
                            badge.classList.add('text-danger', 'fw-bold');
                        }

                        // Disable form if limit reached
                        if (remaining === 0 && '<?php echo $_SESSION['role']; ?>' !== 'superadmin') {
                            userMessageInput.disabled = true;
                            userMessageInput.placeholder = "Daily limit reached!";
                            const btn = chatForm.querySelector('button[type="submit"]');
                            if (btn) btn.disabled = true;
                        }
                    }
                })
                .catch(err => console.error('Error fetching prompt count:', err));
        }

        // Call it immediately to populate if there's history
        fetchChatHistory();
        updatePromptLimit();

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
                    } else {
                        let errMsg = result.message || 'Unknown';
                        if (result.debug_info) {
                            errMsg += ' <br><small class="text-muted">(Debug: Key [' + result.debug_info.api_key_hint + '] via Proxy: ' + result.debug_info.proxy_active + ')</small>';
                        }
                        appendMessageToWidget('Error: ' + errMsg, 'bot');
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
                    updatePromptLimit(); // Refresh limit count
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

            // Escape script and harmful HTML but allow some basic formatting
            let html = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

            // 1. Code Blocks (Fenced)
            html = html.replace(/```(\w*)\n([\s\S]*?)```/g, (match, lang, code) => {
                return `<pre class="bg-dark text-light p-2 rounded my-2" style="font-size: 0.8rem; overflow-x: auto;"><code>${code.trim()}</code></pre>`;
            });

            // 2. Inline Code
            html = html.replace(/`([^`]+)`/g, '<code class="bg-light border rounded px-1" style="font-size: 0.85em; color: #d63384;">$1</code>');

            // 3. Headings (H1 - H4)
            html = html.replace(/^# (.*$)/gm, '<h5 class="fw-bold mt-3 mb-2 border-bottom pb-1">$1</h5>');
            html = html.replace(/^## (.*$)/gm, '<h6 class="fw-bold mt-2 mb-1">$1</h6>');
            html = html.replace(/^### (.*$)/gm, '<div class="fw-bold small mb-1">$1</div>');

            // 4. Bold and Italic
            html = html.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

            // 5. Links [text](url)
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-primary fw-bold text-decoration-none hover-underline">$1</a>');

            // 6. Dividers
            html = html.replace(/^---$/gm, '<hr class="my-3 opacity-25">');

            // 7. Split into blocks for lists and paragraphs
            const blocks = html.split(/\n\n+/);
            html = blocks.map(block => {
                // Unordered Lists
                if (block.match(/^\s*[-*•]\s+/m)) {
                    const items = block.trim().split(/\n/).map(line => {
                        const match = line.match(/^\s*[-*•]\s+(.*)/);
                        return match ? `<li class="mb-1">${match[1]}</li>` : line;
                    }).join('');
                    return `<ul class="ps-3 mb-2">${items}</ul>`;
                }
                // Ordered Lists
                if (block.match(/^\s*\d+\.\s+/m)) {
                    const items = block.trim().split(/\n/).map(line => {
                        const match = line.match(/^\s*\d+\.\s+(.*)/);
                        return match ? `<li class="mb-1">${match[1]}</li>` : line;
                    }).join('');
                    return `<ol class="ps-3 mb-2">${items}</ol>`;
                }
                // Standard Paragraph
                return `<p class="mb-2 last-child-mb-0">${block.replace(/\n/g, '<br>')}</p>`;
            }).join('');

            return html;
        }


        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        scrollToBottom();
    });
</script>