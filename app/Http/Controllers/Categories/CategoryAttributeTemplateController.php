<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryAttributeTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryAttributeTemplateController extends Controller
{
    /**
     * @var array<string, string>
     */
    private array $fieldTypes = [
        'text' => 'Text',
        'number' => 'Number',
        'decimal' => 'Decimal',
        'date' => 'Date',
        'select' => 'Select',
        'boolean' => 'Yes / No',
    ];

    public function index(Category $category): View
    {
        return view('categories.attribute-templates.index', [
            'category' => $category,
            'templates' => $category->attributeTemplates()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'fieldTypes' => $this->fieldTypes,
        ]);
    }

    public function store(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validatedData($request, $category);

        $category->attributeTemplates()->create($validated);

        return redirect()
            ->route('categories.attribute-templates.index', $category)
            ->with('status', 'Attribute template added successfully.');
    }

    public function update(Request $request, Category $category, CategoryAttributeTemplate $attributeTemplate): RedirectResponse
    {
        $this->ensureTemplateBelongsToCategory($category, $attributeTemplate);

        $attributeTemplate->update($this->validatedData($request, $category, $attributeTemplate));

        return redirect()
            ->route('categories.attribute-templates.index', $category)
            ->with('status', 'Attribute template updated successfully.');
    }

    public function destroy(Category $category, CategoryAttributeTemplate $attributeTemplate): RedirectResponse
    {
        $this->ensureTemplateBelongsToCategory($category, $attributeTemplate);

        $attributeTemplate->delete();

        return redirect()
            ->route('categories.attribute-templates.index', $category)
            ->with('status', 'Attribute template deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, Category $category, ?CategoryAttributeTemplate $attributeTemplate = null): array
    {
        $companyId = auth()->user()->current_company_id;
        $request->merge([
            'key' => Str::of($request->input('key') ?: $request->input('name'))
                ->lower()
                ->slug('_')
                ->toString(),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('category_attribute_templates', 'key')
                    ->where('company_id', $companyId)
                    ->where('category_id', $category->id)
                    ->ignore($attributeTemplate),
            ],
            'type' => ['required', Rule::in(array_keys($this->fieldTypes))],
            'unit' => ['nullable', 'string', 'max:50'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string'],
            'options_text' => ['nullable', 'string'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_required'] = $request->boolean('is_required');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['options'] = $this->optionsFromText($validated['options_text'] ?? null);

        unset($validated['options_text']);

        return $validated;
    }

    /**
     * @return array<int, string>|null
     */
    private function optionsFromText(?string $options): ?array
    {
        if (! $options) {
            return null;
        }

        $values = collect(preg_split('/\r\n|\r|\n/', $options))
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->values()
            ->all();

        return $values ?: null;
    }

    private function ensureTemplateBelongsToCategory(Category $category, CategoryAttributeTemplate $attributeTemplate): void
    {
        abort_unless($attributeTemplate->category_id === $category->id, 404);
    }
}
