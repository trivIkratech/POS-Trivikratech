<?php
$gold24k = getSetting('rate_gold_24k', '7250');
$gold22k = getSetting('rate_gold_22k', '6650');
$silverRate = getSetting('rate_silver', '88');
$currentRole = $currentUser['role'] ?? 'Employee';
$activeBranch = $_SESSION['active_branch'] ?? 'Bandra West';

// Fetch unread notifications and latest list
$unreadCount = 0;
$notificationsList = [];
try {
    $db = getDBConnection();
    $notifStmt = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    $unreadCount = (int)$notifStmt->fetchColumn();
    $listStmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8");
    $notificationsList = $listStmt->fetchAll();
} catch(Exception $e) {}
?>
<header class="topbar">
  <div style="display: flex; align-items: center; gap: 16px;">
    <!-- Branch Selector -->
    <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; color: var(--color-text-1);">
      <i data-lucide="map-pin" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
      <form action="api/settings.php?action=switch_branch" method="POST" style="margin: 0;">
        <select name="branch" onchange="this.form.submit()" style="border: 1px solid var(--color-border); border-radius: 6px; padding: 4px 8px; font-weight: 600; font-size: 12.5px; background: #fff; cursor: pointer;">
          <option value="Bandra West" <?= $activeBranch === 'Bandra West' ? 'selected' : '' ?>>Bandra West (Flagship)</option>
          <option value="Andheri East" <?= $activeBranch === 'Andheri East' ? 'selected' : '' ?>>Andheri East</option>
          <option value="Powai" <?= $activeBranch === 'Powai' ? 'selected' : '' ?>>Powai</option>
        </select>
      </form>
    </div>

    <!-- Live Metal Rate Ticker -->
    <div class="rate-ticker">
      <div class="rate-pill">
        <span style="color: #D97706; font-size: 11px;">🟡 Gold 24K</span>
        <span style="color: var(--color-text-1);">₹<?= number_format((float)$gold24k) ?>/g</span>
      </div>
      <div style="color: var(--color-border-2);">|</div>
      <div class="rate-pill">
        <span style="color: #B45309; font-size: 11px;">🟠 Gold 22K</span>
        <span style="color: var(--color-text-1);">₹<?= number_format((float)$gold22k) ?>/g</span>
      </div>
      <div style="color: var(--color-border-2);">|</div>
      <div class="rate-pill">
        <span style="color: #4B5563; font-size: 11px;">⚪ Silver</span>
        <span style="color: var(--color-text-1);">₹<?= number_format((float)$silverRate) ?>/g</span>
      </div>
    </div>
  </div>

  <div class="topbar-actions">
    <!-- Notifications Dropdown Container -->
    <div style="position: relative;" id="notifDropdownContainer">
      <button type="button" id="notifBellBtn" class="btn btn-ghost btn-sm" onclick="toggleNotifDropdown(event)" style="position: relative; padding: 8px; border-radius: 8px; cursor: pointer;">
        <i data-lucide="bell" style="width: 18px; height: 18px;"></i>
        <?php if ($unreadCount > 0): ?>
          <span id="notifBadge" style="position: absolute; top: 1px; right: 1px; min-width: 16px; height: 16px; padding: 0 4px; background: #DC2626; color: #fff; font-size: 9.5px; font-weight: 800; border-radius: 9999px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;">
            <?= $unreadCount ?>
          </span>
        <?php endif; ?>
      </button>

      <!-- Dropdown Menu -->
      <div id="notifDropdownMenu" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); width: 340px; background: #FFFFFF; border: 1px solid var(--color-border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; overflow: hidden;">
        <!-- Header -->
        <div style="padding: 12px 16px; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; background: #F9FAFB;">
          <div style="font-weight: 700; font-size: 13.5px; color: #111827; display: flex; align-items: center; gap: 6px;">
            <span>Notifications</span>
            <span id="notifHeaderCount" class="badge" style="font-size: 10px; background: #E0E7FF; color: #4338CA;"><?= $unreadCount ?> unread</span>
          </div>
          <button type="button" onclick="markAllNotificationsRead()" style="background: none; border: none; font-size: 11.5px; font-weight: 600; color: var(--color-primary); cursor: pointer; padding: 2px 4px;">
            Mark all read
          </button>
        </div>

        <!-- Items List -->
        <div id="notifItemsScroll" style="max-height: 280px; overflow-y: auto;">
          <?php if (empty($notificationsList)): ?>
            <div style="padding: 28px; text-align: center; color: #6B7280; font-size: 13px;">
              <i data-lucide="bell-off" style="width: 24px; height: 24px; margin: 0 auto 8px; color: #9CA3AF; display: block;"></i>
              No notifications right now
            </div>
          <?php else: ?>
            <?php foreach ($notificationsList as $n): 
              $isUnread = !(int)$n['is_read'];
              $type = $n['type'] ?? 'info';
              $icon = 'bell';
              $iconColor = '#0F766E';
              $bgColor = '#F0FDFA';
              if ($type === 'warning' || $type === 'alert') {
                $icon = 'alert-triangle';
                $iconColor = '#D97706';
                $bgColor = '#FEF3C7';
              } elseif ($type === 'success') {
                $icon = 'check-circle-2';
                $iconColor = '#059669';
                $bgColor = '#ECFDF5';
              }
            ?>
              <div class="notif-item" style="padding: 10px 14px; border-bottom: 1px solid #F3F4F6; display: flex; gap: 10px; align-items: flex-start; background: <?= $isUnread ? '#F8FAFC' : '#FFFFFF' ?>;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $bgColor ?>; color: <?= $iconColor ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                  <i data-lucide="<?= $icon ?>" style="width: 14px; height: 14px;"></i>
                </div>
                <div style="flex: 1;">
                  <div class="notif-title" style="font-size: 12.5px; font-weight: <?= $isUnread ? '700' : '500' ?>; color: #1F2937; line-height: 1.35;">
                    <?= htmlspecialchars($n['message']) ?>
                  </div>
                  <div style="font-size: 10.5px; color: #9CA3AF; margin-top: 3px;">
                    <?= date('d M, h:i A', strtotime($n['created_at'])) ?>
                  </div>
                </div>
                <?php if ($isUnread): ?>
                  <div class="notif-unread-dot" style="width: 6px; height: 6px; border-radius: 50%; background: var(--color-primary); flex-shrink: 0; margin-top: 6px;"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Footer -->
        <div style="padding: 8px 14px; border-top: 1px solid var(--color-border); background: #F9FAFB; display: flex; justify-content: space-between; align-items: center;">
          <a href="stock.php" style="font-size: 11.5px; font-weight: 600; color: var(--color-primary); text-decoration: none;">View Low Stock</a>
          <button type="button" onclick="clearAllNotifications()" style="background: none; border: none; font-size: 11.5px; color: #DC2626; cursor: pointer;">Clear All</button>
        </div>
      </div>
    </div>

    <!-- User Profile Dropdown -->
    <div style="display: flex; align-items: center; gap: 10px; border-left: 1px solid var(--color-border); padding-left: 14px;">
      <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #14B8A6, #0F766E); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
        <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
      </div>
      <div>
        <div style="font-weight: 700; font-size: 12.5px; line-height: 1.2; color: var(--color-text-1);">
          <?= htmlspecialchars($currentUser['name'] ?? 'User') ?>
        </div>
        <div style="font-size: 11px; color: var(--color-text-2);">
          <?= htmlspecialchars($currentUser['email'] ?? '') ?>
        </div>
      </div>
    </div>
  </div>
</header>
<main class="page-body">
<?php
$flash = getFlash();
if ($flash):
?>
  <div style="padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 13.5px; display: flex; align-items: center; gap: 10px; <?= $flash['type'] === 'success' ? 'background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;' : 'background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA;' ?>">
    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width: 18px; height: 18px;"></i>
    <span><?= htmlspecialchars($flash['message']) ?></span>
  </div>
<?php endif; ?>
