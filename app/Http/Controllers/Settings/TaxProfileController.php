<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TaxProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaxProfileController extends Controller
{
    public function index(): View
    {
        return view('settings.tax-profiles', [
            'company' => auth()->user()->currentCompany,
            'taxProfiles' => TaxProfile::latest('is_default')->orderBy('name')->get(),
            'countries' => $this->countries(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['company_id'] = auth()->user()->current_company_id;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_compound'] = $request->boolean('is_compound');
        $validated['is_active'] = $request->boolean('is_active', true);

        $profile = TaxProfile::create($validated);
        $this->syncDefault($profile);

        return back()->with('status', 'Tax profile created successfully.');
    }

    public function update(Request $request, TaxProfile $taxProfile): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_compound'] = $request->boolean('is_compound');
        $validated['is_active'] = $request->boolean('is_active');

        $taxProfile->update($validated);
        $this->syncDefault($taxProfile);

        return back()->with('status', 'Tax profile updated successfully.');
    }

    public function destroy(TaxProfile $taxProfile): RedirectResponse
    {
        abort_if($taxProfile->invoices()->exists(), 422, 'Tax profiles used by invoices cannot be deleted.');

        $taxProfile->delete();

        return back()->with('status', 'Tax profile deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'size:2'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function syncDefault(TaxProfile $profile): void
    {
        if (! $profile->is_default) {
            return;
        }

        TaxProfile::where('id', '!=', $profile->id)->update(['is_default' => false]);
    }

    /**
     * @return array<string, string>
     */
    private function countries(): array
    {
        return [
            'US' => 'United States',
            'IN' => 'India',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'ZA' => 'South Africa',
            'DE' => 'Germany',
        ];
    }
}
