<?php
declare(strict_types=1);

/** Admin analytics dashboard and report generation (PDF/CSV). */
class AnalyticsController extends Controller
{
    public function index(): void
    {
        $this->role('admin');
        $from = date('Y-m-01', strtotime('-5 months'));
        $to = today();
        $analytics = new Analytics();
        $this->view('analytics/index', [
            'title' => t('Reports / Analytics'),
            'from' => $from,
            'to' => $to,
            'equipment' => (new Equipment())->options(),
            'summary' => $analytics->summary(date('Y-m-01'), $to),
            'top' => (new Equipment())->topRented($from, $to, 1)[0] ?? null,
            'reports' => (new Report())->recent(10),
        ]);
    }

    /** AJAX data for charts (validated date range + equipment filter). */
    public function data(): void
    {
        $this->role('admin');
        session_write_close();
        $from = $this->input('from');
        $to = $this->input('to');
        if (!valid_date($from) || !valid_date($to)) {
            $this->json(['ok' => false, 'message' => t('Please select a valid date range.')], 422);
        }
        if ($to < $from) {
            $this->json(['ok' => false, 'message' => t('End date cannot be earlier than start date.'), 'errors' => ['to' => t('End date cannot be earlier than start date.')]], 422);
        }
        if (days_between($from, $to) > 1100) {
            $this->json(['ok' => false, 'message' => t('Please select a range of at most 3 years.')], 422);
        }
        $equipmentId = $this->int('equipment');
        $a = new Analytics();
        $summary = $a->summary($from, $to, $equipmentId);
        $series = $a->series($from, $to, $equipmentId);
        $empty = $summary['bookings'] === 0 && $summary['revenue'] == 0;
        $this->json([
            'ok' => true,
            'empty' => $empty,
            'message' => $empty ? t('No data available') : '',
            'summary' => $summary + [
                'revenue_fmt' => money($summary['revenue']),
                'avg_fmt' => money($summary['avg_value']),
                'booking_value_fmt' => money($summary['booking_value']),
            ],
            'series' => $series,
            'usage' => $a->usage($from, $to, $equipmentId),
            'categories' => $a->byCategory($from, $to, $equipmentId),
            'statuses' => array_map(static fn($r) => ['label' => status_meta($r['label'])[0], 'value' => (int)$r['value']], $a->statusBreakdown($from, $to, $equipmentId)),
            'refreshed_at' => date('h:i:s A'),
        ]);
    }

    public function generate(): void
    {
        $user = $this->role('admin');
        $this->requirePost();
        $type = $this->input('type');
        $from = $this->input('from');
        $to = $this->input('to');
        if (!array_key_exists($type, Report::TYPES)) {
            $this->fail(t('Failed to generate report. Please try again.'), ['type' => t('Please select a report type.')]);
        }
        if (!valid_date($from) || !valid_date($to) || $to < $from) {
            $this->fail(t('Invalid date range or no data available for the selected period.'), ['to' => t('End date cannot be earlier than start date.')]);
        }
        $reports = new Report();
        try {
            $data = $reports->build($type, $from, $to);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to generate report. Please try again.'));
        }
        if ($data['count'] === 0) {
            $this->fail(t('Invalid date range or no data available for the selected period.'));
        }
        $report = $reports->log($type, $from, $to, $data, (int)$user['id']);
        $this->success(t('Report generated successfully.'), null, ['row' => View::partial('analytics/_report_row', ['r' => $report])]);
    }

    public function download(): void
    {
        $this->role('admin');
        $reports = new Report();
        $report = $reports->find($this->int('id'));
        if (!$report) {
            $this->fail(t('Failed to download report. Please try again.'), [], url('analytics/index'));
        }
        try {
            $data = $reports->build($report['type'], $report['date_from'], $report['date_to']);
        } catch (DomainException $e) {
            $this->fail($e->getMessage(), [], url('analytics/index'));
        }
        $name = strtolower($report['report_no']) . '-' . $report['type'];
        if ($this->input('format') === 'csv') {
            $labels = array_column($data['columns'], 'label');
            $rows = $data['rows'];
            $rows[] = array_merge(array_fill(0, count($labels) - 1, ''), ['Total: Tk ' . number_format($data['total'], 2)]);
            send_csv($name . '.csv', $labels, $rows);
        }
        try {
            $pdf = new Pdf(true);
            $pdf->addPage();
            $pdf->header(Report::TYPES[$report['type']], $report['report_no'] . ' | ' . fmt_date($report['date_from']) . ' - ' . fmt_date($report['date_to']));
            $pdf->font(10);
            $pdf->text($pdf->margin, $pdf->y, 'Rows: ' . $data['count'] . '    Total: Tk ' . number_format($data['total'], 2) . '    Generated by: ' . ($report['generated_by_name'] ?? 'Admin'));
            $pdf->y += 18;
            $scale = $pdf->contentWidth() / array_sum(array_column($data['columns'], 'width'));
            $columns = array_map(static fn($c) => ['label' => $c['label'], 'width' => $c['width'] * $scale, 'align' => $c['align']], $data['columns']);
            $pdf->table($columns, $data['rows']);
            $pdf->y += 16;
            $pdf->font(11, true);
            $pdf->text($pdf->margin, $pdf->y, 'Total: Tk ' . number_format($data['total'], 2), [20, 83, 45]);
            $pdf->download($name . '.pdf');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to download report. Please try again.'), [], url('analytics/index'));
        }
    }
}
