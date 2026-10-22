<?php
declare(strict_types=1);

/** Admin system settings: general, security, pricing rules, notifications, payment methods, coupons, logs. */
class SettingsController extends Controller
{
    private const TABS = ['general', 'security', 'pricing', 'notifications', 'payments', 'coupons', 'accounts', 'logs'];

    public function index(): void
    {
        $this->role('admin');
        $tab = in_array($this->input('tab'), self::TABS, true) ? $this->input('tab') : 'general';
        $data = [
            'title' => t('System Setting'),
            'tab' => $tab,
            's' => Settings::all(),
            'season' => Pricing::seasonFor(),
        ];
        if ($tab === 'pricing') {
            $data['priceLogs'] = (new PriceLog())->recent(15);
            $data['samples'] = array_slice((new Equipment())->options(), 0, 5);
        }
        if ($tab === 'coupons') {
            $data['coupons'] = (new Coupon())->listAll();
        }
        if ($tab === 'accounts') {
            $data['users'] = (new User())->activeList();
        }
        if ($tab === 'logs') {
            $f = ['q' => $this->input('q'), 'event' => $this->input('event')];
            [$data['logs'], $data['meta']] = (new SecurityLog())->paginateList($f, max(1, $this->int('page', 1)));
            $data['events'] = (new SecurityLog())->events();
            $data['f'] = $f;
        }
        $this->view('settings/index', $data);
    }

    public function save(): void
    {
        $user = $this->role('admin');
        $this->requirePost();
        $section = $this->input('section');
        $v = new Validator($_POST);
        $values = [];
        switch ($section) {
            case 'general':
                $v->required('platform_name', t('Platform name is required'))
                    ->required('support_email', t('Email is required'))->email('support_email', t('Invalid email address'))
                    ->required('support_phone', t('Phone number is required'))
                    ->in('default_language', ['en', 'bn'], t('Please select a language'))
                    ->check('delivery_charge', is_numeric($this->input('delivery_charge')) && (float)$this->input('delivery_charge') >= 0, t('Enter a valid amount'))
                    ->check('emergency_radius_km', is_numeric($this->input('emergency_radius_km')) && (float)$this->input('emergency_radius_km') >= 1, t('Enter a valid distance'));
                $values = [
                    'platform_name' => $v->value('platform_name'), 'support_email' => $v->value('support_email'), 'support_phone' => $v->value('support_phone'),
                    'default_language' => $v->value('default_language'), 'delivery_charge' => (string)round((float)$v->value('delivery_charge'), 2),
                    'emergency_radius_km' => (string)(float)$v->value('emergency_radius_km'),
                ];
                break;
            case 'security':
                foreach (['session_timeout_minutes' => [5, 1440], 'max_login_attempts' => [3, 20], 'lockout_minutes' => [1, 1440]] as $key => [$min, $max]) {
                    $v->check($key, ctype_digit($this->input($key)) && (int)$this->input($key) >= $min && (int)$this->input($key) <= $max, t('Enter a number between :min and :max', ['min' => $min, 'max' => $max]));
                }
                $values = [
                    'enable_2fa_all' => !empty($_POST['enable_2fa_all']) ? '1' : '0',
                    'suspicious_activity_alert' => !empty($_POST['suspicious_activity_alert']) ? '1' : '0',
                    'session_timeout_minutes' => $this->input('session_timeout_minutes'),
                    'max_login_attempts' => $this->input('max_login_attempts'),
                    'lockout_minutes' => $this->input('lockout_minutes'),
                ];
                break;
            case 'pricing':
                $months = static fn(string $s): bool => $s === '' || (bool)preg_match('/^(\s*(1[0-2]|[1-9])\s*)(,\s*(1[0-2]|[1-9])\s*)*$/', $s);
                $v->in('season_mode', ['auto', 'manual'], t('Please select a season mode'))
                    ->in('current_season', Pricing::SEASONS, t('Please select a season'))
                    ->check('harvest_increase_percent', is_numeric($this->input('harvest_increase_percent')) && (float)$this->input('harvest_increase_percent') >= 0 && (float)$this->input('harvest_increase_percent') <= 200, t('Enter a percentage between 0 and 200'))
                    ->check('off_season_discount_percent', is_numeric($this->input('off_season_discount_percent')) && (float)$this->input('off_season_discount_percent') >= 0 && (float)$this->input('off_season_discount_percent') < 90, t('Enter a percentage between 0 and 89'))
                    ->check('min_rental_price', is_numeric($this->input('min_rental_price')) && (float)$this->input('min_rental_price') >= 1, t('Minimum price must be greater than zero'))
                    ->check('harvest_months', $months($this->input('harvest_months')), t('Use month numbers separated by commas, e.g. 4,5,11'))
                    ->check('off_season_months', $months($this->input('off_season_months')), t('Use month numbers separated by commas, e.g. 4,5,11'));
                $harvest = array_filter(array_map('trim', explode(',', $this->input('harvest_months'))));
                $off = array_filter(array_map('trim', explode(',', $this->input('off_season_months'))));
                $v->check('off_season_months', !array_intersect($harvest, $off), t('A month cannot be both harvest and off-season'));
                $values = [
                    'season_mode' => $v->value('season_mode'), 'current_season' => $v->value('current_season'),
                    'harvest_months' => implode(',', $harvest), 'off_season_months' => implode(',', $off),
                    'harvest_increase_percent' => (string)(float)$this->input('harvest_increase_percent'),
                    'off_season_discount_percent' => (string)(float)$this->input('off_season_discount_percent'),
                    'min_rental_price' => (string)round((float)$this->input('min_rental_price'), 2),
                ];
                break;
            case 'notifications':
                foreach (['email_notifications', 'payment_failure_alert', 'delivery_completion_alert', 'complaint_ticket_alert', 'maintenance_alert'] as $key) {
                    $values[$key] = !empty($_POST[$key]) ? '1' : '0';
                }
                break;
            case 'payments':
                foreach (['method_bkash', 'method_nagad', 'method_card', 'method_cash', 'block_needs_maintenance'] as $key) {
                    $values[$key] = !empty($_POST[$key]) ? '1' : '0';
                }
                if (!array_sum(array_map('intval', array_intersect_key($values, array_flip(['method_bkash', 'method_nagad', 'method_card', 'method_cash']))))) {
                    $v->addError('method_cash', t('At least one payment method must stay enabled.'));
                }
                $v->check('default_usage_threshold', ctype_digit($this->input('default_usage_threshold')) && (int)$this->input('default_usage_threshold') >= 10, t('Enter a valid number of hours'));
                $values['default_usage_threshold'] = $this->input('default_usage_threshold');
                break;
            default:
                $this->fail(t('Unknown settings section.'));
        }
        if ($v->fails()) {
            $this->fail(t('Please fix the highlighted fields.'), $v->errors());
        }
        $changes = Settings::save($values);
        if ($section === 'pricing') {
            $log = new PriceLog();
            foreach ($changes as [$key, $old, $new]) {
                $log->log(null, $key, $old, $new, $this->input('note') ?: 'Pricing rules updated', (int)$user['id']);
            }
        }
        if ($section === 'general') {
            Session::set('lang', Session::get('lang') ?: $values['default_language']);
        }
        (new SecurityLog())->log('settings_' . $section, (int)$user['id'], $user['email'], count($changes) . ' change(s)');
        $this->success(t('Settings saved successfully.') . ($section === 'pricing' && $changes ? ' ' . t('New prices apply instantly to all equipment.') : ''), url('settings/index', ['tab' => $section]));
    }

