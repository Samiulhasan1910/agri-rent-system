<?php
declare(strict_types=1);

/** Farmer <-> employee chat with AJAX polling. */
class ChatController extends Controller
{
    private Message $messages;

    public function __construct()
    {
        $this->messages = new Message();
    }

    public function index(): void
    {
        $user = $this->auth();
        $tab = in_array($this->input('tab'), ['active', 'all', 'unread'], true) ? $this->input('tab') : ($user['role'] === 'farmer' ? 'all' : 'active');
        $contacts = $this->messages->contacts($user, $this->input('q'), $tab);
        $withId = $this->int('with');
        if (!$withId && $contacts) {
            $withId = (int)$contacts[0]['id'];
        }
        $other = $withId ? $this->messages->canChat($user, $withId) : null;
        $conversation = [];
        if ($other) {
            $this->messages->markRead((int)$user['id'], (int)$other['id']);
            $conversation = $this->messages->conversation((int)$user['id'], (int)$other['id']);
        }
        if ($this->wantsPartial()) {
            $this->partial('chat/_contacts', ['contacts' => $contacts, 'other' => $other, 'tab' => $tab]);
        }
        $this->view('chat/index', [
            'title' => $user['role'] === 'farmer' ? t('Chat with Employee') : t('Chat With Farmers'),
            'contacts' => $contacts,
            'other' => $other,
            'conversation' => $conversation,
            'user' => $user,
            'tab' => $tab,
            'q' => $this->input('q'),
        ]);
    }

    public function send(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $other = $this->messages->canChat($user, $this->int('to'));
        $text = trim($this->input('message'));
        if (!$other) {
            $this->fail(t('You cannot message this user.'));
        }
        if ($text === '') {
            $this->fail(t('Message cannot be empty.'), ['message' => t('Message cannot be empty.')]);
        }
        $message = $this->messages->send((int)$user['id'], (int)$other['id'], mb_substr($text, 0, 2000));
        if (!$this->messages->hasEarlierUnread((int)$user['id'], (int)$other['id'], (int)$message['id'])) {
            Notifier::send((int)$other['id'], 'New message', t(':name sent you a message.', ['name' => $user['name']]), url('chat/index', ['with' => $user['id']]));
        }
        $this->json(['ok' => true, 'id' => (int)$message['id'], 'html' => View::partial('chat/_message', ['m' => $message, 'me' => (int)$user['id']])]);
    }

    public function poll(): void
    {
        $user = $this->auth();
        $other = $this->messages->canChat($user, $this->int('with'));
        if (!$other) {
            $this->json(['ok' => false, 'message' => t('Conversation not found.')], 404);
        }
        $rows = $this->messages->conversation((int)$user['id'], (int)$other['id'], $this->int('after'));
        $this->messages->markRead((int)$user['id'], (int)$other['id']);
        $html = '';
        $last = $this->int('after');
        foreach ($rows as $m) {
            $html .= View::partial('chat/_message', ['m' => $m, 'me' => (int)$user['id']]);
            $last = (int)$m['id'];
        }
        $this->json(['ok' => true, 'html' => $html, 'last' => $last]);
    }
}
