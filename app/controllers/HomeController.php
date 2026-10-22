<?php
declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $equipment = new Equipment();
        $db = Database::connection();
        $this->view('home/index', [
            'title' => t('Rent Agricultural Equipment Easily'),
            'featured' => $equipment->featured(6),
            'categories' => $equipment->categoryTiles(),
            'stats' => [
                'equipment' => $equipment->countListed(),
                'farmers' => (new User())->countFarmers(),
                'rentals' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn(),
                'districts' => (int)$db->query("SELECT COUNT(DISTINCT location) FROM equipment WHERE status<>'inactive'")->fetchColumn(),
            ],
        ], 'public');
    }
}
