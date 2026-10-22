<?php
declare(strict_types=1);

class NotificationController extends Controller
{
    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    public function index(): void
    {
        $user = $this->auth();
        $filter = $this->input('filter') === 'unread' ? 'unread' : 'all';
        [$rows, $meta] = $this->notifications->paginateList((int)$user['id'], $filter, max(1, $this->int('page', 1)));
        $this->view('notification/index', ['title' => t('Notifications'), 'rows' => $rows, 'meta' => $meta, 'filter' => $filter]);
    }

    /** AJAX: badge counts + dropdown items (polled every 30 seconds). */
    public function poll(): void
    {
        $user = $this->auth();
        session_write_close();
        $this->json([
            'ok' => true,
            'unread' => $this->notifications->unreadCount((int)$user['id']),
            'messages' => (new Message())->unreadCount((int)$user['id']),
            'html' => View::partial('notification/_dropdown', ['rows' => $this->notifications->latest((int)$user['id'], 6)]),
        ]);
    }

    /** Opens a notification: marks it read and follows its internal link. */
    public function open(): void
    {
        $user = $this->auth();
        $row = $this->notifications->markRead($this->int('id'), (int)$user['id']);
        $link = (string)($row['link'] ?? '');
        $this->redirectTo(str_starts_with($link, 'index.php') ? $link : url('notification/index'));
    }

    public function readAll(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $this->notifications->markAllRead((int)$user['id']);
        $this->success(t('All notifications marked as read.'), null, ['unread' => 0]);
    }

    public function delete(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $this->notifications->remove($this->int('id'), (int)$user['id']);
        $this->success(t('Notification removed.'), null, ['removed' => $this->int('id')]);
    }
}
