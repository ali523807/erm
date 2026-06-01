<?php

use App\Models\Category;
use App\Models\CategoryAttributeTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('manages category attribute templates on a full page', function () {
    [$user, $company] = createTemplateTenant();

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Generators',
        'description' => 'Power rental assets',
    ]);

    $this->actingAs($user)
        ->get(route('categories.attribute-templates.index', $category))
        ->assertOk()
        ->assertSee('Generators Attributes')
        ->assertSee('Add Attribute Template')
        ->assertSee('Help Text');

    $this->actingAs($user)
        ->post(route('categories.attribute-templates.store', $category), [
            'name' => 'Fuel Type',
            'type' => 'select',
            'unit' => null,
            'placeholder' => 'Example: Diesel',
            'help_text' => 'Select the primary fuel used by this generator.',
            'options_text' => "Diesel\nPetrol\nElectric",
            'default_value' => 'Diesel',
            'is_required' => '1',
            'sort_order' => 5,
        ])
        ->assertRedirect(route('categories.attribute-templates.index', $category));

    $template = CategoryAttributeTemplate::where('category_id', $category->id)->first();

    expect($template)->not->toBeNull()
        ->and($template->company_id)->toBe($company->id)
        ->and($template->key)->toBe('fuel_type')
        ->and($template->options)->toBe(['Diesel', 'Petrol', 'Electric'])
        ->and($template->is_required)->toBeTrue();

    $this->actingAs($user)
        ->put(route('categories.attribute-templates.update', [$category, $template]), [
            'name' => 'Fuel Source',
            'key' => 'fuel_source',
            'type' => 'text',
            'help_text' => 'Record exact fuel source details.',
            'sort_order' => 2,
        ])
        ->assertRedirect(route('categories.attribute-templates.index', $category));

    $template->refresh();

    expect($template->name)->toBe('Fuel Source')
        ->and($template->key)->toBe('fuel_source')
        ->and($template->is_required)->toBeFalse();

    $this->actingAs($user)
        ->delete(route('categories.attribute-templates.destroy', [$category, $template]))
        ->assertRedirect(route('categories.attribute-templates.index', $category));

    expect(CategoryAttributeTemplate::whereKey($template->id)->exists())->toBeFalse();
});

it('suggests category templates on the equipment create page', function () {
    [$user, $company] = createTemplateTenant('equipment-template@example.com', 'Equipment Template Rentals');

    $category = Category::create([
        'company_id' => $company->id,
        'name' => 'Camera Gear',
        'description' => 'Camera rental assets',
    ]);

    CategoryAttributeTemplate::create([
        'company_id' => $company->id,
        'category_id' => $category->id,
        'name' => 'Lens Mount',
        'key' => 'lens_mount',
        'type' => 'select',
        'options' => ['RF', 'EF', 'PL'],
        'placeholder' => 'Example: RF',
        'help_text' => 'Use the mount type customers need for compatibility.',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('products.create', ['category_id' => $category->id]))
        ->assertOk()
        ->assertSee('Lens Mount')
        ->assertSee('"type":"select"', false)
        ->assertSee('RF')
        ->assertSee('Use the mount type customers need for compatibility.');
});

/**
 * @return array{0: User, 1: Company}
 */
function createTemplateTenant(string $email = 'template-owner@example.com', string $companyName = 'Template Rentals'): array
{
    $company = Company::create([
        'name' => $companyName,
        'slug' => str($companyName)->slug().'-'.str()->random(6),
        'email' => $email,
        'country' => 'US',
        'timezone' => 'UTC',
    ]);

    $user = User::factory()->create([
        'email' => $email,
        'current_company_id' => $company->id,
    ]);

    $company->users()->attach($user, [
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    return [$user, $company];
}
