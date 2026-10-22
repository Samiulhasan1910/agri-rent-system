<?php
declare(strict_types=1);

/** Smart equipment matching (farm size + crop type + season -> ranked suggestions). */
class SmartController extends Controller
{
    public function index(): void
    {
        $this->role('farmer', 'admin', 'employee');
        $rules = new CropRule();
        $this->view('smart/index', [
            'title' => t('Smart Equipment Matching'),
            'crops' => $rules->crops(),
            'seasons' => CropRule::SEASONS,
            'rules' => is_role('admin') ? $rules->listAll() : [],
            'categories' => (new Category())->active(),
        ]);
    }

    /** AJAX: refreshed on every input change. */
    public function suggest(): void
    {
        $this->role('farmer', 'admin', 'employee');
        $size = $this->input('farm_size');
        $crop = $this->input('crop');
        $season = $this->input('season');

        if ($size === '' || !is_numeric($size) || (float)$size <= 0 || (float)$size > 10000) {
            $this->json(['ok' => false, 'state' => 'invalid', 'message' => t('Farm size is required'), 'errors' => ['farm_size' => t('Farm size is required')]], 422);
        }
        if ($season === '' || !array_key_exists($season, CropRule::SEASONS)) {
            $this->json(['ok' => true, 'state' => 'inactive', 'html' => '', 'message' => t('Select a season to see suggestions.')]);
        }
        if ($crop === '') {
            $this->json(['ok' => true, 'state' => 'inactive', 'html' => '', 'message' => t('Enter a crop type to see suggestions.')]);
        }
        $rules = new CropRule();
        $results = $rules->cropExists($crop) ? $rules->suggest((float)$size, $crop, $season) : [];
        if (!$results) {
            $this->json(['ok' => true, 'state' => 'empty', 'html' => '', 'message' => t('No matching equipment found')]);
        }
        $this->json([
            'ok' => true,
            'state' => 'results',
            'count' => count($results),
            'html' => View::partial('smart/_results', ['results' => $results, 'farmSize' => (float)$size, 'crop' => $crop, 'season' => $season]),
        ]);
    }

    public function ruleSave(): void
    {
        $this->role('admin');
        $this->requirePost();
        $v = new Validator($_POST);
        $v->required('crop', t('Crop is required'))->in('season', array_keys(CropRule::SEASONS), t('Please select a season'))
            ->required('category_id', t('Please select a category'))
            ->check('priority', is_numeric($this->input('priority')) && (int)$this->input('priority') >= 1 && (int)$this->input('priority') <= 100, t('Priority must be between 1 and 100'));
        if ($v->fails()) {
            $this->fail(t('Please fix the highlighted fields.'), $v->errors());
        }
        try {
            (new CropRule())->create([
                'crop' => ucwords(mb_strtolower($v->value('crop'))),
                'season' => $v->value('season'),
                'category_id' => (int)$v->value('category_id'),
                'priority' => (int)$v->value('priority'),
                'min_farm_size' => is_numeric($this->input('min_farm_size')) ? (float)$this->input('min_farm_size') : 0,
                'max_farm_size' => is_numeric($this->input('max_farm_size')) ? (float)$this->input('max_farm_size') : null,
                'note' => mb_substr($this->input('note'), 0, 150) ?: null,
            ]);
        } catch (PDOException $e) {
            $this->fail(t('This rule already exists.'));
        }
        $this->success(t('Matching rule saved.'), url('smart/index'));
    }

    public function ruleDelete(): void
    {
        $this->role('admin');
        $this->requirePost();
        (new CropRule())->remove($this->int('id'));
        $this->success(t('Matching rule removed.'), null, ['removed' => $this->int('id')]);
    }
}
