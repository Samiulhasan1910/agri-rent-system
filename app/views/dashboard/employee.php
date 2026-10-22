<div class="page-head">
    <div><h1><?= e(t('Employee Dashboard')) ?></h1><p class="muted"><?= e(t('Hello :name, here is what needs your attention today.', ['name' => explode(' ', $user['name'])[0]])) ?></p></div>
    <a class="btn btn-primary" href="<?= e(url('equipment/create')) ?>"><?= icon('plus') ?><?= e(t('Add Equipment')) ?></a>
</div>

<section class="card">
    <h2 class="card-title"><?= e(t('Search Section')) ?></h2>
    <form class="search-bar" method="get" action="index.php">
        <input type="hidden" name="r" value="dashboard/index">
        <div class="input-icon grow"><?= icon('search') ?><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('Search equipment')) ?>"></div>
        <select name="category"><option value=""><?= e(t('Category')) ?></option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"<?= selected($_GET['category'] ?? '', $c['id']) ?>><?= e(t($c['name'])) ?></option><?php endforeach; ?></select>
        <select name="status"><option value=""><?= e(t('Status')) ?></option><?php foreach (['available', 'unavailable', 'maintenance'] as $s): ?><option value="<?= $s ?>"<?= selected($_GET['status'] ?? '', $s) ?>><?= e(status_meta($s)[0]) ?></option><?php endforeach; ?></select>
        <input name="location" value="<?= e($_GET['location'] ?? '') ?>" placeholder="<?= e(t('Location')) ?>">
        <button class="btn btn-primary" type="submit"><?= e(t('Search')) ?></button>
    </form>
</section>

<section class="stats">
    <article class="stat"><span class="stat-icon"><?= icon('tractor') ?></span><div><strong><?= (int)$stats['equipment'] ?></strong><span><?= e(t('Total Equipment')) ?></span></div></article>
    <article class="stat"><span class="stat-icon blue"><?= icon('calendar-check') ?></span><div><strong><?= (int)$stats['active'] ?></strong><span><?= e(t('Active Rental')) ?></span></div></article>
    <article class="stat"><span class="stat-icon purple"><?= icon('history') ?></span><div><strong><?= (int)$stats['returned'] ?></strong><span><?= e(t('Returned Equipment')) ?></span></div></article>
    <article class="stat"><span class="stat-icon amber"><?= icon('clock') ?></span><div><strong><?= (int)$stats['pending'] ?></strong><span><?= e(t('Pending Requests')) ?></span></div></article>
</section>

