<?php
declare(strict_types=1);

class EquipmentController extends Controller
{
    private Equipment $equipment;

    public function __construct()
    {
        $this->equipment = new Equipment();
    }

    /* ==============================================================
     * Farmer catalogue
     * ============================================================== */
    public function index(): void
    {
        $user = $this->auth();
        $filters = [
            'q' => $this->input('q'),
            'category' => $this->int('category'),
            'location' => $this->input('location'),
            'min_price' => $this->input('min_price'),
            'max_price' => $this->input('max_price'),
            'availability' => in_array($this->input('availability'), ['available', 'unavailable'], true) ? $this->input('availability') : '',
            'sort' => array_key_exists($this->input('sort'), Equipment::SORTS) ? $this->input('sort') : 'newest',
        ];
        $error = '';
        foreach (['min_price', 'max_price'] as $key) {
            if ($filters[$key] !== '' && (!is_numeric($filters[$key]) || (float)$filters[$key] < 0)) {
                $error = t('Enter a valid non-negative price.');
                $filters[$key] = '';
            }
        }
        if ($filters['min_price'] !== '' && $filters['max_price'] !== '' && (float)$filters['min_price'] > (float)$filters['max_price']) {
            $error = t('Maximum price must be at least the minimum price.');
        }
        [$rows, $meta] = $error ? [[], paginate(0, 1)] : $this->equipment->search($filters, max(1, $this->int('page', 1)), 9);
        $data = [
            'rows' => $rows,
            'meta' => $meta,
            'filters' => $filters,
            'error' => $error,
            'free' => Availability::freeUnits($rows, today(), today()),
            'wishlist' => $user['role'] === 'farmer' ? (new Wishlist())->ids((int)$user['id']) : [],
            'systemEmpty' => $this->equipment->countListed() === 0,
            'season' => Pricing::seasonFor(),
        ];
        if ($this->wantsPartial()) {
            $this->json(['ok' => true, 'html' => View::partial('equipment/_results', $data), 'count' => $meta['total']]);
        }
        $this->view('equipment/index', $data + [
            'title' => t('Browse Equipment'),
            'categories' => $this->equipment->categoryTiles(),
            'locations' => array_keys(Emergency::DISTRICTS),
        ]);
    }

    public function show(): void
    {
        $user = $this->auth();
        $item = $this->equipment->findDetailed($this->int('id'));
        if (!$item || ($item['status'] === 'inactive' && $user['role'] === 'farmer')) {
            throw new HttpException(404, t('Equipment not found.'));
        }
        $start = valid_date($this->input('start_date')) ? $this->input('start_date') : '';
        $end = valid_date($this->input('end_date')) ? $this->input('end_date') : '';
        $this->view('equipment/show', [
            'title' => $item['name'],
            'item' => $item,
            'user' => $user,
            'reviews' => $this->equipment->reviews((int)$item['id']),
            'similar' => $this->equipment->similar($item),
            'saved' => $user['role'] === 'farmer' && (new Wishlist())->has((int)$user['id'], (int)$item['id']),
            'blockReason' => Availability::blockReason($item),
            'freeToday' => Availability::freeUnits([$item], today(), today())[(int)$item['id']] ?? 0,
            'price' => Pricing::tryQuote($item['price_per_day'], $start ?: today()),
            'start' => $start,
            'end' => $end,
            'maintenance' => (new Maintenance())->forEquipment((int)$item['id']),
            'contact' => (new User())->supportContact(),
        ]);
    }

