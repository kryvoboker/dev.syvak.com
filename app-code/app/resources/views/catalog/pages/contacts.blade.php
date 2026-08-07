@php
    $contacts_data = $contacts_data ?? [];
    $working_hours = $contacts_data['working_hours'] ?? [];
    $map = $contacts_data['map'] ?? [];
    $form = $contacts_data['form'] ?? [];
    $form_fields = $form['fields'] ?? [];
@endphp

@extends('catalog.layouts.main')

@section('content')
    <section class="section contacts-page" id="contacts-page">
        <div class="container">
            <h1 class="font-cormorant-garamond text-4xl md:text-5xl lg:text-6xl">
                {{ $contacts_data['title'] ?? __('catalog/contacts.fallbacks.title') }}
            </h1>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(18rem,0.9fr)] lg:items-start lg:gap-10">
                @if(filled($map['iframe_src'] ?? null) || filled($map['coordinates_url'] ?? null))
                    <div class="order-2 flex min-w-0 flex-col gap-4 lg:order-1">
                        @if(filled($map['iframe_src'] ?? null))
                            <div class="w-full max-w-full overflow-hidden border border-opacity-light-gray-40% {{ $map['custom_css_classes'] ?? '' }}"
                                 style="max-width: {{ (int) ($map['width'] ?? 600) }}px;">
                                <iframe
                                    class="block w-full max-w-full"
                                    src="{{ $map['iframe_src'] }}"
                                    title="{{ __('catalog/contacts.labels.map') }}"
                                    width="{{ (int) ($map['width'] ?? 600) }}"
                                    height="{{ (int) ($map['height'] ?? 400) }}"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    style="height: min(70vw, {{ (int) ($map['height'] ?? 400) }}px);"
                                ></iframe>
                            </div>
                        @else
                            <a class="btn btn-outline btn-lg w-fit uppercase"
                               href="{{ $map['coordinates_url'] }}"
                               target="_blank"
                               rel="noopener noreferrer">
                                {{ __('catalog/contacts.buttons.open_map') }}
                            </a>
                        @endif
                    </div>
                @endif

                <form class="_needs-validation order-1 flex flex-col gap-5 border border-opacity-light-gray-40% bg-black p-5 md:p-8 lg:order-2"
                      action="{{ $form_action }}"
                      data-contacts-form
                      method="POST"
                      enctype="multipart/form-data"
                      novalidate>
                    @csrf

                    @if(session('contacts_form_success'))
                        <div class="alert alert-success" role="status">
                            {{ __('catalog/contacts.messages.success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        @foreach($errors->all() as $error)
                            <div class="alert alert-error removing:translate-x-5 removing:opacity-0 flex items-center gap-4 transition duration-300 ease-in-out"
                                 id="contacts-error-{{ $loop->index }}"
                                 role="alert">
                                <span>{{ $error }}</span>
                                <button class="ms-auto cursor-pointer leading-none"
                                        type="button"
                                        data-remove-element="#contacts-error-{{ $loop->index }}"
                                        aria-label="{{ __('catalog/contacts.buttons.close_error') }}">
                                    <span class="icon-[tabler--x] size-5"></span>
                                </button>
                            </div>
                        @endforeach
                    @endif

                    @foreach(['name', 'email', 'phone', 'text'] as $field_name)
                        @php
                            $field = is_array($form_fields[$field_name] ?? null) ? $form_fields[$field_name] : [];
                            $is_enabled = (bool) ($field['enabled'] ?? false);
                            $is_required = (bool) ($field['required'] ?? false);
                        @endphp

                        @if($is_enabled)
                            <label class="form-control w-full" for="contacts-{{ $field_name }}">
                                <span class="label-text text-base text-white md:text-lg">
                                    {{ __('catalog/contacts.fields.' . $field_name) }}@if($is_required)*@endif
                                </span>

                                @if($field_name === 'text')
                                    <textarea class="textarea textarea-bordered min-h-32 w-full bg-transparent text-white placeholder:text-light-gray"
                                              id="contacts-{{ $field_name }}"
                                              name="{{ $field_name }}"
                                              placeholder="{{ __('catalog/contacts.placeholders.' . $field_name) }}"
                                              aria-describedby="contacts-{{ $field_name }}-error"
                                              minlength="{{ filled($field['min_length'] ?? null) ? (int) $field['min_length'] : '' }}"
                                              maxlength="{{ filled($field['max_length'] ?? null) ? (int) $field['max_length'] : '' }}"
                                              @required($is_required)>{{ old($field_name) }}</textarea>
                                @else
                                    <input class="input input-bordered w-full bg-transparent text-white placeholder:text-light-gray"
                                           id="contacts-{{ $field_name }}"
                                           name="{{ $field_name }}"
                                           type="{{ $field_name === 'email' ? 'email' : ($field_name === 'phone' ? 'tel' : 'text') }}"
                                           value="{{ old($field_name) }}"
                                           placeholder="{{ __('catalog/contacts.placeholders.' . $field_name) }}"
                                           aria-describedby="contacts-{{ $field_name }}-error"
                                           minlength="{{ filled($field['min_length'] ?? null) ? (int) $field['min_length'] : '' }}"
                                           maxlength="{{ filled($field['max_length'] ?? null) ? (int) $field['max_length'] : '' }}"
                                           @if($field_name === 'phone')
                                               pattern="\+38 \(0\d{2}\) \d{3}-\d{2}-\d{2}"
                                           @endif
                                           @required($is_required) />
                                @endif

                                <span class="_error label-text-alt text-error"
                                      aria-live="polite"
                                      id="contacts-{{ $field_name }}-error">{{ __('catalog/contacts.validation.invalid') }}</span>
                            </label>
                        @endif
                    @endforeach

                    @php
                        $file_field = is_array($form_fields['file'] ?? null) ? $form_fields['file'] : [];
                    @endphp

                    @if(($file_field['enabled'] ?? false))
                        <label class="form-control w-full" for="contacts-file">
                            <span class="label-text text-base text-white md:text-lg">
                                {{ __('catalog/contacts.fields.file') }}@if(($file_field['required'] ?? false))*@endif
                            </span>
                            <input class="file-input file-input-bordered w-full bg-transparent text-white"
                                   id="contacts-file"
                                   name="file"
                                   type="file"
                                   accept="{{ collect((array) ($file_field['allowed_types'] ?? []))->map(fn (string $type): string => '.' . $type)->implode(',') }}"
                                   aria-describedby="contacts-file-error"
                                   @required(($file_field['required'] ?? false)) />
                            <span class="_error label-text-alt text-error"
                                  aria-live="polite"
                                  id="contacts-file-error">{{ __('catalog/contacts.validation.invalid_file') }}</span>
                        </label>
                    @endif

                    <button class="btn btn-primary btn-lg w-full uppercase" type="submit">
                        {{ __('catalog/contacts.buttons.submit') }}
                    </button>
                </form>
            </div>

            @if(!empty($contacts_data['images']))
                <div class="flex flex-wrap items-end justify-center gap-4 md:gap-6" aria-label="{{ __('catalog/contacts.labels.images') }}">
                    @foreach($contacts_data['images'] as $image)
                        <x-catalog::common.img
                            @class(['object-contain', $image['custom_css_classes'] ?? ''])
                            :urls_data="$image['urls']"
                            :size="$image['width']"
                            :max-density="3"
                            sizes="(max-width: 768px) 42vw, (max-width: 1280px) 28vw, 20vw"
                            width="{{ $image['width'] }}"
                            height="{{ $image['height'] }}"
                            alt=""
                            aria-hidden="true"
                        />
                    @endforeach
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" aria-label="{{ __('catalog/contacts.labels.contact_information') }}">
                <div class="flex flex-col gap-4 border border-opacity-light-gray-40% p-5 md:p-8">
                    <h2 class="text-2xl md:text-3xl">{{ $working_hours['title'] ?? __('catalog/contacts.fallbacks.working_hours_title') }}</h2>
                    @if(filled($working_hours['description'] ?? null))
                        <p class="text-light-gray">{{ $working_hours['description'] }}</p>
                    @endif
                    @if(filled($working_hours['content'] ?? null))
                        <p class="whitespace-pre-line font-semibold">{{ $working_hours['content'] }}</p>
                    @endif
                </div>

                <div class="flex flex-col gap-4 border border-opacity-light-gray-40% p-5 md:p-8">
                    <h2 class="text-2xl md:text-3xl">{{ __('catalog/contacts.labels.contact_details') }}</h2>
                    <div class="flex flex-col gap-2">
                        @foreach($contacts_data['phones'] ?? [] as $phone)
                            <a class="link link-hover text-lg" href="tel:{{ clear_telephone($phone['value']) }}">{{ parse_telephone($phone['value']) }}</a>
                        @endforeach
                        @foreach($contacts_data['emails'] ?? [] as $email)
                            <a class="link link-hover wrap-break-word text-lg" href="mailto:{{ $email }}">{{ $email }}</a>
                        @endforeach
                    </div>
                </div>

                @foreach($contacts_data['addresses'] ?? [] as $address)
                    <article class="flex flex-col gap-3 border border-opacity-light-gray-40% p-5 md:p-8">
                        <h2 class="text-2xl md:text-3xl">{{ $address['title'] }}</h2>
                        @if(filled($address['description'] ?? null))
                            <p class="text-light-gray">{{ $address['description'] }}</p>
                        @endif
                        <p class="whitespace-pre-line">{{ $address['value'] }}</p>
                        @if(filled($address['url'] ?? null))
                            <a class="btn btn-outline mt-auto w-fit uppercase" href="{{ $address['url'] }}" target="_blank" rel="noopener noreferrer">
                                {{ __('catalog/contacts.buttons.open_address') }}
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