<div class="dash-grid">
    <section class="card span-2">
        <div class="card-head"><h2><?= e(t('Equipment Management Table')) ?></h2><a class="btn btn-outline btn-sm" href="<?= e(url('equipment/manage')) ?>"><?= e(t('View All')) ?></a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th><?= e(t('Equipment')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Quantity')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Price')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($equipment as $e): ?>
                <tr><td><img class="thumb" src="<?= e(equipment_image($e)) ?>" alt="" onerror="this.onerror=null;this.src='assets/img/equipment/<?= e($e['category_icon']) ?>.svg'"></td>
                    <td><a href="<?= e(url('equipment/edit', ['id' => $e['id']])) ?>"><strong><?= e($e['name']) ?></strong></a><small class="block muted"><?= e($e['location']) ?></small></td>
                    <td><?= (int)$e['quantity'] ?></td><td><?= badge($e['health_status'] === 'needs_maintenance' ? 'needs_maintenance' : $e['status']) ?></td><td><?= money($e['price_per_day']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$equipment): ?><tr><td colspan="5" class="center muted"><?= e(t('No equipment found')) ?></td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= e(t('Maintenance Alert Section')) ?></h2><a class="btn-link" href="<?= e(url('maintenance/health')) ?>"><?= e(t('View All')) ?></a></div>
        <div class="list">
            <?php foreach ($alerts as $a): ?>
                <div class="list-item">
                    <span class="li-icon <?= $a['type'] === 'missing_data' ? 'gray' : 'red' ?>"><?= icon($a['type'] === 'missing_data' ? 'info' : 'wrench') ?></span>
                    <div><strong><?= e($a['equipment_name']) ?></strong><small><?= e(str_limit($a['message'], 70)) ?></small></div>
                    <small class="muted"><?= e(time_ago($a['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$alerts): ?><p class="muted center"><?= icon('check-circle', 'icon-xs') ?> <?= e(t('All equipment is healthy.')) ?></p><?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= e(t('Rental Request Panel')) ?></h2><a class="btn-link" href="<?= e(url('rental/index', ['status' => 'pending'])) ?>"><?= e(t('View All')) ?></a></div>
        <div class="list">
            <?php foreach ($requests as $b): ?>
                <div class="list-item request-item" data-row="booking-<?= (int)$b['id'] ?>">
                    <?= avatar(['name' => $b['farmer_name']], 'sm') ?>
                    <div><strong><?= e($b['farmer_name']) ?></strong><small><?= e($b['equipment_name']) ?> · <?= e(fmt_date($b['start_date'], 'd M')) ?>–<?= e(fmt_date($b['end_date'], 'd M')) ?></small></div>
                    <div class="row-actions">
                        <form method="post" action="<?= e(url('rental/status')) ?>" data-ajax data-remove-row="booking-<?= (int)$b['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="status" value="confirmed"><button class="btn btn-primary btn-xs" type="submit"><?= e(t('Accept')) ?></button></form>
                        <form method="post" action="<?= e(url('rental/status')) ?>" data-ajax data-remove-row="booking-<?= (int)$b['id'] ?>" data-prompt="<?= e(t('Reason for rejection')) ?>" data-prompt-field="note"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="status" value="rejected"><button class="btn btn-danger btn-xs" type="submit"><?= e(t('Reject')) ?></button></form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$requests): ?><p class="muted center"><?= e(t('No pending rental requests.')) ?></p><?php endif; ?>
        </div>
        <?php if ($extensions): ?>
            <h3 class="sub-title"><?= e(t('Extension Requests')) ?></h3>
            <?php foreach ($extensions as $x): ?>
                <div class="list-item" data-row="ext-<?= (int)$x['id'] ?>">
                    <span class="li-icon blue"><?= icon('calendar') ?></span>
                    <div><strong><?= e($x['booking_code']) ?> · <?= e($x['farmer_name']) ?></strong><small><?= e(t('to :date (+:d days, :cost)', ['date' => fmt_date($x['requested_end_date']), 'd' => $x['extra_days'], 'cost' => money($x['extra_cost'])])) ?></small></div>
                    <div class="row-actions">
                        <form method="post" action="<?= e(url('rental/extension')) ?>" data-ajax data-remove-row="ext-<?= (int)$x['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><input type="hidden" name="decision" value="approved"><button class="btn btn-primary btn-xs"><?= e(t('Approve')) ?></button></form>
                        <form method="post" action="<?= e(url('rental/extension')) ?>" data-ajax data-remove-row="ext-<?= (int)$x['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><input type="hidden" name="decision" value="rejected"><button class="btn btn-outline btn-xs"><?= e(t('Reject')) ?></button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= e(t('Return Inspection Section')) ?></h2><a class="btn-link" href="<?= e(url('inspection/index')) ?>"><?= e(t('View All')) ?></a></div>
        <?php if ($returns): $r = $returns[0]; ?>
            <p class="muted small"><?= e(t('Recently returned')) ?>: <?php foreach (array_slice($returns, 0, 3) as $x): ?><span class="chip-sm"><?= e($x['equipment_name']) ?></span><?php endforeach; ?></p>
            <form method="post" action="<?= e(url('inspection/store')) ?>" data-ajax data-reload class="stack">
                <?= csrf_field() ?>
                <select name="booking_id" aria-label="<?= e(t('Rental')) ?>"><?php foreach ($returns as $x): ?><option value="<?= (int)$x['id'] ?>"><?= e($x['booking_code']) ?> · <?= e($x['equipment_name']) ?> · <?= e($x['farmer_name']) ?></option><?php endforeach; ?></select>
                <select name="condition" aria-label="<?= e(t('Condition')) ?>"><option value=""><?= e(t('Condition')) ?></option><?php foreach (Inspection::CONDITIONS as $k => $label): ?><option value="<?= $k ?>"><?= e(t($label)) ?></option><?php endforeach; ?></select>
                <input name="usage_hours" type="number" min="0" step="0.5" placeholder="<?= e(t('Usage hours (meter)')) ?>">
                <input name="comments" placeholder="<?= e(t('Comment')) ?>">
                <button class="btn btn-primary btn-sm" type="submit"><?= e(t('Submit')) ?></button>
            </form>
        <?php else: ?><p class="muted center"><?= e(t('No equipment waiting for inspection.')) ?></p><?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= e(t('Farmer Verification Panel')) ?></h2><a class="btn-link" href="<?= e(url('verification/index')) ?>"><?= e(t('View All')) ?></a></div>
        <div class="list">
            <?php foreach ($farmers as $f): ?>
                <div class="list-item" data-row="user-<?= (int)$f['id'] ?>">
                    <?= avatar($f, 'sm') ?>
                    <div><strong><?= e($f['name']) ?></strong><small><?= e(t('Farmer')) ?> · <?= e($f['address']) ?></small></div>
                    <div class="row-actions">
                        <form method="post" action="<?= e(url('verification/decide')) ?>" data-ajax data-remove-row="user-<?= (int)$f['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="decision" value="approved"><button class="btn btn-primary btn-xs"><?= e(t('Accept')) ?></button></form>
                        <form method="post" action="<?= e(url('verification/decide')) ?>" data-ajax data-remove-row="user-<?= (int)$f['id'] ?>" data-prompt="<?= e(t('Reason for rejection')) ?>" data-prompt-field="note"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="decision" value="rejected"><button class="btn btn-danger btn-xs"><?= e(t('Reject')) ?></button></form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$farmers): ?><p class="muted center"><?= e(t('No farmers waiting for verification.')) ?></p><?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= e(t('Emergency Request Panel')) ?></h2><a class="btn-link" href="<?= e(url('emergency/index')) ?>"><?= e(t('View All')) ?></a></div>
        <div class="table-wrap"><table class="table compact">
            <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Equipment')) ?></th><th><?= e(t('Status')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($emergencies as $em): ?><tr><td><?= e($em['farmer_name']) ?></td><td><?= e($em['category_name']) ?></td><td><?= badge($em['status']) ?></td></tr><?php endforeach; ?>
            <?php if (!$emergencies): ?><tr><td colspan="3" class="center muted"><?= e(t('No emergency requests.')) ?></td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</div>
