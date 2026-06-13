<section class="panel {{ $class ?? '' }}">
    <div class="panel-header align-items-start">
        <div>
            <h2>{{ $title ?? 'Email Document' }}</h2>
            <p>{{ $description ?? 'Send this document as a PDF attachment and keep a delivery history for follow-up.' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="row g-3">
        @csrf
        @isset($hidden)
            @foreach($hidden as $name => $value)
                @if($value !== null)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
        @endisset
        <div class="col-md-4">
            <label for="{{ $idPrefix }}_recipient_email" class="form-label">Recipient Email</label>
            <input id="{{ $idPrefix }}_recipient_email" name="recipient_email" type="email" class="form-control" value="{{ old('recipient_email', $recipientEmail ?? '') }}" required>
        </div>
        <div class="col-md-4">
            <label for="{{ $idPrefix }}_recipient_name" class="form-label">Recipient Name</label>
            <input id="{{ $idPrefix }}_recipient_name" name="recipient_name" class="form-control" value="{{ old('recipient_name', $recipientName ?? '') }}">
        </div>
        <div class="col-md-4">
            <label for="{{ $idPrefix }}_subject" class="form-label">Subject</label>
            <input id="{{ $idPrefix }}_subject" name="subject" class="form-control" value="{{ old('subject', $subject ?? '') }}">
        </div>
        <div class="col-12">
            <label for="{{ $idPrefix }}_message" class="form-label">Message</label>
            <textarea id="{{ $idPrefix }}_message" name="message" rows="3" class="form-control">{{ old('message', $message ?? 'Please find the attached document for your records.') }}</textarea>
            <div class="form-text">A PDF copy is generated at send time and the attempt is recorded in Delivery Log.</div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-dark">
                <x-lucide-send class="w-4 h-4"/>
                Send Email
            </button>
        </div>
    </form>
</section>
