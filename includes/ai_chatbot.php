<!-- AI Chatbot Floating Widget -->
<style>
    .ai-chatbot-launcher {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5, #0ea5e9);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
        z-index: 9999;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .ai-chatbot-launcher:hover {
        transform: scale(1.1) rotate(15deg);
        box-shadow: 0 12px 28px rgba(79, 70, 229, 0.5);
    }
    .ai-chatbot-window {
        position: fixed;
        bottom: 96px;
        right: 24px;
        width: 360px;
        height: 480px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        box-shadow: var(--shadow-lg), 0 12px 40px rgba(0,0,0,0.15);
        z-index: 9999;
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .ai-chatbot-header {
        background: linear-gradient(135deg, #4f46e5, #0ea5e9);
        color: white;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ai-chatbot-messages {
        flex: 1;
        padding: 1rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background: var(--bg-body);
    }
    .chat-msg {
        max-width: 85%;
        padding: 8px 12px;
        border-radius: 14px;
        font-size: 0.85rem;
        line-height: 1.4;
    }
    .chat-msg.bot {
        background: var(--bg-surface);
        color: var(--text-main);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        border: 1px solid var(--border-color);
    }
    .chat-msg.user {
        background: #4f46e5;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .ai-chatbot-input-container {
        display: flex;
        padding: 10px;
        background: var(--bg-surface);
        border-top: 1px solid var(--border-color);
        gap: 8px;
    }
    .ai-chatbot-input {
        flex: 1;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 0.875rem;
        outline: none;
    }
    .ai-chatbot-send {
        background: #4f46e5;
        color: white;
        border: none;
        border-radius: 50%;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }
    .ai-chatbot-send:hover {
        background: #4338ca;
    }
</style>

<div class="ai-chatbot-launcher" onclick="toggleChatbot()" id="aiChatLauncher">
    <i class="fas fa-robot"></i>
</div>

<div class="ai-chatbot-window" id="aiChatWindow">
    <div class="ai-chatbot-header">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-brain text-white"></i>
            <div>
                <h6 class="mb-0 fw-bold text-white">SAS AI Assistant</h6>
                <small class="opacity-75 text-white" style="font-size:0.7rem;">Online &bull; ERP Chatbot</small>
            </div>
        </div>
        <button class="btn-close btn-close-white" onclick="toggleChatbot()"></button>
    </div>
    <div class="ai-chatbot-messages" id="aiChatMessages">
        <div class="chat-msg bot">
            Hello! I am your AI Assistant. Ask me queries like:
            <br>&bull; <em>"What is my attendance?"</em>
            <br>&bull; <em>"Can I miss tomorrow?"</em>
            <br>&bull; <em>"Show low attendance subjects"</em>
        </div>
    </div>
    <div class="ai-chatbot-input-container">
        <input type="text" class="ai-chatbot-input" id="aiChatInput" placeholder="Ask a question..." onkeypress="handleChatKey(event)">
        <button class="ai-chatbot-send" onclick="sendChatMessage()">
            <i class="fas fa-paper-plane" style="font-size: 0.8rem;"></i>
        </button>
    </div>
</div>

<script>
    function toggleChatbot() {
        const win = document.getElementById('aiChatWindow');
        if (win.style.display === 'none' || win.style.display === '') {
            win.style.display = 'flex';
            document.getElementById('aiChatInput').focus();
        } else {
            win.style.display = 'none';
        }
    }

    function sendChatMessage() {
        const input = document.getElementById('aiChatInput');
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        appendMessage(text, 'user');

        const loadingId = 'loading-' + Date.now();
        appendMessage('Thinking...', 'bot', loadingId);

        fetch('../api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) loadingEl.remove();
            
            if (data.status === 'success') {
                appendMessage(data.reply, 'bot');
            } else {
                appendMessage('Error: ' + data.message, 'bot');
            }
        })
        .catch(err => {
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) loadingEl.remove();
            appendMessage('Connection error. Try again.', 'bot');
        });
    }

    function handleChatKey(e) {
        if (e.key === 'Enter') {
            sendChatMessage();
        }
    }

    function appendMessage(html, sender, id = null) {
        const msgs = document.getElementById('aiChatMessages');
        const div = document.createElement('div');
        div.className = `chat-msg ${sender}`;
        if (id) div.id = id;
        div.innerHTML = html;
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }
</script>