    /** AJAX: price & availability for a rental period. */
    public function quote(): void
    {
        $this->auth();
        $item = $this->equipment->find($this->int('equipment_id'));
        if (!$item || $item['status'] === 'inactive') {
            $this->json(['ok' => false, 'message' => t('Equipment not found.')], 404);
        }
        try {
            $quote = (new Booking())->quote($item, $this->input('start_date'), $this->input('end_date'));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (DomainException $e) {
            $this->json(['ok' => false, 'message' => t('Unable to calculate price. Please try again.') . ' ' . $e->getMessage()], 422);
        }
        $this->json(['ok' => true] + $quote);
    }

    /** AJAX: compact catalogue cached by the browser for offline browsing. */
    public function catalog(): void
    {
        $this->auth();
        $rows = array_map(static function (array $e): array {
            $price = Pricing::tryQuote($e['price_per_day']);
            return [
                'id' => (int)$e['id'],
                'name' => $e['name'],
                'specs' => $e['specs'],
                'category' => $e['category_name'],
                'category_id' => (int)$e['category_id'],
                'location' => $e['location'],
                'price' => $price ? $price['price'] : null,
                'image' => equipment_image($e),
                'available' => Availability::blockReason($e) === null,
                'rating' => round((float)$e['rating'], 1),
            ];
        }, $this->equipment->catalogue());
        $this->json(['ok' => true, 'synced_at' => date('c'), 'items' => $rows]);
    }

    /* ==============================================================
     * Staff management
     * ============================================================== */
    public function manage(): void
    {
        $this->role('employee', 'admin');
        $filters = [
            'q' => $this->input('q'),
            'category' => $this->int('category'),
            'status' => in_array($this->input('status'), ['available', 'unavailable', 'maintenance'], true) ? $this->input('status') : '',
            'location' => $this->input('location'),
            'sort' => 'newest',
        ];
        [$rows, $meta] = $this->equipment->search($filters, max(1, $this->int('page', 1)), 10);
        $data = ['rows' => $rows, 'meta' => $meta, 'filters' => $filters];
        if ($this->wantsPartial()) {
            $this->partial('equipment/_table', $data);
        }
        $this->view('equipment/manage', $data + [
            'title' => t('Manage Equipment'),
            'categories' => (new Category())->active(),
            'locations' => array_keys(Emergency::DISTRICTS),
        ]);
    }

    public function create(): void
    {
        $this->role('employee', 'admin');
        $this->form(null);
    }

    public function edit(): void
    {
        $this->role('employee', 'admin');
        $item = $this->equipment->find($this->int('id'));
        if (!$item) {
            throw new HttpException(404, t('Equipment not found.'));
        }
        $this->form($item);
    }

    private function form(?array $item): void
    {
        $this->view('equipment/form', [
            'title' => $item ? t('Update Equipment') : t('Add New Equipment'),
            'item' => $item,
            'categories' => (new Category())->active(),
            'locations' => array_keys(Emergency::DISTRICTS),
        ]);
    }

    public function store(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        [$data, $errors] = $this->validated(true);
        if ($errors) {
            $this->fail(t('All fields are required and must be valid.'), $errors);
        }
        try {
            $data['image'] = Upload::image('image', 'equipment');
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage(), ['image' => $e->getMessage()]);
        }
        try {
            $data['added_by'] = (int)$user['id'];
            $data['last_service_date'] = today();
            $id = $this->equipment->create($data);
            (new PriceLog())->log($id, 'price_per_day', null, $data['price_per_day'], 'Initial price', (int)$user['id']);
        } catch (Throwable $e) {
            Upload::delete($data['image'] ?? null);
            error_log($e->getMessage());
            $this->fail(t('Failed to save equipment. Please try again.'));
        }
        $this->success(t('Equipment added successfully'), url('equipment/manage'));
    }

