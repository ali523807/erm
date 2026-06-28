<footer class="landing-footer">
    <div class="landing-footer-brand">
        <a href="{{ route('landing') }}" class="brand-lockup">
            <span class="brand-mark">
                <x-application-logo/>
            </span>
            <span>
                <strong>RentalHook</strong>
                <small>The Complete Equipment Rental Platform</small>
            </span>
        </a>
        <p>Cloud software for equipment rental companies managing fleet, customers, rentals, dispatch, invoicing, payments, and reporting.</p>
    </div>

    <div class="landing-footer-links">
        <div>
            <strong>Product</strong>
            <a href="{{ route('landing') }}#software">Software</a>
            <a href="{{ route('landing') }}#modules">Modules</a>
            <a href="{{ route('landing') }}#pricing">Pricing</a>
        </div>
        <div>
            <strong>Account</strong>
            <a href="{{ route('register') }}">Start Free Trial</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('customer-portal.login') }}">Customer Portal</a>
        </div>
        <div>
            <strong>Contact</strong>
            <a href="mailto:support@rentalhook.com">support@rentalhook.com</a>
            <a href="mailto:info@rentalhook.com">info@rentalhook.com</a>
            <a href="{{ route('contact') }}">Contact page</a>
        </div>
    </div>

    <div class="landing-footer-bottom">
        <span>&copy; {{ now()->year }} RentalHook. All rights reserved.</span>
        <span>Built for Middle East and global equipment rental teams.</span>
    </div>
</footer>
