@php($contact = config('school.contact'))

<div class="topbar on-dark">
    <div class="wrap">
        <div class="tb-left">
            @if ($hours = config('school.office_hours')[0] ?? null)
                <span><x-icon name="clock" /> {{ $hours }}</span>
            @endif
            <a href="tel:{{ $contact['phone_link'] }}"><x-icon name="phone" /> {{ $contact['phone'] }}</a>
        </div>
        <div class="tb-right">
            <a href="mailto:{{ $contact['email'] }}"><x-icon name="mail" /> {{ $contact['email'] }}</a>
        </div>
    </div>
</div>
