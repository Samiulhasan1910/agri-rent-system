<?php
declare(strict_types=1);

class CategoryController extends Controller
{
    private Category $categories;

    public function __construct()
    {
        $this->categories = new Category();
    }

    public function index(): void
    {
        $this->role('admin');
        $rows = $this->categories->withCounts($this->input('q'));
        if ($this->wantsPartial()) {
            $this->partial('category/_table', ['rows' => $rows]);
        }
        $this->view('category/index', ['title' => t('Category Management'), 'rows' => $rows]);
    }

    public function store(): void
    {
        $this->role('admin');
        $this->requirePost();
        $data = $this->validated(0, t('Category name cannot be empty or duplicated.'));
        try {
            $this->categories->create($data);
        } catch (Throwable $e) {
            $this->fail(t('Failed to save category. Please try again.'));
        }
        $this->success(t('Action completed successfully.'), url('category/index'));
    }

    public function update(): void
    {
        $this->role('admin');
        $this->requirePost();
        $id = $this->int('id');
        if (!$this->categories->find($id)) {
            $this->fail(t('Failed to update category. Please ensure the name is valid and try again.'));
        }
        $data = $this->validated($id, t('Failed to update category. Please ensure the name is valid and try again.'));
        try {
            $this->categories->edit($id, $data);
        } catch (Throwable $e) {
            $this->fail(t('Failed to update category. Please ensure the name is valid and try again.'));
        }
        $this->success(t('Action completed successfully.'), url('category/index'));
    }

    public function destroy(): void
    {
        $this->role('admin');
        $this->requirePost();
        $id = $this->int('id');
        if ($this->categories->equipmentCount($id) > 0) {
            $this->fail(t('Failed to delete category.') . ' ' . t('Move or remove the equipment in this category first.'));
        }
        try {
            if (!$this->categories->delete($id)) {
                throw new RuntimeException('not found');
            }
        } catch (Throwable $e) {
            $this->fail(t('Failed to delete category.'));
        }
        $this->success(t('Action completed successfully.'), null, ['removed' => $id]);
    }

    private function validated(int $id, string $message): array
    {
        $name = $this->input('name');
        $errors = [];
        if ($name === '' || mb_strlen($name) > 80 || $this->categories->nameExists($name, $id)) {
            $errors['name'] = t('Category name cannot be empty or duplicated.');
        }
        if ($errors) {
            $this->fail($message, $errors);
        }
        return [
            'name' => $name,
            'description' => mb_substr($this->input('description'), 0, 255) ?: null,
            'icon' => in_array($this->input('icon'), Category::ICONS, true) ? $this->input('icon') : 'tractor',
            'status' => $this->input('status') === 'inactive' ? 'inactive' : 'active',
        ];
    }
}
