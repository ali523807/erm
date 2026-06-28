<header class="landing-nav">
    <a href="{{ route('landing') }}" class="brand-lockup">
        <span class="brand-mark">
            <x-application-logo/>
        </span>
        <span>
            <strong>RentalHook</strong>
            <small>The Complete Equipment Rental Platform</small>
        </span>
    </a>

    <button class="landing-nav-toggle" type="button" aria-expanded="false" aria-controls="landing-menu" aria-label="Toggle navigation">
        <x-lucide-menu class="landing-nav-icon landing-nav-icon-menu"/>
        <x-lucide-x class="landing-nav-icon landing-nav-icon-close"/>
    </button>

    <nav id="landing-menu" class="landing-menu">
        <a href="{{ route('landing') }}#software">Software</a>
        <a href="{{ route('landing') }}#middle-east">Middle East</a>
        <a href="{{ route('landing') }}#modules">Modules</a>
        <a href="{{ route('landing') }}#pricing">Pricing</a>
        <a href="{{ route('contact') }}">Contact</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('register') }}" class="btn btn-dark">Start Free Trial</a>
    </nav>
</header>