    public function couponSave(): void
    {
        $this->role('admin');
        $this->requirePost();
        $id = $this->int('id');
        $coupons = new Coupon();
        $v = new Validator($_POST);
        $code = mb_strtoupper(preg_replace('/\s+/', '', $v->value('code')));
        $v->required('code', t('Coupon code is required'))->regex('code', '/^[A-Za-z0-9_-]{3,30}$/', t('Use 3-30 letters or numbers'))
            ->check('code', !$coupons->codeExists($code, $id), t('This coupon code already exists'))
            ->in('discount_type', ['percent', 'fixed'], t('Please select a discount type'))
            ->check('discount_value', is_numeric($this->input('discount_value')) && (float)$this->input('discount_value') > 0 && ($this->input('discount_type') !== 'percent' || (float)$this->input('discount_value') <= 100), t('Enter a valid discount'))
            ->date('starts_at', t('Invalid date'))->date('expires_at', t('Invalid date'))
            ->check('expires_at', $this->input('expires_at') === '' || $this->input('starts_at') === '' || $this->input('expires_at') >= $this->input('starts_at'), t('End date cannot be earlier than start date.'));
        if ($v->fails()) {
            $this->fail(t('Please fix the highlighted fields.'), $v->errors());
        }
        $coupons->save([
            'code' => $code,
            'description' => mb_substr($this->input('description'), 0, 150) ?: null,
            'discount_type' => $this->input('discount_type'),
            'discount_value' => (float)$this->input('discount_value'),
            'min_amount' => is_numeric($this->input('min_amount')) ? (float)$this->input('min_amount') : 0,
            'max_discount' => is_numeric($this->input('max_discount')) ? (float)$this->input('max_discount') : null,
            'starts_at' => $this->input('starts_at') ?: null,
            'expires_at' => $this->input('expires_at') ?: null,
            'usage_limit' => ctype_digit($this->input('usage_limit')) ? (int)$this->input('usage_limit') : null,
            'status' => $this->input('status') === 'inactive' ? 'inactive' : 'active',
        ], $id);
        $this->success(t('Coupon saved.'), url('settings/index', ['tab' => 'coupons']));
    }

    public function couponDelete(): void
    {
        $this->role('admin');
        $this->requirePost();
        try {
            (new Coupon())->remove($this->int('id'));
        } catch (PDOException $e) {
            $this->fail(t('This coupon has been used and cannot be deleted. Deactivate it instead.'));
        }
        $this->success(t('Coupon deleted.'), null, ['removed' => $this->int('id')]);
    }

    public function exportLogs(): void
    {
        $this->role('admin');
        send_csv('system-logs-' . date('Ymd') . '.csv', ['Time', 'Event', 'Email', 'User', 'IP', 'User agent', 'Details'], (new SecurityLog())->export());
    }
}
