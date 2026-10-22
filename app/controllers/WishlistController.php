<?php
declare(strict_types=1);

class WishlistController extends Controller
{
    private Wishlist $wishlist;

    public function __construct()
    {
        $this->wishlist = new Wishlist();
    }

    public function index(): void
    {
        $user = $this->role('farmer');
        $availability = in_array($this->input('availability'), ['available', 'unavailable'], true) ? $this->input('availability') : '';
        try {
            $rows = $this->wishlist->items((int)$user['id'], $this->input('q'), $availability);
        } catch (Throwable $e) {
            $this->fail(t('Failed to load saved equipment. Please try again.'), [], url('dashboard/index'));
        }
        $data = ['rows' => $rows, 'free' => Availability::freeUnits($rows, today(), today()), 'q' => $this->input('q'), 'availability' => $availability];
        if ($this->wantsPartial()) {
            $this->partial('wishlist/_list', $data);
        }
        $this->view('wishlist/index', $data + ['title' => t('Wishlist')]);
    }

    public function add(): void
    {
        $user = $this->role('farmer');
        $this->requirePost();
        $id = $this->int('equipment_id');
        $equipment = (new Equipment())->find($id);
        if (!$equipment || $equipment['status'] === 'inactive') {
            $this->fail(t('Failed to save equipment. Please try again.'));
        }
        if ($this->wishlist->has((int)$user['id'], $id)) {
            $this->fail(t('Equipment is already saved.'), [], null, 409, ['saved' => true]);
        }
        try {
            $this->wishlist->add((int)$user['id'], $id);
        } catch (Throwable $e) {
            $this->fail(t('Failed to save equipment. Please try again.'));
        }
        $this->success(t('Action completed successfully.'), null, ['saved' => true, 'count' => $this->wishlist->count((int)$user['id'])]);
    }

    public function remove(): void
    {
        $user = $this->role('farmer');
        $this->requirePost();
        try {
            $removed = $this->wishlist->remove((int)$user['id'], $this->int('equipment_id'));
        } catch (Throwable $e) {
            $removed = 0;
        }
        if (!$removed) {
            $this->fail(t('Failed to remove saved equipment. Please try again.'));
        }
        $this->success(t('Action completed successfully.'), null, ['saved' => false, 'removed' => $this->int('equipment_id'), 'count' => $this->wishlist->count((int)$user['id'])]);
    }
}
