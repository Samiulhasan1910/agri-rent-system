<?php
declare(strict_types=1);

/**
 * Payments. The card and mobile-wallet screens use a simulated gateway (academic project):
 *   - card numbers ending in 0002 are declined
 *   - wallet PIN 0000 fails with "insufficient balance"
 * Everything else succeeds. Cash on delivery creates a pending payment collected by staff.
 */
class PaymentController extends Controller
{
    private Payment $payments;
    private Booking $bookings;

    public function __construct()
    {
        $this->payments = new Payment();
        $this->bookings = new Booking();
    }

    private function payableBooking(array $user, int $id): array
    {
        $booking = $this->bookings->find($id);
        if (!$booking || (int)$booking['farmer_id'] !== (int)$user['id']) {
            throw new HttpException(404, t('Booking not found.'));
        }
        if (in_array($booking['status'], ['cancelled', 'rejected'], true)) {
            $this->fail(t('This booking was cancelled.'), [], url('booking/show', ['id' => $id]));
        }
        return $booking;
    }

    /* ==============================================================
     * Farmer checkout
     * ============================================================== */
    public function checkout(): void
    {
        $user = $this->role('farmer');
        $booking = $this->payableBooking($user, $this->int('booking'));
        $due = Booking::balance($booking);
        if ($due <= 0) {
            $this->fail(t('This booking has no outstanding balance.'), [], url('booking/show', ['id' => $booking['id']]));
        }
        if ($this->payments->hasPendingCash((int)$booking['id'])) {
            Session::flash('info', t('Cash on delivery is already selected for this booking. You can still pay online now.'));
        }
        $this->view('payment/checkout', [
            'title' => t('Choose Payment Method'),
            'b' => $booking,
            'due' => $due,
            'methods' => Payment::enabledMethods(),
        ]);
    }

    public function pay(): void
    {
        $user = $this->role('farmer');
        $booking = $this->payableBooking($user, $this->int('booking'));
        $method = $this->input('method');
        if (!array_key_exists($method, Payment::enabledMethods())) {
            $this->fail(t('Selected payment method is not available.'), [], url('payment/checkout', ['booking' => $booking['id']]));
        }
        $due = Booking::balance($booking);
        if ($due <= 0) {
            $this->fail(t('This booking has no outstanding balance.'), [], url('booking/show', ['id' => $booking['id']]));
        }
        $this->view('payment/pay', ['title' => Payment::label($method), 'b' => $booking, 'due' => $due, 'method' => $method, 'user' => $user]);
    }

