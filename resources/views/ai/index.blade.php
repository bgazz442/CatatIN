@extends('layouts.app')

@section('title', 'CatatIn Assistant')
@section('page-title', 'CatatIn Assistant')
@section('page-subtitle', 'Asisten pribadi untuk catatan, tugas, dan target Anda.')

@section('content')
<div class="chat-page">
    <div id="chat-shell" class="chat-shell">
        <aside id="chat-sidebar" class="chat-sidebar" aria-label="Riwayat percakapan">
            <div class="chat-sidebar-header">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="chat-brand-mark">
                        <i data-lucide="sparkles" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">CatatIn Assistant</p>
                        <p class="text-[11px] text-slate-500">Asisten pribadi</p>
                    </div>
                </div>
                <button id="chat-sidebar-close" type="button" class="chat-icon-button lg:hidden" aria-label="Tutup riwayat">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="px-3 pt-3">
                <button id="new-chat" type="button" class="chat-new-button">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Percakapan baru</span>
                </button>
            </div>

            <div class="chat-history">
                <div id="conversation-list">
                    @php
                        $todayConversations = $conversations->filter(fn ($conversation) => $conversation->updated_at?->isToday());
                        $yesterdayConversations = $conversations->filter(fn ($conversation) => $conversation->updated_at?->isYesterday());
                        $earlierConversations = $conversations->reject(fn ($conversation) => $conversation->updated_at?->isToday() || $conversation->updated_at?->isYesterday());
                    @endphp

                    @foreach ([['Hari Ini', 'today', $todayConversations], ['Kemarin', 'yesterday', $yesterdayConversations], ['Sebelumnya', 'earlier', $earlierConversations]] as [$label, $group, $items])
                        @if ($items->isNotEmpty())
                            <section data-history-group="{{ $group }}" class="chat-history-group">
                                <h2>{{ $label }}</h2>
                                @foreach ($items as $conversation)
                                    <div role="button" tabindex="0"
                                         data-conversation-id="{{ $conversation->id }}"
                                         data-title="{{ $conversation->title ?? 'Percakapan baru' }}"
                                         class="conversation-item">
                                        <div class="min-w-0 flex-1">
                                            <p class="conversation-title">{{ $conversation->title ?? 'Percakapan baru' }}</p>
                                            <p class="conversation-date">{{ $conversation->updated_at?->diffForHumans() }}</p>
                                        </div>
                                        <button type="button" data-delete-conversation="{{ $conversation->id }}" class="delete-conversation" aria-label="Hapus percakapan">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </section>
                        @endif
                    @endforeach

                    @if ($conversations->isEmpty())
                        <div data-empty-conversations class="chat-empty-history">
                            Belum ada percakapan.
                        </div>
                    @endif
                </div>
            </div>

            <div class="chat-account">
                @auth
                    <div class="chat-account-avatar">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('settings') }}" class="chat-icon-button" aria-label="Pengaturan chatbot">
                        <i data-lucide="settings-2" class="h-4 w-4"></i>
                    </a>
                @endauth
            </div>
        </aside>

        <div id="chat-mobile-backdrop" class="chat-mobile-backdrop" aria-hidden="true"></div>

        <main class="chat-main">
            <header class="chat-main-header">
                <div class="flex min-w-0 items-center gap-2">
                    <button id="chat-sidebar-toggle" type="button" class="chat-icon-button" aria-label="Tampilkan atau sembunyikan riwayat">
                        <i data-lucide="panel-left" class="h-4 w-4"></i>
                    </button>
                    <button id="chat-mobile-menu" type="button" class="chat-icon-button lg:hidden" aria-label="Buka riwayat">
                        <i data-lucide="menu" class="h-4 w-4"></i>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">Apa yang ingin kamu rencanakan?</p>
                        <p id="chat-status-label" class="text-[11px] text-slate-500">Siap membantu</p>
                    </div>
                </div>
                <button id="stop-generation" type="button" class="chat-stop-button hidden">
                    <i data-lucide="square" class="h-3.5 w-3.5"></i>
                    Hentikan
                </button>
            </header>

            <div id="chat-messages" class="chat-messages" aria-live="polite">
                <div class="chat-welcome">
                    <div class="chat-welcome-mark">
                        <i data-lucide="sparkles" class="h-6 w-6"></i>
                    </div>
                    <h2>Selamat datang di CatatIn Assistant</h2>
                    <p>Asisten AI pribadi yang siap membantu kamu memahami catatan, mengelola tugas dan target, serta menjawab berbagai pertanyaan.</p>
                    <div class="chat-prompt-grid">
                        <button type="button" class="quick-prompt" data-prompt="Bantu saya memahami materi pemrograman.">
                            <span>Bantu saya memahami materi pemrograman.</span>
                            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                        </button>
                        <button type="button" class="quick-prompt" data-prompt="Apa saja catatan belajar saya?">
                            <span>Apa saja catatan belajar saya?</span>
                            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                        </button>
                        <button type="button" class="quick-prompt" data-prompt="Bantu saya menyusun prioritas berdasarkan tugas dan target saya.">
                            <span>Bantu saya menyusun prioritas tugas.</span>
                            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                        </button>
                        <button type="button" class="quick-prompt" data-prompt="Jelaskan konsep database dengan sederhana.">
                            <span>Jelaskan konsep database dengan sederhana.</span>
                            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="chat-composer-wrap">
                <form id="chat-form" class="chat-composer-form">
                    <input type="hidden" id="conversation_id" name="conversation_id" value="">
                    <label for="chat-input" class="sr-only">Masukkan pertanyaan</label>
                    <div class="chat-composer">
                        <textarea id="chat-input" name="message" rows="1" maxlength="5000"
                                  class="chat-textarea"
                                  placeholder="Tanyakan apa saja kepada CatatIn Assistant..."></textarea>
                        <button id="submit-chat" type="submit" class="chat-send-button" aria-label="Kirim pesan">
                            <i data-lucide="arrow-up" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <p class="chat-disclaimer">Jawaban dapat memuat data pribadi milik akun Anda. Periksa kembali hasil yang penting.</p>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    const conversationList = document.getElementById('conversation-list');
    const newChatButton = document.getElementById('new-chat');
    const stopGenerationButton = document.getElementById('stop-generation');
    const conversationIdInput = document.getElementById('conversation_id');
    const submitChatButton = document.getElementById('submit-chat');
    const statusLabel = document.getElementById('chat-status-label');
    const chatShell = document.getElementById('chat-shell');
    const chatSidebar = document.getElementById('chat-sidebar');
    const chatSidebarToggle = document.getElementById('chat-sidebar-toggle');
    const chatMobileMenu = document.getElementById('chat-mobile-menu');
    const chatSidebarClose = document.getElementById('chat-sidebar-close');
    const chatMobileBackdrop = document.getElementById('chat-mobile-backdrop');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let activeAbortController = null;
    let isSubmitting = false;

    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = value;
        return element.innerHTML;
    }

    function renderInlineMarkdown(value) {
        let html = escapeHtml(value);
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        return html;
    }

    function renderMarkdown(value) {
        const codeBlocks = [];
        const source = String(value).replace(/```(?:[\w-]+)?\n([\s\S]*?)```/g, (_, code) => {
            const index = codeBlocks.push(code.replace(/\n$/, '')) - 1;
            return '\n@@CODE_BLOCK_' + index + '@@\n';
        });

        const lines = source.split('\n');
        let html = '';
        let listType = null;
        let tablePending = false;

        const closeList = () => {
            if (listType) {
                html += '</' + listType + '>';
                listType = null;
            }
        };

        lines.forEach((line, index) => {
            const trimmed = line.trim();
            const codeMatch = trimmed.match(/^@@CODE_BLOCK_(\d+)@@$/);
            if (codeMatch) {
                closeList();
                const code = escapeHtml(codeBlocks[Number(codeMatch[1])]);
                html += '<div class="chat-code-wrap"><button type="button" class="chat-code-copy" data-copy-code aria-label="Salin kode"><i data-lucide="copy"></i><span>Salin</span></button><pre><code>' + code + '</code></pre></div>';
                return;
            }

            if (!trimmed) {
                closeList();
                return;
            }

            if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
                closeList();
                const cells = trimmed.slice(1, -1).split('|').map((cell) => cell.trim());
                const next = lines[index + 1]?.trim();
                if (next && /^\|[\s\-:|]+\|$/.test(next)) {
                    html += '<div class="chat-table-wrap"><table><thead><tr>' + cells.map((cell) => '<th>' + renderInlineMarkdown(cell) + '</th>').join('') + '</tr></thead><tbody>';
                    tablePending = true;
                    return;
                }
                if (tablePending && !cells.every((cell) => /^:?-+:?$/.test(cell))) {
                    html += '<tr>' + cells.map((cell) => '<td>' + renderInlineMarkdown(cell) + '</td>').join('') + '</tr>';
                    return;
                }
                if (tablePending) {
                    return;
                }
                html += '<p>' + renderInlineMarkdown(trimmed) + '</p>';
                return;
            }
            if (tablePending) {
                html += '</tbody></table></div>';
                tablePending = false;
            }

            if (/^### /.test(trimmed)) {
                closeList(); html += '<h3>' + renderInlineMarkdown(trimmed.slice(4)) + '</h3>'; return;
            }
            if (/^## /.test(trimmed)) {
                closeList(); html += '<h2>' + renderInlineMarkdown(trimmed.slice(3)) + '</h2>'; return;
            }
            if (/^# /.test(trimmed)) {
                closeList(); html += '<h1>' + renderInlineMarkdown(trimmed.slice(2)) + '</h1>'; return;
            }
            if (/^[-*] /.test(trimmed)) {
                if (listType !== 'ul') { closeList(); html += '<ul>'; listType = 'ul'; }
                html += '<li>' + renderInlineMarkdown(trimmed.slice(2)) + '</li>'; return;
            }
            if (/^\d+\.\s/.test(trimmed)) {
                if (listType !== 'ol') { closeList(); html += '<ol>'; listType = 'ol'; }
                html += '<li>' + renderInlineMarkdown(trimmed.replace(/^\d+\.\s/, '')) + '</li>'; return;
            }
            if (/^>\s/.test(trimmed)) {
                closeList(); html += '<blockquote>' + renderInlineMarkdown(trimmed.slice(2)) + '</blockquote>'; return;
            }

            closeList();
            html += '<p>' + renderInlineMarkdown(trimmed) + '</p>';
        });

        closeList();
        if (tablePending) {
            html += '</tbody></table></div>';
        }
        return html;
    }

    function resizeChatInput() {
        chatInput.style.height = 'auto';
        chatInput.style.height = Math.min(chatInput.scrollHeight, 160) + 'px';
    }

    function scrollToLatest(force = false) {
        const distanceFromBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight;
        if (force || distanceFromBottom < 120) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    function resetChat() {
        chatMessages.innerHTML = '';
        chatMessages.innerHTML = `
            <div class="chat-welcome">
                <div class="chat-welcome-mark"><i data-lucide="sparkles" class="h-6 w-6"></i></div>
                <h2>Selamat datang di CatatIn Assistant</h2>
                <p>Asisten AI pribadi yang siap membantu kamu memahami catatan, mengelola tugas dan target, serta menjawab berbagai pertanyaan.</p>
                <div class="chat-prompt-grid">
                    <button type="button" class="quick-prompt" data-prompt="Bantu saya memahami materi pemrograman."><span>Bantu saya memahami materi pemrograman.</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></button>
                    <button type="button" class="quick-prompt" data-prompt="Apa saja catatan belajar saya?"><span>Apa saja catatan belajar saya?</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></button>
                    <button type="button" class="quick-prompt" data-prompt="Bantu saya menyusun prioritas berdasarkan tugas dan target saya."><span>Bantu saya menyusun prioritas tugas.</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></button>
                    <button type="button" class="quick-prompt" data-prompt="Jelaskan konsep database dengan sederhana."><span>Jelaskan konsep database dengan sederhana.</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></button>
                </div>
            </div>`;
        attachQuickPrompts();
        if (window.lucide) window.lucide.createIcons();
        chatMessages.scrollTop = 0;
    }

    function attachQuickPrompts() {
        document.querySelectorAll('.quick-prompt').forEach((button) => {
            button.onclick = () => {
                chatInput.value = button.dataset.prompt || '';
                resizeChatInput();
                chatForm.requestSubmit();
            };
        });
    }

    function attachCopyHandlers(root = document) {
        root.querySelectorAll('[data-copy-code]').forEach((button) => {
            button.onclick = async () => {
                const code = button.closest('.chat-code-wrap')?.querySelector('code')?.textContent || '';
                await navigator.clipboard.writeText(code);
                button.querySelector('span').textContent = 'Disalin';
                setTimeout(() => { button.querySelector('span').textContent = 'Salin'; }, 1200);
            };
        });
    }

    function appendMessage(role, content, sources = []) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-message chat-message-' + role;

        if (role === 'assistant') {
            const avatar = document.createElement('div');
            avatar.className = 'chat-message-avatar';
            avatar.innerHTML = '<i data-lucide="sparkles" class="h-4 w-4"></i>';
            wrapper.appendChild(avatar);
        }

        const body = document.createElement('div');
        body.className = role === 'user' ? 'chat-user-bubble' : 'chat-assistant-body';

        if (role === 'assistant') {
            const markdown = document.createElement('div');
            markdown.className = 'chat-markdown';
            markdown.innerHTML = renderMarkdown(content);
            body.appendChild(markdown);

            if (sources.length) {
                const sourcesWrapper = document.createElement('div');
                sourcesWrapper.className = 'chat-sources';
                sourcesWrapper.innerHTML = '<p>Sumber digunakan</p><div></div>';
                const list = sourcesWrapper.querySelector('div');
                sources.forEach((source) => {
                    const link = document.createElement('a');
                    link.href = source.link || '#';
                    link.textContent = source.title || 'Sumber';
                    list.appendChild(link);
                });
                body.appendChild(sourcesWrapper);
            }

            const actions = document.createElement('div');
            actions.className = 'chat-message-actions';
            actions.innerHTML = '<button type="button" class="chat-copy-answer" aria-label="Salin jawaban"><i data-lucide="copy" class="h-3.5 w-3.5"></i><span>Salin</span></button>';
            actions.querySelector('button').onclick = async () => {
                await navigator.clipboard.writeText(content);
                const span = actions.querySelector('span');
                span.textContent = 'Disalin';
                setTimeout(() => { span.textContent = 'Salin'; }, 1200);
            };
            body.appendChild(actions);
        } else {
            body.textContent = content;
        }

        wrapper.appendChild(body);
        chatMessages.appendChild(wrapper);
        attachCopyHandlers(wrapper);
        if (window.lucide) window.lucide.createIcons();
        scrollToLatest(false);
    }

    function showThinking() {
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-message chat-message-assistant';
        wrapper.setAttribute('data-thinking', 'true');
        wrapper.innerHTML = '<div class="chat-message-avatar"><i data-lucide="sparkles" class="h-4 w-4"></i></div><div class="chat-thinking"><span></span><span></span><span></span></div>';
        chatMessages.appendChild(wrapper);
        if (window.lucide) window.lucide.createIcons();
        scrollToLatest(true);
    }

    function removeThinking() {
        document.querySelector('[data-thinking="true"]')?.remove();
    }

    function getHistoryGroup() {
        let group = conversationList.querySelector('[data-history-group="today"]');
        if (!group) {
            group = document.createElement('section');
            group.dataset.historyGroup = 'today';
            group.className = 'chat-history-group';
            group.innerHTML = '<h2>Hari Ini</h2>';
            conversationList.prepend(group);
        }
        return group;
    }

    function refreshConversationList(title = null, conversationId = null) {
        conversationList.querySelector('[data-empty-conversations]')?.remove();
        if (title && conversationId && !conversationList.querySelector('[data-conversation-id="' + conversationId + '"]')) {
            const item = document.createElement('div');
            item.className = 'conversation-item';
            item.setAttribute('role', 'button');
            item.tabIndex = 0;
            item.dataset.conversationId = conversationId;
            item.dataset.title = title;
            item.innerHTML = '<div class="min-w-0 flex-1"><p class="conversation-title"></p><p class="conversation-date">Baru saja</p></div><button type="button" class="delete-conversation" aria-label="Hapus percakapan"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i></button>';
            item.querySelector('.conversation-title').textContent = title;
            item.querySelector('button').dataset.deleteConversation = conversationId;
            item.addEventListener('click', (event) => {
                if (!event.target.closest('[data-delete-conversation]')) loadConversation(conversationId);
            });
            item.addEventListener('keydown', (event) => {
                if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('[data-delete-conversation]')) {
                    event.preventDefault(); loadConversation(conversationId);
                }
            });
            item.querySelector('button').addEventListener('click', (event) => {
                event.stopPropagation(); deleteConversation(conversationId);
            });
            getHistoryGroup().appendChild(item);
        }
        document.querySelectorAll('.conversation-item').forEach((item) => {
            item.classList.toggle('is-active', item.dataset.conversationId === conversationIdInput.value);
        });
        if (window.lucide) window.lucide.createIcons();
    }

    function loadConversation(conversationId) {
        fetch('/ai/conversations/' + conversationId, {
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
        })
            .then((response) => {
                if (!response.ok) throw new Error('Percakapan tidak dapat dimuat.');
                return response.json();
            })
            .then((data) => {
                chatMessages.innerHTML = '';
                conversationIdInput.value = data.conversation_id;
                (data.messages || []).forEach((message) => appendMessage(message.role, message.content, message.source_refs || []));
                document.querySelectorAll('.conversation-item').forEach((item) => item.classList.toggle('is-active', item.dataset.conversationId === String(conversationId)));
                closeMobileSidebar();
                scrollToLatest(true);
            })
            .catch(() => appendMessage('assistant', 'Riwayat percakapan gagal dimuat. Silakan coba lagi.'));
    }

    function deleteConversation(conversationId) {
        if (!window.confirm('Hapus percakapan ini? Tindakan ini tidak dapat dibatalkan.')) return;
        fetch('/ai/conversations/' + conversationId, {
            method: 'DELETE',
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
        })
            .then((response) => {
                if (!response.ok) throw new Error('Percakapan tidak dapat dihapus.');
                return response.json();
            })
            .then(() => {
                const item = document.querySelector('[data-conversation-id="' + conversationId + '"]');
                item?.remove();
                if (!conversationList.querySelector('.conversation-item')) {
                    conversationList.innerHTML = '<div data-empty-conversations class="chat-empty-history">Belum ada percakapan.</div>';
                }
                if (conversationIdInput.value === String(conversationId)) {
                    conversationIdInput.value = '';
                    resetChat();
                }
            })
            .catch(() => appendMessage('assistant', 'Percakapan gagal dihapus. Silakan coba lagi.'));
    }

    document.querySelectorAll('.conversation-item').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!event.target.closest('[data-delete-conversation]')) loadConversation(element.dataset.conversationId);
        });
        element.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('[data-delete-conversation]')) {
                event.preventDefault(); loadConversation(element.dataset.conversationId);
            }
        });
    });
    document.querySelectorAll('[data-delete-conversation]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation(); deleteConversation(button.dataset.deleteConversation);
        });
    });

    newChatButton.addEventListener('click', () => {
        conversationIdInput.value = '';
        resetChat();
        closeMobileSidebar();
        chatInput.focus();
    });

    function openMobileSidebar() {
        chatSidebar.classList.add('is-open');
        chatMobileBackdrop.classList.add('is-open');
    }

    function closeMobileSidebar() {
        chatSidebar.classList.remove('is-open');
        chatMobileBackdrop.classList.remove('is-open');
    }

    chatSidebarToggle.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 1023px)').matches) {
            chatSidebar.classList.contains('is-open') ? closeMobileSidebar() : openMobileSidebar();
            return;
        }
        chatShell.classList.toggle('sidebar-hidden');
        chatSidebar.classList.toggle('is-hidden');
    });
    chatMobileMenu.addEventListener('click', openMobileSidebar);
    chatSidebarClose.addEventListener('click', closeMobileSidebar);
    chatMobileBackdrop.addEventListener('click', closeMobileSidebar);

    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (isSubmitting) return;

        const message = chatInput.value.trim();
        if (!message) {
            chatInput.focus();
            return;
        }

        appendMessage('user', message);
        isSubmitting = true;
        const conversationId = conversationIdInput.value || null;
        chatInput.value = '';
        resizeChatInput();
        chatInput.disabled = true;
        submitChatButton.disabled = true;
        submitChatButton.classList.add('is-loading');
        statusLabel.textContent = 'Sedang menghasilkan jawaban...';
        showThinking();
        stopGenerationButton.classList.remove('hidden');

        activeAbortController = new AbortController();
        let partialMessage = null;
        let answerText = '';
        let receivedDone = false;

        try {
            const response = await fetch('/ai/chat', {
                method: 'POST',
                signal: activeAbortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message: message,
                    conversation_id: conversationId,
                }),
            });

            if (!response.ok) throw new Error('Gagal memproses permintaan.');

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            const handleEvent = (line) => {
                if (!line.trim()) return;
                const event = JSON.parse(line);
                if (event.type === 'conversation') {
                    conversationIdInput.value = event.conversation_id;
                    refreshConversationList(event.title, event.conversation_id);
                    return;
                }
                if (event.type === 'chunk') {
                    removeThinking();
                    answerText += event.text;
                    if (!partialMessage) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'chat-message chat-message-assistant';
                        wrapper.innerHTML = '<div class="chat-message-avatar"><i data-lucide="sparkles" class="h-4 w-4"></i></div><div class="chat-assistant-body"><div class="chat-markdown chat-streaming"></div></div>';
                        chatMessages.appendChild(wrapper);
                        partialMessage = { wrapper, text: wrapper.querySelector('.chat-markdown') };
                        if (window.lucide) window.lucide.createIcons();
                    }
                    partialMessage.text.textContent = answerText;
                    scrollToLatest(false);
                    return;
                }
                if (event.type === 'done') {
                    partialMessage?.wrapper.remove();
                    partialMessage = null;
                    appendMessage('assistant', answerText, event.source_refs || []);
                    receivedDone = true;
                    return;
                }
                if (event.type === 'error') {
                    throw new Error(event.message || 'Jawaban gagal dibuat.');
                }
            };

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                lines.forEach(handleEvent);
            }
            buffer += decoder.decode();
            handleEvent(buffer);
            if (!receivedDone) throw new Error('Jawaban tidak selesai diproses.');
        } catch (error) {
            removeThinking();
            partialMessage?.wrapper.remove();
            if (error.name === 'AbortError') {
                appendMessage('assistant', 'Jawaban dihentikan. Kamu bisa mengirim pertanyaan berikutnya untuk melanjutkan.');
            } else {
                appendMessage('assistant', 'Maaf, saya tidak bisa menjawab saat ini. Silakan cek koneksi atau coba lagi beberapa saat lagi.');
            }
        } finally {
            removeThinking();
            activeAbortController = null;
            isSubmitting = false;
            chatInput.disabled = false;
            submitChatButton.disabled = false;
            submitChatButton.classList.remove('is-loading');
            stopGenerationButton.classList.add('hidden');
            statusLabel.textContent = 'Siap membantu';
            chatInput.focus();
        }
    });

    stopGenerationButton.addEventListener('click', () => {
        activeAbortController?.abort();
        activeAbortController = null;
        removeThinking();
    });

    chatInput.addEventListener('input', resizeChatInput);
    chatInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            chatForm.requestSubmit();
        }
    });

    attachQuickPrompts();
    attachCopyHandlers();
    resizeChatInput();
</script>
@endsection
