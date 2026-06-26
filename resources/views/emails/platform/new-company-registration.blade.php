<x-mail::message>
# New company registered

A new rental company has started registration on RentalHook.

<x-mail::panel>
**Company:** {{ $company->name }}  
**Owner:** {{ $user->name }}  
**Email:** {{ $user->email }}  
**Plan:** {{ $subscriptionPlan?->name ?? 'Not selected' }}  
**Registered:** {{ now()->format('M d, Y H:i') }}
</x-mail::panel>

<x-mail::button :url="route('platform.companies.show', $company)">
Review Company
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
