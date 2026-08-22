    </section>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const adminToggle = document.getElementById('admin-menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebar-overlay');

  if (adminToggle && sidebar) {
    function toggleAdminSidebar(show) {
      const isOpen = typeof show === 'boolean' ? show : !sidebar.classList.contains('is-open');
      if (isOpen) {
        sidebar.classList.add('is-open');
        if (overlay) overlay.classList.add('is-open');
        adminToggle.setAttribute('aria-expanded', 'true');
        adminToggle.innerHTML = '✕';
      } else {
        sidebar.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-open');
        adminToggle.setAttribute('aria-expanded', 'false');
        adminToggle.innerHTML = '☰';
      }
    }

    adminToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      toggleAdminSidebar();
    });

    if (overlay) {
      overlay.addEventListener('click', function () {
        toggleAdminSidebar(false);
      });
    }

    sidebar.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function () {
        if (window.innerWidth <= 900) {
          toggleAdminSidebar(false);
        }
      });
    });
  }
});

const BASE_URL = '<?= BASE_URL ?>';

// Admin Real-Time Notification Polling & Chime Sound
(function() {
  const bellBtn = document.getElementById('adminNotifBellBtn');
  const dropdown = document.getElementById('adminNotifDropdown');
  const badge = document.getElementById('adminNotifBadge');
  const notifList = document.getElementById('adminNotifList');
  const markAllBtn = document.getElementById('adminMarkAllReadBtn');
  const toastContainer = document.getElementById('adminToastContainer');

  let previousUnreadCount = -1;
  let audioCtx = null;

  function playChimeSound() {
    try {
      if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      if (audioCtx.state === 'suspended') {
        audioCtx.resume();
      }
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.15);
      gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.5);
    } catch(e){}
  }

  function showToast(title, message, orderId) {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = 'admin-toast';
    toast.innerHTML = `
      <div style="font-size:22px;">🔔</div>
      <div style="flex:1;">
        <strong style="font-size:13.5px;color:var(--espresso);display:block;margin-bottom:2px;">${escapeHtml(title)}</strong>
        <p style="margin:0;font-size:12px;color:#555;line-height:1.4;">${escapeHtml(message)}</p>
        ${orderId ? `<a href="${BASE_URL}/admin/orders.php?detail=${orderId}" style="font-size:11.5px;font-weight:bold;color:var(--terracotta-dark);display:inline-block;margin-top:4px;">Lihat Pesanan →</a>` : ''}
      </div>
    `;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 400);
    }, 5000);
  }

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function fetchNotifications() {
    fetch(BASE_URL + '/admin/ajax-notifications.php?action=fetch')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const unreadCount = data.unread_count || 0;
          
          if (badge) {
            badge.textContent = unreadCount;
            badge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
            if (unreadCount > 0) {
              badge.classList.add('pulse');
            } else {
              badge.classList.remove('pulse');
            }
          }

          if (previousUnreadCount >= 0 && unreadCount > previousUnreadCount) {
            playChimeSound();
            const latestNotif = data.notifications && data.notifications[0];
            if (latestNotif && !latestNotif.is_read) {
              showToast(latestNotif.title, latestNotif.message, latestNotif.order_id);
            }
          }
          previousUnreadCount = unreadCount;

          if (notifList && data.notifications) {
            if (data.notifications.length === 0) {
              notifList.innerHTML = '<div style="text-align:center;padding:20px;color:#888;font-size:12px;">Tidak ada notifikasi.</div>';
            } else {
              notifList.innerHTML = data.notifications.map(n => `
                <div style="padding:10px 14px;border-bottom:1px solid #f0f0f0;background:${n.is_read ? '#fff' : '#fffcf8'};display:flex;flex-direction:column;gap:3px;">
                  <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="font-size:12.5px;color:var(--espresso);">${escapeHtml(n.title)}</strong>
                    <small style="font-size:10.5px;color:#999;">${escapeHtml(n.time_ago)}</small>
                  </div>
                  <p style="margin:0;font-size:11.5px;color:#555;line-height:1.4;">${escapeHtml(n.message)}</p>
                  ${n.order_id ? `<a href="${BASE_URL}/admin/orders.php?detail=${n.order_id}" style="font-size:11px;font-weight:bold;color:var(--terracotta-dark);margin-top:2px;">Buka Pesanan →</a>` : ''}
                </div>
              `).join('');
            }
          }
        }
      })
      .catch(e => {});
  }

  if (bellBtn && dropdown) {
    bellBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const isVisible = dropdown.style.display === 'block';
      dropdown.style.display = isVisible ? 'none' : 'block';
      if (!isVisible) fetchNotifications();
    });

    document.addEventListener('click', function(e) {
      if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  }

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function() {
      const formData = new FormData();
      formData.append('action', 'mark_read');
      fetch(BASE_URL + '/admin/ajax-notifications.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) fetchNotifications();
        });
    });
  }

  fetchNotifications();
  setInterval(fetchNotifications, 10000);
})();
</script>
<script src="<?= BASE_URL ?>/assets/js/price-formatter.js"></script>
</body>
</html>
