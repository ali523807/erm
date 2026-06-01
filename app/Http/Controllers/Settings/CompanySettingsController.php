<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.company', [
            'company' => auth()->user()->currentCompany,
            'countries' => $this->countries(),
            'currencies' => $this->currencies(),
            'locales' => $this->locales(),
            'timezones' => $this->timezones(),
            'dateFormats' => $this->dateFormats(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', Rule::in(array_keys($this->countries()))],
            'currency' => ['required', 'string', Rule::in(array_keys($this->currencies()))],
            'locale' => ['required', 'string', Rule::in(array_keys($this->locales()))],
            'timezone' => ['required', 'string', Rule::in($this->timezones())],
            'date_format' => ['required', 'string', Rule::in(array_keys($this->dateFormats()))],
            'measurement_system' => ['required', Rule::in(['metric', 'imperial', 'mixed'])],
            'tax_name' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['nullable', 'boolean'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
        ]);

        $validated['currency'] = strtoupper($validated['currency']);
        $validated['tax_inclusive'] = $request->boolean('tax_inclusive');

        auth()->user()->currentCompany->update($validated);

        return back()->with('status', 'Company setup updated successfully.');
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

    /**
     * @return array<string, string>
     */
    private function currencies(): array
    {
        return [
            'USD' => 'USD - US Dollar',
            'INR' => 'INR - Indian Rupee',
            'AED' => 'AED - UAE Dirham',
            'GBP' => 'GBP - British Pound',
            'CAD' => 'CAD - Canadian Dollar',
            'AUD' => 'AUD - Australian Dollar',
            'SAR' => 'SAR - Saudi Riyal',
            'SGD' => 'SGD - Singapore Dollar',
            'ZAR' => 'ZAR - South African Rand',
            'EUR' => 'EUR - Euro',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function locales(): array
    {
        return [
            'en' => 'English',
            'en_IN' => 'English (India)',
            'en_GB' => 'English (United Kingdom)',
            'ar' => 'Arabic',
            'de' => 'German',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function timezones(): array
    {
        return [
            'UTC',
            'America/New_York',
            'America/Chicago',
            'America/Los_Angeles',
            'Asia/Calcutta',
            'Asia/Dubai',
            'Asia/Riyadh',
            'Asia/Singapore',
            'Europe/London',
            'Europe/Berlin',
            'Australia/Sydney',
            'Africa/Johannesburg',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dateFormats(): array
    {
        return [
            'Y-m-d' => '2026-06-01',
            'd/m/Y' => '01/06/2026',
            'm/d/Y' => '06/01/2026',
            'd M Y' => '01 Jun 2026',
            'M d, Y' => 'Jun 01, 2026',
        ];
    }
}
