<div class="modal" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" hidden>
    <div class="modal-card modal-sm">
        <div class="modal-icon" data-confirm-icon><?= icon('alert') ?></div>
        <h3 id="confirmTitle" data-confirm-title><?= e(t('Are you sure?')) ?></h3>
        <p data-confirm-text></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-confirm-cancel><?= e(t('Cancel')) ?></button>
            <button type="button" class="btn btn-danger" data-confirm-ok><?= e(t('Confirm')) ?></button>
        </div>
    </div>
</div>
