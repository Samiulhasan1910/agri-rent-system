<?php
$tabs = [
    'general' => ['General Settings', 'settings'], 'security' => ['Security Settings', 'shield'], 'pricing' => ['Pricing Rules', 'tag'],
    'notifications' => ['Notification Settings', 'bell'], 'payments' => ['Payments & Booking', 'credit-card'], 'coupons' => ['Coupons', 'percent'],
    'accounts' => ['Account Management', 'users'], 'logs' => ['System Logs', 'list'],
];
$on = static fn(string $k): string => !empty($s[$k]) && $s[$k] === '1' ? ' checked' : '';
?>
<div class="page-head"><div><h1><?= e(t('System Setting')) ?></h1><p class="muted"><?= e(t('Manage system security, settings, language and system rules')) ?></p></div></div>
<div class="settings-layout">
    <nav class="card settings-nav">
        <?php foreach ($tabs as $k => [$label, $ico]): ?><a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= e(url('settings/index', ['tab' => $k])) ?>"><?= icon($ico) ?><?= e(t($label)) ?></a><?php endforeach; ?>
    </nav>
    <section class="card">
    <?php if ($tab === 'general'): ?>
        <h2 class="card-title"><?= e(t('General Settings')) ?></h2>
        <form method="post" action="<?= e(url('settings/save')) ?>" data-ajax novalidate>
            <?= csrf_field() ?><input type="hidden" name="section" value="general">
            <div class="form-grid">
                <div class="field"><label><?= e(t('Platform Name')) ?></label><input name="platform_name" value="<?= e($s['platform_name']) ?>"><?= field_error('platform_name') ?></div>
                <div class="field"><label><?= e(t('Support Email')) ?></label><input name="support_email" value="<?= e($s['support_email']) ?>"><?= field_error('support_email') ?></div>
                <div class="field"><label><?= e(t('Support Hotline')) ?></label><input name="support_phone" value="<?= e($s['support_phone']) ?>"><?= field_error('support_phone') ?></div>
                <div class="field"><label><?= e(t('Default Language')) ?></label><select name="default_language"><option value="en"<?= selected($s['default_language'], 'en') ?>>English</option><option value="bn"<?= selected($s['default_language'], 'bn') ?>>বাংলা</option></select></div>
                <div class="field"><label><?= e(t('Time Zone')) ?></label><input value="<?= e($s['timezone']) ?> (GMT+6)" disabled></div>
                <div class="field"><label><?= e(t('Delivery charge (৳)')) ?></label><input type="number" min="0" name="delivery_charge" value="<?= e($s['delivery_charge']) ?>"><?= field_error('delivery_charge') ?></div>
                <div class="field"><label><?= e(t('Emergency search radius (km)')) ?></label><input type="number" min="1" name="emergency_radius_km" value="<?= e($s['emergency_radius_km']) ?>"><?= field_error('emergency_radius_km') ?></div>
            </div>
            <button class="btn btn-primary"><?= icon('check') ?><?= e(t('Save Changes')) ?></button>
        </form>
    <?php elseif ($tab === 'security'): ?>
        <h2 class="card-title"><?= e(t('Security Settings')) ?></h2>
        <form method="post" action="<?= e(url('settings/save')) ?>" data-ajax novalidate>
            <?= csrf_field() ?><input type="hidden" name="section" value="security">
            <label class="switch-row"><span><strong><?= e(t('Enable Two-Factor Authentication for everyone')) ?></strong><small><?= e(t('Every login requires a 6-digit code sent by email (users can also enable it individually).')) ?></small></span><input type="checkbox" name="enable_2fa_all" value="1"<?= $on('enable_2fa_all') ?>></label>
            <label class="switch-row"><span><strong><?= e(t('Suspicious Activity Alert')) ?></strong><small><?= e(t('Alert administrators about lockouts, new-device logins and repeated failures.')) ?></small></span><input type="checkbox" name="suspicious_activity_alert" value="1"<?= $on('suspicious_activity_alert') ?>></label>
            <div class="form-grid three">
                <div class="field"><label><?= e(t('Session Timeout (minutes)')) ?></label><input type="number" name="session_timeout_minutes" value="<?= e($s['session_timeout_minutes']) ?>"><?= field_error('session_timeout_minutes') ?></div>
                <div class="field"><label><?= e(t('Max Login Attempts')) ?></label><input type="number" name="max_login_attempts" value="<?= e($s['max_login_attempts']) ?>"><?= field_error('max_login_attempts') ?></div>
                <div class="field"><label><?= e(t('Lockout duration (minutes)')) ?></label><input type="number" name="lockout_minutes" value="<?= e($s['lockout_minutes']) ?>"><?= field_error('lockout_minutes') ?></div>
            </div>
            <button class="btn btn-primary"><?= icon('shield-check') ?><?= e(t('Update Security')) ?></button>
        </form>
    <?php elseif ($tab === 'pricing'): ?>
        <div class="card-head"><h2><?= e(t('Pricing Rules')) ?></h2><span class="season-pill season-<?= e($season) ?>"><?= e(t('Current')) ?>: <?= e(Pricing::label($season)) ?></span></div>
        <form method="post" action="<?= e(url('settings/save')) ?>" data-ajax novalidate>
            <?= csrf_field() ?><input type="hidden" name="section" value="pricing">
            <div class="form-grid">
                <div class="field"><label><?= e(t('Season mode')) ?></label><select name="season_mode"><option value="auto"<?= selected($s['season_mode'], 'auto') ?>><?= e(t('Automatic (by rental month)')) ?></option><option value="manual"<?= selected($s['season_mode'], 'manual') ?>><?= e(t('Manual')) ?></option></select></div>
                <div class="field"><label><?= e(t('Manual season')) ?></label><select name="current_season"><?php foreach (Pricing::SEASONS as $sn): ?><option value="<?= $sn ?>"<?= selected($s['current_season'], $sn) ?>><?= e(status_meta($sn)[0]) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label><?= e(t('Harvest months')) ?></label><input name="harvest_months" value="<?= e($s['harvest_months']) ?>" placeholder="4,5,11,12"><?= field_error('harvest_months') ?></div>
                <div class="field"><label><?= e(t('Off-season months')) ?></label><input name="off_season_months" value="<?= e($s['off_season_months']) ?>" placeholder="1,2,7"><?= field_error('off_season_months') ?></div>
                <div class="field"><label><?= e(t('Harvest Season Increase (%)')) ?></label><input type="number" step="0.1" name="harvest_increase_percent" value="<?= e($s['harvest_increase_percent']) ?>"><?= field_error('harvest_increase_percent') ?></div>
                <div class="field"><label><?= e(t('Off-season Discount (%)')) ?></label><input type="number" step="0.1" name="off_season_discount_percent" value="<?= e($s['off_season_discount_percent']) ?>"><?= field_error('off_season_discount_percent') ?></div>
                <div class="field"><label><?= e(t('Minimum Rental Price (৳)')) ?></label><input type="number" step="1" name="min_rental_price" value="<?= e($s['min_rental_price']) ?>"><?= field_error('min_rental_price') ?></div>
                <div class="field"><label><?= e(t('Reason for change (audit)')) ?></label><input name="note"></div>
            </div>
            <button class="btn btn-primary"><?= icon('check') ?><?= e(t('Save pricing rules')) ?></button>
        </form>
        <h3 class="sub-title"><?= e(t('Price preview')) ?></h3>
        <div class="table-wrap"><table class="table compact"><thead><tr><th><?= e(t('Equipment')) ?></th><th><?= e(t('Base')) ?></th><th><?= e(t('Normal')) ?></th><th><?= e(t('Harvest')) ?></th><th><?= e(t('Off-season')) ?></th></tr></thead><tbody>
            <?php foreach ((new Equipment())->search([], 1, 5)[0] as $eq): $b = (float)$eq['price_per_day']; $min = (float)$s['min_rental_price']; ?>
                <tr><td><?= e($eq['name']) ?></td><td><?= money($b) ?></td><td><?= money(max($min, $b)) ?></td><td><?= money(max($min, round($b * (1 + (float)$s['harvest_increase_percent'] / 100), 2))) ?></td><td><?= money(max($min, round($b * (1 - (float)$s['off_season_discount_percent'] / 100), 2))) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <h3 class="sub-title"><?= e(t('Price change audit log')) ?></h3>
        <div class="table-wrap"><table class="table compact"><thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Item')) ?></th><th><?= e(t('Field')) ?></th><th><?= e(t('Old')) ?></th><th><?= e(t('New')) ?></th><th><?= e(t('By')) ?></th><th><?= e(t('Note')) ?></th></tr></thead><tbody>
            <?php foreach ($priceLogs as $l): ?><tr><td><?= e(fmt_datetime($l['created_at'])) ?></td><td><?= e($l['equipment_name'] ?? t('Pricing rules')) ?></td><td><code><?= e($l['field']) ?></code></td><td><?= e($l['old_value'] ?? '—') ?></td><td><strong><?= e($l['new_value'] ?? '—') ?></strong></td><td><?= e($l['changed_by_name'] ?? '—') ?></td><td><small><?= e($l['note'] ?? '') ?></small></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php elseif ($tab === 'notifications'): ?>
        <h2 class="card-title"><?= e(t('Notification Settings')) ?></h2>
        <form method="post" action="<?= e(url('settings/save')) ?>" data-ajax>
            <?= csrf_field() ?><input type="hidden" name="section" value="notifications">
            <?php foreach (['email_notifications' => ['Email Notification', 'Send booking, payment and status emails to users.'], 'payment_failure_alert' => ['Payment Failure Alert', 'Notify administrators when a payment fails.'], 'delivery_completion_alert' => ['Delivery Completion Alert', 'Notify administrators when a delivery is completed.'], 'complaint_ticket_alert' => ['Complaint Ticket Alert', 'Notify staff about new complaints.'], 'maintenance_alert' => ['Maintenance Alert', 'Notify staff when equipment needs maintenance.']] as $k => [$l, $d]): ?>
                <label class="switch-row"><span><strong><?= e(t($l)) ?></strong><small><?= e(t($d)) ?></small></span><input type="checkbox" name="<?= $k ?>" value="1"<?= $on($k) ?>></label>
            <?php endforeach; ?>
            <button class="btn btn-primary"><?= icon('check') ?><?= e(t('Save Preferences')) ?></button>
        </form>
    <?php elseif ($tab === 'payments'): ?>
        <h2 class="card-title"><?= e(t('Payments & Booking')) ?></h2>
        <form method="post" action="<?= e(url('settings/save')) ?>" data-ajax novalidate>
            <?= csrf_field() ?><input type="hidden" name="section" value="payments">
            <?php foreach (Payment::METHODS as $k => $l): ?><label class="switch-row"><span><strong><?= e(t($l)) ?></strong><small><?= e(t('Allow farmers to pay with this method.')) ?></small></span><input type="checkbox" name="method_<?= $k ?>" value="1"<?= $on('method_' . $k) ?>></label><?php endforeach; ?>
            <?= field_error('method_cash') ?>
            <label class="switch-row"><span><strong><?= e(t('Block booking of equipment that needs maintenance')) ?></strong><small><?= e(t('Equipment marked "Needs Maintenance" cannot be booked.')) ?></small></span><input type="checkbox" name="block_needs_maintenance" value="1"<?= $on('block_needs_maintenance') ?>></label>
            <div class="field"><label><?= e(t('Default service threshold for new equipment (hours)')) ?></label><input type="number" name="default_usage_threshold" value="<?= e($s['default_usage_threshold']) ?>"><?= field_error('default_usage_threshold') ?></div>
            <button class="btn btn-primary"><?= icon('check') ?><?= e(t('Save Changes')) ?></button>
        </form>
    <?php elseif ($tab === 'coupons'): ?>
        <div class="card-head"><h2><?= e(t('Coupons')) ?></h2><button class="btn btn-primary btn-sm" type="button" data-modal-open="#couponModal" data-coupon-new><?= icon('plus') ?><?= e(t('Add coupon')) ?></button></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th><?= e(t('Code')) ?></th><th><?= e(t('Discount')) ?></th><th><?= e(t('Min amount')) ?></th><th><?= e(t('Valid')) ?></th><th><?= e(t('Used')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
            <tbody><?php foreach ($coupons as $c): $expired = $c['expires_at'] && $c['expires_at'] < today(); ?>
                <tr data-row="coupon-<?= (int)$c['id'] ?>"><td><strong><?= e($c['code']) ?></strong><small class="block muted"><?= e($c['description'] ?? '') ?></small></td>
                    <td><?= $c['discount_type'] === 'percent' ? e((float)$c['discount_value'] . '%') : money($c['discount_value']) ?><?= $c['max_discount'] ? '<small class="block muted">' . e(t('max')) . ' ' . money($c['max_discount']) . '</small>' : '' ?></td>
                    <td><?= money($c['min_amount']) ?></td><td><small><?= e(fmt_date($c['starts_at'])) ?> – <?= e(fmt_date($c['expires_at'])) ?></small></td>
                    <td><?= (int)$c['used_count'] ?><?= $c['usage_limit'] ? ' / ' . (int)$c['usage_limit'] : '' ?></td>
                    <td><?= $expired ? '<span class="badge badge-gray">' . e(t('Expired')) . '</span>' : badge($c['status']) ?></td>
                    <td class="nowrap"><button class="btn btn-light btn-xs" type="button" data-modal-open="#couponModal" data-coupon-edit='<?= e(json_encode($c)) ?>'><?= icon('edit', 'icon-xs') ?></button>
                        <form class="inline" method="post" action="<?= e(url('settings/coupon-delete')) ?>" data-ajax data-remove-row="coupon-<?= (int)$c['id'] ?>" data-confirm="<?= e(t('Delete this coupon?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger-soft btn-xs"><?= icon('trash', 'icon-xs') ?></button></form></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <div class="modal" id="couponModal" role="dialog" aria-modal="true" hidden>
            <form class="modal-card" method="post" action="<?= e(url('settings/coupon-save')) ?>" data-ajax data-coupon-form novalidate>
                <div class="modal-head"><h3 data-modal-title><?= e(t('Add coupon')) ?></h3><button type="button" class="btn-icon" data-modal-close>×</button></div>
                <div class="modal-body form-grid">
                    <?= csrf_field() ?><input type="hidden" name="id" value="">
                    <div class="field"><label><?= e(t('Code')) ?></label><input name="code" required><?= field_error('code') ?></div>
                    <div class="field"><label><?= e(t('Description')) ?></label><input name="description"></div>
                    <div class="field"><label><?= e(t('Discount type')) ?></label><select name="discount_type"><option value="percent"><?= e(t('Percent (%)')) ?></option><option value="fixed"><?= e(t('Fixed amount (৳)')) ?></option></select></div>
                    <div class="field"><label><?= e(t('Discount value')) ?></label><input type="number" step="0.01" name="discount_value" required><?= field_error('discount_value') ?></div>
                    <div class="field"><label><?= e(t('Minimum amount')) ?></label><input type="number" step="0.01" name="min_amount" value="0"></div>
                    <div class="field"><label><?= e(t('Maximum discount')) ?></label><input type="number" step="0.01" name="max_discount"></div>
                    <div class="field"><label><?= e(t('Starts')) ?></label><input type="date" name="starts_at"></div>
                    <div class="field"><label><?= e(t('Expires')) ?></label><input type="date" name="expires_at"><?= field_error('expires_at') ?></div>
                    <div class="field"><label><?= e(t('Usage limit')) ?></label><input type="number" name="usage_limit"></div>
                    <div class="field"><label><?= e(t('Status')) ?></label><select name="status"><option value="active"><?= e(t('Active')) ?></option><option value="inactive"><?= e(t('Inactive')) ?></option></select></div>
                </div>
                <div class="modal-actions"><button type="button" class="btn btn-light" data-modal-close><?= e(t('Cancel')) ?></button><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
            </form>
        </div>
    <?php elseif ($tab === 'accounts'): ?>
        <h2 class="card-title"><?= e(t('Account Management')) ?></h2>
        <div class="account-actions">
            <form class="sub-card" method="post" action="<?= e(url('user/status')) ?>" data-ajax data-confirm="<?= e(t('Deactivate this account?')) ?>">
                <?= csrf_field() ?><input type="hidden" name="status" value="suspended">
                <h3><?= icon('user') ?><?= e(t('Deactivate User Account')) ?></h3>
                <select name="id" required><option value=""><?= e(t('Select user')) ?></option><?php foreach ($users as $u): if ($u['status'] !== 'active') continue; ?><option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?> (<?= e(role_label($u['role'])) ?>)</option><?php endforeach; ?></select>
                <button class="btn btn-danger-soft btn-sm"><?= e(t('Deactivate')) ?></button>
            </form>
            <form class="sub-card" method="post" action="<?= e(url('user/reset-password')) ?>" data-ajax>
                <?= csrf_field() ?>
                <h3><?= icon('key') ?><?= e(t('Reset User Password')) ?></h3>
                <select name="id" required><option value=""><?= e(t('Select user')) ?></option><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?> — <?= e($u['email']) ?></option><?php endforeach; ?></select>
                <button class="btn btn-outline btn-sm"><?= e(t('Send reset link')) ?></button>
            </form>
            <div class="sub-card">
                <h3><?= icon('download') ?><?= e(t('Export System Logs')) ?></h3>
                <p class="muted small"><?= e(t('Download the security audit log (logins, lockouts, changes) as CSV.')) ?></p>
                <a class="btn btn-outline btn-sm" href="<?= e(url('settings/export-logs')) ?>"><?= e(t('Export CSV')) ?></a>
            </div>
            <div class="sub-card">
                <h3><?= icon('users') ?><?= e(t('All users')) ?></h3>
                <p class="muted small"><?= e(t('Create staff accounts and manage every user.')) ?></p>
                <a class="btn btn-outline btn-sm" href="<?= e(url('user/index')) ?>"><?= e(t('Open User Management')) ?></a>
            </div>
        </div>
    <?php elseif ($tab === 'logs'): ?>
        <div class="card-head"><h2><?= e(t('System Logs')) ?></h2><a class="btn btn-outline btn-sm" href="<?= e(url('settings/export-logs')) ?>"><?= icon('download') ?><?= e(t('Export CSV')) ?></a></div>
        <form class="toolbar" method="get" action="index.php">
            <input type="hidden" name="r" value="settings/index"><input type="hidden" name="tab" value="logs">
            <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search email, IP or details')) ?>"></div>
            <select name="event" onchange="this.form.submit()"><option value=""><?= e(t('All events')) ?></option><?php foreach ($events as $ev): ?><option<?= selected($f['event'], $ev['event']) ?>><?= e($ev['event']) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary"><?= e(t('Search')) ?></button>
        </form>
        <div class="table-wrap"><table class="table compact">
            <thead><tr><th><?= e(t('Time')) ?></th><th><?= e(t('Event')) ?></th><th><?= e(t('User')) ?></th><th>IP</th><th><?= e(t('Details')) ?></th></tr></thead>
            <tbody><?php foreach ($logs as $l): $danger = in_array($l['event'], ['login_failed', 'account_locked', 'suspicious_login', 'suspicious_ip', '2fa_failed'], true); ?>
                <tr class="<?= $danger ? 'row-warn' : '' ?>"><td class="nowrap"><?= e(fmt_datetime($l['created_at'])) ?></td><td><span class="badge badge-<?= $danger ? 'red' : 'gray' ?>"><?= e($l['event']) ?></span></td><td><?= e($l['name'] ?? $l['email'] ?? '—') ?></td><td><?= e($l['ip']) ?></td><td><small><?= e($l['details']) ?></small></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php $meta = $meta; include __DIR__ . '/../partials/pagination.php'; ?>
    <?php endif; ?>
    </section>
</div>
