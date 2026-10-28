/**
 * Trivikratech POS - Global Frontend Application Scripts
 */

function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerHTML = `<span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Auto-initialize Lucide Icons
document.addEventListener('DOMContentLoaded', () => {
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Close notification dropdown when clicking outside
  document.addEventListener('click', (e) => {
    const container = document.getElementById('notifDropdownContainer');
    const menu = document.getElementById('notifDropdownMenu');
    if (container && menu && !container.contains(e.target)) {
      menu.style.display = 'none';
    }
  });
});

function toggleNotifDropdown(e) {
  if (e) e.stopPropagation();
  const menu = document.getElementById('notifDropdownMenu');
  if (!menu) return;
  const isShown = menu.style.display === 'block';
  menu.style.display = isShown ? 'none' : 'block';
}

async function markAllNotificationsRead() {
  try {
    const res = await fetch('api/notifications.php?action=mark_all_read');
    const data = await res.json();
    if (data.success) {
      const badge = document.getElementById('notifBadge');
      if (badge) badge.remove();
      const countHeader = document.getElementById('notifHeaderCount');
      if (countHeader) countHeader.innerText = '0 unread';
      document.querySelectorAll('.notif-unread-dot').forEach(el => el.remove());
      document.querySelectorAll('.notif-item').forEach(el => {
        el.style.background = '#FFFFFF';
        const titleEl = el.querySelector('.notif-title');
        if (titleEl) titleEl.style.fontWeight = '500';
      });
      showToast('All notifications marked as read', 'success');
    }
  } catch(err) {
    console.error(err);
  }
}

async function clearAllNotifications() {
  try {
    const res = await fetch('api/notifications.php?action=clear_all');
    const data = await res.json();
    if (data.success) {
      const badge = document.getElementById('notifBadge');
      if (badge) badge.remove();
      const countHeader = document.getElementById('notifHeaderCount');
      if (countHeader) countHeader.innerText = '0 unread';
      const listContainer = document.getElementById('notifItemsScroll');
      if (listContainer) {
        listContainer.innerHTML = `
          <div style="padding: 28px; text-align: center; color: #6B7280; font-size: 13px;">
            <i data-lucide="bell-off" style="width: 24px; height: 24px; margin: 0 auto 8px; color: #9CA3AF; display: block;"></i>
            No notifications right now
          </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
      }
      showToast('Notifications cleared', 'info');
    }
  } catch(err) {
    console.error(err);
  }
}

