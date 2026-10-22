<div class="dropdown lang-switch">
    <button class="btn btn-outline btn-sm" type="button" data-dropdown aria-haspopup="true" aria-expanded="false">
        <?= icon('globe') ?><span><?= Lang::get() === 'bn' ? 'বাংলা' : 'English' ?></span><?= icon('chevron-down', 'icon-xs') ?>
    </button>
    <div class="dropdown-menu" role="menu">
        <a role="menuitem" class="<?= Lang::get() === 'en' ? 'active' : '' ?>" href="<?= e(url('lang/switch', ['to' => 'en'])) ?>">English</a>
        <a role="menuitem" class="<?= Lang::get() === 'bn' ? 'active' : '' ?>" href="<?= e(url('lang/switch', ['to' => 'bn'])) ?>">বাংলা (Bangla)</a>
    </div>
</div>
