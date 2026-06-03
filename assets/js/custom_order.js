/* ─────────────────────────────────────────────
 * CUSTOM_ORDER.JS — Chat konsultasi dengan polling
 * ───────────────────────────────────────────── */

(function () {
  'use strict';

  const chatBox = document.getElementById('chat-box');
  if (!chatBox) return;

  const idCustom = chatBox.dataset.idCustom;
  const BASE     = window.location.pathname.split('/pelanggan/')[0] + '/';
  const ENDPOINT_FETCH = BASE + 'pelanggan/chat_fetch.php';
  const ENDPOINT_SEND  = BASE + 'pelanggan/chat_send.php';

  let lastChatId   = 0;
  let pollTimer    = null;
  let lastDay      = '';

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function formatTime(ts) {
    const d = new Date(ts.replace(' ', 'T'));
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  }

  function formatDay(ts) {
    const d = new Date(ts.replace(' ', 'T'));
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString())     return 'Hari Ini';
    if (d.toDateString() === yesterday.toDateString()) return 'Kemarin';
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function appendChat(chat) {
    const dayLabel = formatDay(chat.created_at);
    if (dayLabel !== lastDay) {
      const divider = document.createElement('div');
      divider.className = 'chat-day-divider';
      divider.innerHTML = '<span>' + dayLabel + '</span>';
      chatBox.appendChild(divider);
      lastDay = dayLabel;
    }

    const role = chat.pengirim_role;
    const wrap = document.createElement('div');
    wrap.className = 'chat-bubble-wrap from-' + role;
    wrap.innerHTML = `
      <div>
        <div class="chat-sender-label">${role === 'admin' ? '👨‍💼 Admin Toko' : '🙋 Kamu'}</div>
        <div class="chat-bubble">${escapeHtml(chat.pesan)}</div>
        <div class="chat-time">${formatTime(chat.created_at)}</div>
      </div>
    `;
    chatBox.appendChild(wrap);
    lastChatId = Math.max(lastChatId, parseInt(chat.id_chat));
  }

  function scrollToBottom() {
    chatBox.scrollTop = chatBox.scrollHeight;
  }

  function fetchChats(isInitial) {
    const url = ENDPOINT_FETCH + '?id_custom=' + idCustom + '&since=' + lastChatId;
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;

        if (isInitial) chatBox.innerHTML = '';

        if (data.chats && data.chats.length > 0) {
          data.chats.forEach(appendChat);
          scrollToBottom();
        } else if (isInitial) {
          chatBox.innerHTML = '<div class="co-chat-loading"><i class="bi bi-chat"></i> Belum ada percakapan. Mulai dengan pesan pertama!</div>';
        }

        // Reload kalau status berubah jadi closed
        if (data.chat_closed && document.getElementById('chat-form')) {
          location.reload();
        }
      })
      .catch(err => console.error('Chat fetch error:', err));
  }

  // Initial load
  fetchChats(true);

  // Polling tiap 5 detik
  pollTimer = setInterval(() => fetchChats(false), 5000);

  // Stop polling saat tab tidak aktif (hemat resource)
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearInterval(pollTimer);
    } else {
      fetchChats(false);
      pollTimer = setInterval(() => fetchChats(false), 5000);
    }
  });

  // ── Submit chat ────────────────────────────
  const form    = document.getElementById('chat-form');
  const input   = document.getElementById('chat-input');
  const sendBtn = document.getElementById('chat-send-btn');

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const pesan = input.value.trim();
      if (!pesan) return;

      sendBtn.disabled = true;
      sendBtn.innerHTML = '<i class="bi bi-arrow-clockwise spinning"></i>';

      const fd = new FormData(form);
      fetch(ENDPOINT_SEND, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            input.value = '';
            appendChat(data.chat);
            scrollToBottom();
          } else {
            alert(data.message || 'Gagal mengirim pesan');
          }
        })
        .catch(err => {
          console.error('Send chat error:', err);
          alert('Gagal mengirim pesan');
        })
        .finally(() => {
          sendBtn.disabled = false;
          sendBtn.innerHTML = '<i class="bi bi-send-fill"></i>';
          input.focus();
        });
    });

    // Enter to send (Shift+Enter for new line)
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        form.dispatchEvent(new Event('submit'));
      }
    });
  }
})();
