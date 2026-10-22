<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->auth();
        $method = $user['role'];
        $this->$method($user);
    }

    private function farmer(array $user): void
    {
        $bookings = new Booking();
        $equipment = new Equipment();
        $counts = $bookings->countByStatus((int)$user['id']);
        $stats = (new Payment())->stats((int)$user['id']);
        $this->view('dashboard/farmer', [
            'title' => t('Dashboard'),
            'user' => $user,
            'categories' => $equipment->categoryTiles(),
            'latest' => $bookings->latestForFarmer((int)$user['id']),
            'endingSoon' => $bookings->endingSoon((int)$user['id']),
            'dues' => $bookings->withBalance((int)$user['id']),
            'counts' => $counts,
            'active' => $counts['confirmed'] + $counts['delivered'],
            'spent' => (float)$stats['paid_total'],
            'wishlistCount' => (new Wishlist())->count((int)$user['id']),
            'openTickets' => (new Complaint())->countOpen((int)$user['id']),
            'recommended' => $equipment->featured(3),
            'locations' => array_keys(Emergency::DISTRICTS),
        ]);
    }

    private function employee(array $user): void
    {
        (new Maintenance())->startDue();
        Health::evaluateAll();
        $bookings = new Booking();
        $equipment = new Equipment();
        [$equipmentRows] = $equipment->search(['q' => $this->input('q'), 'category' => $this->int('category'), 'location' => $this->input('location'), 'status' => $this->input('status')], 1, 6);
        $counts = $bookings->countByStatus();
        $this->view('dashboard/employee', [
            'title' => t('Employee Dashboard'),
            'user' => $user,
            'categories' => (new Category())->active(),
            'stats' => [
                'equipment' => $equipment->countListed(),
                'active' => $counts['confirmed'] + $counts['delivered'],
                'returned' => $counts['returned'],
                'pending' => $counts['pending'],
            ],
            'equipment' => $equipmentRows,
            'alerts' => (new Maintenance())->alerts('open', 6),
            'requests' => $bookings->pendingRequests(5),
            'returns' => $bookings->awaitingInspection(),
            'farmers' => array_slice((new User())->verificationQueue(['farmer']), 0, 4),
            'emergencies' => array_slice((new Emergency())->listAll(), 0, 5),
            'extensions' => (new Extension())->pending(),
        ]);
    }

    private function admin(array $user): void
    {
        (new Maintenance())->startDue();
        Health::evaluateAll();
        $bookings = new Booking();
        $counts = $bookings->countByStatus();
        $monthStart = date('Y-m-01');
        $db = Database::connection();
        $alerts = $db->query("SELECT type,title,message,link,created_at FROM notifications WHERE user_id=" . (int)$user['id'] . " ORDER BY id DESC LIMIT 6")->fetchAll();
        $this->view('dashboard/admin', [
            'title' => t('Admin Dashboard'),
            'user' => $user,
            'stats' => [
                'equipment' => (new Equipment())->countListed(),
                'active' => $counts['confirmed'] + $counts['delivered'],
                'revenue' => (new Payment())->revenueBetween($monthStart, today()),
                'pending' => $counts['pending'] + (new User())->countPendingVerification(['farmer', 'employee']),
            ],
            'chart' => (new Analytics())->monthlyBookings(7),
            'alerts' => $alerts,
            'recent' => $bookings->recent(6),
            'health' => (new Equipment())->healthCounts(),
            'pendingExtensions' => count((new Extension())->pending()),
            'pendingEmergencies' => (new Emergency())->countPending(),
            'openComplaints' => (new Complaint())->countOpen(),
        ]);
    }
}