    /** AJAX coupon preview. */
    public function coupon(): void
    {
        $user = $this->role('farmer');
        $this->requirePost();
        $booking = $this->payableBooking($user, $this->int('booking'));
        $due = Booking::balance($booking);
        try {
            $result = (new Coupon())->validate($this->input('code'), $due);
        } catch (DomainException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage(), 'errors' => ['coupon' => $e->getMessage()], 'amount' => $due], 422);
        }
        $this->json([
            'ok' => true,
            'message' => t('Coupon applied! You save :amount.', ['amount' => money($result['discount'], true)]),
            'discount' => $result['discount'],
            'amount' => round($due - $result['discount'], 2),
            'code' => $result['coupon']['code'],
        ]);
    }

    /** Demo wallet OTP (a real bKash/Nagad gateway sends this SMS itself). */
    public function sendOtp(): void
    {
        $this->role('farmer');
        $this->requirePost();
        $number = preg_replace('/\D/', '', $this->input('wallet_number'));
        if (!preg_match('/^01[3-9]\d{8}$/', $number)) {
            $this->fail(t('Invalid wallet number'), ['wallet_number' => t('Invalid wallet number')]);
        }
        $code = random_digits(6);
        Session::set('wallet_otp', ['code' => $code, 'number' => $number, 'expires' => time() + (int)config('security.otp_seconds', 120)]);
        $this->success(t('OTP sent to :number. Demo OTP: :code', ['number' => substr($number, 0, 5) . '****' . substr($number, -2), 'code' => $code]), null, ['expires_in' => (int)config('security.otp_seconds', 120), 'demo_otp' => $code]);
    }

    public function process(): void
    {
        $user = $this->role('farmer');
        $this->requirePost();
        $booking = $this->payableBooking($user, $this->int('booking'));
        $method = $this->input('method');
        if (!array_key_exists($method, Payment::enabledMethods())) {
            $this->fail(t('Selected payment method is not available.'), [], url('payment/checkout', ['booking' => $booking['id']]));
        }

        // ---- validate method details
        $v = new Validator($_POST);
        $accountRef = null;
        if ($method === 'card') {
            $number = preg_replace('/\D/', '', $v->value('card_number'));
            $v->required('card_number', t('Card number is required'))->check('card_number', $number === '' || self::luhn($number), t('Invalid card number'))
                ->required('card_name', t('Cardholder name is required'))
                ->required('expiry', t('Expiry date is required'))->check('expiry', $v->value('expiry') === '' || self::validExpiry($v->value('expiry')), t('Invalid expiry date'))
                ->required('cvc', t('CVC is required'))->regex('cvc', '/^\d{3,4}$/', t('Invalid CVC'));
            $accountRef = self::cardBrand($number) . ' **** ' . substr($number, -4);
        } elseif (in_array($method, ['bkash', 'nagad'], true)) {
            $wallet = preg_replace('/\D/', '', $v->value('wallet_number'));
            $otp = Session::get('wallet_otp');
            $v->required('wallet_number', t('Wallet number is required'))->check('wallet_number', $wallet === '' || preg_match('/^01[3-9]\d{8}$/', $wallet) === 1, t('Invalid wallet number'))
                ->required('otp', t('OTP is required'))
                ->check('otp', $v->value('otp') === '' || ($otp && $otp['number'] === $wallet && $otp['expires'] >= time() && hash_equals($otp['code'], $v->value('otp'))), t('Invalid or expired OTP'))
                ->required('pin', t('PIN is required'))->regex('pin', '/^\d{4,5}$/', t('Invalid PIN'));
            $accountRef = substr($wallet, 0, 5) . '****' . substr($wallet, -2);
        } else {
            $v->check('confirm_cash', !empty($_POST['confirm_cash']), t('Please confirm that you will pay in cash on delivery.'));
        }
        if ($v->fails()) {
            $this->fail(t('Please check your payment details.'), $v->errors());
        }

        // ---- coupon
        $due = Booking::balance($booking);
        if ($due <= 0) {
            $this->fail(t('This booking has no outstanding balance.'), [], url('booking/show', ['id' => $booking['id']]));
        }
        $coupon = null;
        $discount = 0.0;
        if ($this->input('coupon') !== '') {
            try {
                $result = (new Coupon())->validate($this->input('coupon'), $due);
                $coupon = $result['coupon'];
                $discount = $result['discount'];
            } catch (DomainException $e) {
                $this->fail($e->getMessage(), ['coupon' => $e->getMessage()]);
            }
        }
        $amount = round($due - $discount, 2);

        // ---- simulated gateway decision
        $declined = null;
        if ($method === 'card' && str_ends_with(preg_replace('/\D/', '', $v->value('card_number')), '0002')) {
            $declined = t('Card declined by the issuing bank.');
        } elseif (in_array($method, ['bkash', 'nagad'], true) && $v->value('pin') === '0000') {
            $declined = t('Insufficient wallet balance.');
        }

        if ($declined) {
            $paymentId = $this->payments->create([
                'booking_id' => $booking['id'], 'method' => $method, 'amount' => $amount, 'account_ref' => $accountRef,
                'status' => 'failed', 'failure_reason' => $declined,
            ]);
            Session::forget('wallet_otp');
            $message = t('Payment failed. Please try again.') . ' ' . $declined;
            if (Settings::enabled('payment_failure_alert')) {
                $notified = Notifier::admins('Payment failed', t('Payment for :code (:equipment) by :farmer failed: :reason', [
                    'code' => $booking['booking_code'], 'equipment' => $booking['equipment_name'], 'farmer' => $user['name'], 'reason' => $declined,
                ]), url('payment/admin', ['status' => 'failed']), 'danger');
                if (!$notified) {
                    error_log("Payment failure notification could not be delivered for payment {$paymentId}");
                    $message .= ' ' . t('Payment failure notification could not be delivered.');
                }
            }
            $this->fail($message, [], null, 402);
        }

        // ---- record payment (no money moves before this point)
        try {
            $paymentId = $this->payments->transaction(function () use ($booking, $method, $amount, $discount, $coupon, $accountRef): int {
                Database::connection()->prepare('SELECT id FROM bookings WHERE id=? FOR UPDATE')->execute([$booking['id']]);
                $fresh = $this->bookings->find((int)$booking['id']);
                if (Booking::balance($fresh) <= 0) {
                    throw new DomainException(t('This booking has no outstanding balance.'));
                }
                if ($discount > 0) {
                    $this->bookings->applyDiscount((int)$booking['id'], $discount);
                    (new Coupon())->used((int)$coupon['id']);
                }
                if ($method === 'cash') {
                    Database::connection()->prepare("UPDATE payments SET status='cancelled' WHERE booking_id=? AND method='cash' AND status='pending'")->execute([$booking['id']]);
                }
                return $this->payments->create([
                    'booking_id' => $booking['id'],
                    'method' => $method,
                    'amount' => $amount,
                    'discount' => $discount,
                    'coupon_id' => $coupon['id'] ?? null,
                    'transaction_id' => $method === 'cash' ? null : 'TXN' . strtoupper(bin2hex(random_bytes(5))),
                    'account_ref' => $accountRef,
                    'status' => $method === 'cash' ? 'pending' : 'paid',
                    'paid_at' => $method === 'cash' ? null : date('Y-m-d H:i:s'),
                ]);
            });
        } catch (DomainException $e) {
            $this->fail($e->getMessage(), [], url('booking/show', ['id' => $booking['id']]));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Payment failed. Please try again.'));
        }
        Session::forget('wallet_otp');

        $payment = $this->payments->find($paymentId);
        if ($method === 'cash') {
            Notifier::staff('Cash on delivery booking', t(':code will be paid in cash on delivery (:amount).', ['code' => $booking['booking_code'], 'amount' => money($amount)]), url('payment/admin'));
            $this->success(t('Booking confirmed! Please pay :amount in cash when the equipment is delivered.', ['amount' => money($amount)]), url('payment/complete', ['id' => $paymentId]));
        }
        Notifier::send((int)$user['id'], 'Payment successful', t('We received :amount for :code. Invoice :invoice.', ['amount' => money($amount), 'code' => $booking['booking_code'], 'invoice' => $payment['invoice_no']]), url('payment/complete', ['id' => $paymentId]), 'success');
        $emailed = $this->sendReceipt($payment);
        $this->success($emailed ? t('Payment successful!') : t('Payment successful, but receipt email could not be sent.'), url('payment/complete', ['id' => $paymentId]));
    }

    private function sendReceipt(array $p): bool
    {
        $rows = [
            t('Invoice') => $p['invoice_no'], t('Transaction ID') => $p['transaction_id'] ?? '-', t('Booking ID') => $p['booking_code'],
            t('Equipment') => $p['equipment_name'], t('Payment Method') => Payment::label($p['method']), t('Paid Amount') => money($p['amount'], true),
            t('Payment Date') => fmt_datetime($p['paid_at'] ?? $p['created_at']),
        ];
        $table = '<table style="width:100%;border-collapse:collapse;margin:12px 0">';
        foreach ($rows as $k => $val) {
            $table .= '<tr><td style="padding:7px 0;color:#6b7c72;border-bottom:1px solid #eef3ef">' . e($k) . '</td><td style="padding:7px 0;text-align:right;font-weight:600;border-bottom:1px solid #eef3ef">' . e($val) . '</td></tr>';
        }
        $table .= '</table>';
        $ok = Notifier::email($p['farmer_email'], t('Payment receipt :invoice', ['invoice' => $p['invoice_no']]), t('Payment received'),
            '<p>' . e(t('Thank you! Your payment was successful.')) . '</p>' . $table, t('Download Invoice'), abs_url('payment/invoice', ['id' => $p['id']]), true);
        if ($ok) {
            $this->payments->markReceiptEmailed((int)$p['id']);
        }
        return $ok;
    }

    public function complete(): void
    {
        $user = $this->auth();
        $payment = $this->ownedPayment($user);
        $this->view('payment/success', ['title' => t('Payment Successful!'), 'p' => $payment, 'b' => $this->bookings->find((int)$payment['booking_id'])]);
    }

    private function ownedPayment(array $user): array
    {
        $payment = $this->payments->find($this->int('id'));
        if (!$payment || ($user['role'] === 'farmer' && (int)$payment['farmer_id'] !== (int)$user['id'])) {
            throw new HttpException(404, t('Payment not found.'));
        }
        return $payment;
    }

    /* ==============================================================
     * History, invoices & exports
     * ============================================================== */
    private function filters(): array
    {
        return [
            'q' => $this->input('q'),
            'status' => in_array($this->input('status'), ['paid', 'pending', 'failed', 'refunded', 'cancelled'], true) ? $this->input('status') : '',
            'method' => array_key_exists($this->input('method'), Payment::METHODS) ? $this->input('method') : '',
            'from' => valid_date($this->input('from')) ? $this->input('from') : '',
            'to' => valid_date($this->input('to')) ? $this->input('to') : '',
        ];
    }

    public function history(): void
    {
        $user = $this->role('farmer');
        $f = $this->filters() + ['farmer_id' => (int)$user['id']];
        try {
            [$rows, $meta] = $this->payments->paginateList($f, max(1, $this->int('page', 1)));
        } catch (Throwable $e) {
            $this->fail(t('Failed to load payment history.'), [], url('dashboard/index'));
        }
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('payment/_history', $data);
        }
        $this->view('payment/history', $data + [
            'title' => t('Payment History'),
            'stats' => $this->payments->stats((int)$user['id']),
            'dues' => $this->bookings->withBalance((int)$user['id']),
        ]);
    }

    public function admin(): void
    {
        $this->role('admin');
        $f = $this->filters();
        try {
            [$rows, $meta] = $this->payments->paginateList($f, max(1, $this->int('page', 1)));
        } catch (Throwable $e) {
            $this->fail(t('Unable to load payment status.'), [], url('dashboard/index'));
        }
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('payment/_admin_table', $data);
        }
        $this->view('payment/admin', $data + ['title' => t('Payment And Invoice Management'), 'stats' => $this->payments->stats()]);
    }

    public function invoice(): void
    {
        $user = $this->auth();
        $payment = $this->ownedPayment($user);
        if (!in_array($payment['status'], ['paid', 'pending', 'refunded'], true)) {
            $this->fail(t('Failed to generate invoice.') . ' ' . t('Invoices are only available for successful payments.'));
        }
        if ($user['role'] === 'admin') {
            $this->payments->markInvoiceGenerated((int)$payment['id']);
        }
        echo View::render('payment/invoice', ['title' => $payment['invoice_no'], 'p' => $payment, 'booking' => $this->bookings->find((int)$payment['booking_id'])], 'print');
    }

    public function invoicePdf(): void
    {
        $user = $this->auth();
        $p = $this->ownedPayment($user);
        if (!in_array($p['status'], ['paid', 'pending', 'refunded'], true)) {
            $this->fail(t('Failed to generate invoice.'));
        }
        try {
            $pdf = new Pdf();
            $pdf->addPage();
            $pdf->header('INVOICE ' . $p['invoice_no'], 'Issued ' . fmt_date($p['created_at']));
            $m = $pdf->margin;
            $pdf->font(10, true);
            $pdf->text($m, $pdf->y, 'Billed to');
            $pdf->text($m + 280, $pdf->y, 'Payment');
            $pdf->font(10);
            $lines = [[$p['farmer_name'], 'Method: ' . Payment::METHODS[$p['method']]], [$p['farmer_email'], 'Status: ' . ucfirst($p['status'])],
                [$p['farmer_phone'], 'Transaction: ' . ($p['transaction_id'] ?: '-')], [$p['delivery_address'], 'Date: ' . fmt_datetime($p['paid_at'] ?? $p['created_at'])]];
            foreach ($lines as $i => [$left, $right]) {
                $pdf->text($m, $pdf->y + 16 + $i * 14, $pdf->fit((string)$left, 260));
                $pdf->text($m + 280, $pdf->y + 16 + $i * 14, $pdf->fit((string)$right, 230));
            }
            $pdf->y += 90;
            $pdf->table([
                ['label' => 'Description', 'width' => 255], ['label' => 'Days', 'width' => 50, 'align' => 'R'],
                ['label' => 'Rate', 'width' => 100, 'align' => 'R'], ['label' => 'Amount', 'width' => 110, 'align' => 'R'],
            ], [
                [$p['equipment_name'] . ' (' . $p['booking_code'] . ', ' . fmt_date($p['start_date']) . ' - ' . fmt_date($p['end_date']) . ')', $p['days'], 'Tk ' . number_format((float)$p['price_per_day'], 2), 'Tk ' . number_format((float)$p['rental_cost'], 2)],
                ['Delivery charge', '', '', 'Tk ' . number_format((float)$p['delivery_charge'], 2)],
                ['Discount' . ($p['coupon_code'] ? ' (' . $p['coupon_code'] . ')' : ''), '', '', '- Tk ' . number_format((float)$p['discount'], 2)],
            ], 22);
            $pdf->y += 18;
            $pdf->rect($m + 300, $pdf->y, 215, 30, [234, 246, 238]);
            $pdf->font(12, true);
            $pdf->text($m + 310, $pdf->y + 20, 'Amount ' . ($p['status'] === 'pending' ? 'due' : 'paid') . ':');
            $pdf->text($m + 300, $pdf->y + 20, 'Tk ' . number_format((float)$p['amount'], 2), [20, 83, 45], 'R', 205);
            $pdf->y += 60;
            $pdf->font(9);
            $pdf->text($m, $pdf->y, 'Thank you for renting with AgriRent. Support: ' . setting('support_email') . ' | Hotline ' . setting('support_phone'), [107, 124, 114]);
            if ($user['role'] === 'admin') {
                $this->payments->markInvoiceGenerated((int)$p['id']);
            }
            $pdf->download($p['invoice_no'] . '.pdf');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to generate invoice.'));
        }
    }

    public function generateInvoice(): void
    {
        $this->role('admin');
        $this->requirePost();
        $payment = $this->payments->find($this->int('id'));
        if (!$payment || !in_array($payment['status'], ['paid', 'pending', 'refunded'], true)) {
            $this->fail(t('Failed to generate invoice.'));
        }
        $this->payments->markInvoiceGenerated((int)$payment['id']);
        $this->success(t('Invoice :invoice generated.', ['invoice' => $payment['invoice_no']]), null, ['open' => url('payment/invoice', ['id' => $payment['id']])]);
    }

    public function markPaid(): void
    {
        $this->role('admin', 'employee');
        $this->requirePost();
        $payment = $this->payments->find($this->int('id'));
        if (!$payment || !$this->payments->markPaid((int)$payment['id'])) {
            $this->fail(t('Unable to update payment status.'));
        }
        Notifier::send((int)$payment['farmer_id'], 'Payment received', t('Cash payment of :amount for :code was received. Thank you!', ['amount' => money($payment['amount']), 'code' => $payment['booking_code']]), url('payment/history'), 'success');
        $this->success(t('Cash payment marked as received.'));
    }

    /** "Retry" for failed payments: asks the farmer to try again. */
    public function remind(): void
    {
        $this->role('admin');
        $this->requirePost();
        $payment = $this->payments->find($this->int('id'));
        if (!$payment || $payment['status'] !== 'failed') {
            $this->fail(t('Only failed payments can be retried.'));
        }
        $ok = Notifier::send((int)$payment['farmer_id'], 'Payment failed', t('Your payment for :code failed. Please try again from My Bookings.', ['code' => $payment['booking_code']]), url('payment/checkout', ['booking' => $payment['booking_id']]), 'danger');
        if (!$ok) {
            $this->fail(t('Payment failure notification could not be delivered.'));
        }
        $this->success(t('The farmer has been asked to retry the payment.'));
    }

    public function export(): void
    {
        $user = $this->role('admin', 'farmer');
        $f = $this->filters();
        if ($user['role'] === 'farmer') {
            $f['farmer_id'] = (int)$user['id'];
        }
        try {
            $rows = $this->payments->exportRows($f);
        } catch (Throwable $e) {
            $this->fail(t('Failed to export payment data.'));
        }
        send_csv('payments-' . date('Ymd') . '.csv', ['Invoice', 'Date', 'Booking', 'Farmer', 'Equipment', 'Method', 'Transaction ID', 'Amount (BDT)', 'Discount (BDT)', 'Status', 'Invoice generated'],
            array_map(static fn($p) => [$p['invoice_no'], $p['created_at'], $p['booking_code'], $p['farmer_name'], $p['equipment_name'], Payment::METHODS[$p['method']] ?? $p['method'],
                $p['transaction_id'] ?? '', number_format((float)$p['amount'], 2, '.', ''), number_format((float)$p['discount'], 2, '.', ''), $p['status'], $p['invoice_generated_at'] ?? ''], $rows));
    }

    /* ---------------- helpers ---------------- */
    private static function luhn(string $number): bool
    {
        if (!preg_match('/^\d{13,19}$/', $number)) {
            return false;
        }
        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int)$number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }

    private static function validExpiry(string $value): bool
    {
        if (!preg_match('#^(0[1-9]|1[0-2])\s*/\s*(\d{2})$#', $value, $m)) {
            return false;
        }
        $year = 2000 + (int)$m[2];
        $month = (int)$m[1];
        return $year > (int)date('Y') || ($year === (int)date('Y') && $month >= (int)date('n'));
    }

    private static function cardBrand(string $number): string
    {
        if (str_starts_with($number, '4')) {
            return 'VISA';
        }
        if (preg_match('/^(5[1-5]|2[2-7])/', $number)) {
            return 'MASTERCARD';
        }
        if (preg_match('/^3[47]/', $number)) {
            return 'AMEX';
        }
        return 'CARD';
    }
}