    public function update(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $item = $this->equipment->find($this->int('id'));
        if (!$item) {
            $this->fail(t('Equipment not found.'));
        }
        [$data, $errors] = $this->validated(false);
        if ($errors) {
            $this->fail(t('Failed to update equipment. Please ensure all fields are valid and try again.'), $errors);
        }
        if (Upload::has('image')) {
            try {
                $data['image'] = Upload::image('image', 'equipment');
            } catch (RuntimeException $e) {
                $this->fail($e->getMessage(), ['image' => $e->getMessage()]);
            }
        }
        try {
            $this->equipment->edit((int)$item['id'], $data);
            if ((float)$item['price_per_day'] !== (float)$data['price_per_day']) {
                (new PriceLog())->log((int)$item['id'], 'price_per_day', $item['price_per_day'], $data['price_per_day'], $this->input('price_note') ?: 'Price updated', (int)$user['id']);
            }
            if (isset($data['image']) && str_starts_with((string)$item['image'], 'uploads/')) {
                Upload::delete($item['image']);
            }
            Health::evaluate((int)$item['id']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to update equipment. Please ensure all fields are valid and try again.'));
        }
        $this->success(t('Equipment updated successfully.'), url('equipment/manage'));
    }

    public function destroy(): void
    {
        $this->role('admin');
        $this->requirePost();
        $id = $this->int('id');
        if ($this->equipment->activeRentals($id) > 0) {
            $this->fail(t('Failed to delete equipment.') . ' ' . t('It has active rentals.'));
        }
        try {
            if (!$this->equipment->remove($id)) {
                throw new RuntimeException('not found');
            }
        } catch (Throwable $e) {
            $this->fail(t('Failed to delete equipment.'));
        }
        $this->success(t('Equipment removed successfully.'), null, ['removed' => $id]);
    }

    /** Shared validation for add/update. Returns [data, errors]. */
    private function validated(bool $creating): array
    {
        $v = new Validator($_POST);
        $v->required('name', t('Equipment name is required'))->max('name', 150, t('Equipment name is too long'))
            ->required('category_id', t('Please select a category'))
            ->required('price_per_day', t('Rental price is required'))
            ->numeric('price_per_day', t('Rental price must be a valid number'))
            ->check('price_per_day', !is_numeric($v->value('price_per_day')) || (float)$v->value('price_per_day') > 0, t('Rental price must be a valid number'))
            ->required('quantity', t('Quantity is required'))
            ->check('quantity', $v->value('quantity') === '' || (preg_match('/^-?\d+$/', $v->value('quantity')) && (int)$v->value('quantity') > 0), t('Quantity must be a positive number'))
            ->required('location', t('Location is required'))
            ->required('description', t('Description is required'))
            ->numeric('usage_threshold_hours', t('Enter a valid number of hours'))
            ->numeric('service_interval_days', t('Enter a valid number of days'));
        if ($creating && !Upload::has('image')) {
            $v->addError('image', t('Equipment image is required'));
        }
        if ($v->value('category_id') !== '' && !(new Category())->find((int)$v->value('category_id'))) {
            $v->addError('category_id', t('Please select a category'));
        }
        $status = in_array($v->value('status'), ['available', 'unavailable', 'maintenance'], true) ? $v->value('status') : 'available';
        $location = $v->value('location');
        [$lat, $lng] = Emergency::DISTRICTS[$location] ?? [null, null];
        if (is_numeric($v->value('latitude')) && is_numeric($v->value('longitude'))) {
            $lat = (float)$v->value('latitude');
            $lng = (float)$v->value('longitude');
        }
        $data = [
            'name' => $v->value('name'),
            'category_id' => (int)$v->value('category_id'),
            'specs' => $v->value('specs') !== '' ? mb_substr($v->value('specs'), 0, 150) : null,
            'description' => $v->value('description'),
            'price_per_day' => round((float)$v->value('price_per_day'), 2),
            'quantity' => (int)$v->value('quantity'),
            'location' => mb_substr($location, 0, 150),
            'latitude' => $lat,
            'longitude' => $lng,
            'owner_name' => $v->value('owner_name') !== '' ? mb_substr($v->value('owner_name'), 0, 120) : null,
            'owner_phone' => $v->value('owner_phone') !== '' ? mb_substr($v->value('owner_phone'), 0, 30) : null,
            'status' => $status,
            'usage_threshold_hours' => max(10, (int)($v->value('usage_threshold_hours') ?: setting('default_usage_threshold', 250))),
            'service_interval_days' => max(7, (int)($v->value('service_interval_days') ?: 120)),
        ];
        return [$data, $v->errors()];
    }
}
