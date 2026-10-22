<div class="page-head"><div><h1><?= e($title) ?></h1></div></div>
<section class="chat card">
    <aside class="chat-contacts">
        <form class="chat-search" method="get" action="index.php" data-live data-target="#contactList">
            <input type="hidden" name="r" value="chat/index">
            <?php if ($other): ?><input type="hidden" name="with" value="<?= (int)$other['id'] ?>"><?php endif; ?>
            <input type="hidden" name="tab" value="<?= e($tab) ?>" data-tab-input>
            <div class="input-icon"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Search for chats...')) ?>"></div>
            <div class="chips small">
                <?php foreach (['active' => 'Active chats', 'unread' => 'Unread', 'all' => 'All contacts'] as $k => $l): ?><button type="button" class="chip <?= $tab === $k ? 'active' : '' ?>" data-tab="<?= $k ?>"><?= e(t($l)) ?></button><?php endforeach; ?>
            </div>
        </form>
        <div id="contactList"><?php include __DIR__ . '/_contacts.php'; ?></div>
    </aside>
    <div class="chat-main">
        <?php if ($other): ?>
            <header class="chat-head"><?= avatar($other, 'sm') ?><div><strong><?= e($other['name']) ?></strong><small class="text-green"><?= e(role_label($other['role'])) ?></small></div></header>
            <div class="chat-messages" id="chatMessages" data-chat-with="<?= (int)$other['id'] ?>" data-last="<?= $conversation ? (int)end($conversation)['id'] : 0 ?>">
                <?php if (!$conversation): ?><p class="muted center chat-empty"><?= e(t('No messages yet. Say hello!')) ?></p><?php endif; ?>
                <?php foreach ($conversation as $m) { echo View::partial('chat/_message', ['m' => $m, 'me' => (int)$user['id']]); } ?>
            </div>
            <form class="chat-form" method="post" action="<?= e(url('chat/send')) ?>" data-chat-form>
                <?= csrf_field() ?><input type="hidden" name="to" value="<?= (int)$other['id'] ?>">
                <input name="message" placeholder="<?= e(t('Type your message...')) ?>" autocomplete="off" maxlength="2000" required>
                <button class="btn btn-primary btn-icon-only" type="submit" aria-label="<?= e(t('Send')) ?>"><?= icon('send') ?></button>
            </form>
        <?php else: ?>
            <?php $icon = 'message'; $heading = t('Select a conversation'); $text = t('Choose a contact to start chatting.'); include __DIR__ . '/../partials/empty.php'; ?>
        <?php endif; ?>
    </div>
</section>
