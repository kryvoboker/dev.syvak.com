@props([
    'working_hours' => [],
    'fallback_title' => null,
    'phones' => [],
    'emails' => [],
])

<article class="flex flex-col gap-y-2">
    <div class="card">
        <div class="card-body p-4 md:px-8 md:py-4">
            <h4 class="font-inter font-medium text-2xl leading-tight normal-case">
                {{ $working_hours['title'] ?? $fallback_title ?? __('catalog/contacts.fallbacks.working_hours_title') }}
            </h4>

            @if(filled($working_hours['content'] ?? null))
                <p class="whitespace-pre-line text-lg font-semibold leading-tight">{{ $working_hours['content'] }}</p>
            @endif

            @if(filled($working_hours['description'] ?? null))
                <p class="text-lg leading-tight text-secondary font-light">{{ $working_hours['description'] }}</p>
            @endif
        </div>
    </div>

    @foreach($phones as $phone)
        @php
            $phone_value = is_array($phone) ? ($phone['value'] ?? '') : $phone;
        @endphp

        <a class="btn w-full text-2xl"
           href="tel:{{ clear_telephone($phone_value) }}">
            {{ parse_telephone($phone_value) }}
        </a>
    @endforeach

    @foreach($emails as $email)
        @php
            $email_value = is_array($email) ? ($email['value'] ?? '') : $email;
        @endphp

        <a class="btn btn-black w-full normal-case! wrap-break-word"
           href="mailto:{{ $email_value }}">
            {{ $email_value }}
        </a>
    @endforeach
</article>
