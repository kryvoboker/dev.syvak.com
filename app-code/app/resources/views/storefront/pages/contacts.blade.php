@php
    $contacts_data = $contacts_data ?? [];
    $working_hours = $contacts_data['working_hours'] ?? [];
    $map = $contacts_data['map'] ?? [];
    $form = $contacts_data['form'] ?? [];
    $form_fields = $form['fields'] ?? [];
    $width = (int) ($map['width'] ?? 600) / 16;
    $heigh = (int) ($map['height'] ?? 400) / 16;
@endphp

@extends('storefront.layouts.main')

@section('content')
    <section class="section contacts-page" id="contacts-page">
        <div class="container">
            <h1 class="section-title">
                {{ $contacts_data['title'] ?? __('storefront/contacts.fallbacks.title') }}
            </h1>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(18rem,0.9fr)] lg:items-start lg:gap-10">
                @if(filled($map['iframe_src'] ?? null) || filled($map['coordinates_url'] ?? null))
                    <div class="order-2 flex min-w-0 flex-col gap-4 lg:order-1">
                        @if(filled($map['iframe_src'] ?? null))
                            <div class="w-full max-w-full overflow-hidden border border-opacity-light-gray-40% {{ $map['custom_css_classes'] ?? '' }}"
                                 style="max-width: {{ $width }}rem;">
                                <iframe
                                    class="block w-full max-w-full"
                                    src="{{ $map['iframe_src'] }}"
                                    title="{{ __('storefront/contacts.labels.map') }}"
                                    width="{{ $width }}rem"
                                    height="{{ $heigh }}rem"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    style="height: min(70vw, {{ $heigh }}rem);"
                                ></iframe>
                            </div>
                        @else
                            <a class="btn btn-outline btn-lg w-fit uppercase"
                               href="{{ $map['coordinates_url'] }}"
                               target="_blank"
                               rel="noopener noreferrer">
                                {{ __('storefront/contacts.buttons.open_map') }}
                            </a>
                        @endif
                    </div>
                @endif

                <form class="_needs-validation order-1 flex flex-col gap-4 lg:order-2"
                      action="{{ $form_action }}"
                      data-contacts-form
                      method="POST"
                      enctype="multipart/form-data"
                      novalidate>
                    @csrf

                    @if(session('contacts_form_success'))
                        <div class="alert alert-success" role="status">
                            {{ __('storefront/contacts.messages.success') }}
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
                                        aria-label="{{ __('storefront/contacts.buttons.close_error') }}">
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
                            <label class="form-control w-full gap-2 border-b border-opacity-light-gray-40% pb-2" for="contacts-{{ $field_name }}">
                                <span class="label-text text-lg font-normal text-white">
                                    {{ __('storefront/contacts.fields.' . $field_name) }}@if($is_required)
                                        *
                                    @endif
                                </span>

                                @if($field_name === 'text')
                                    <textarea class="textarea min-h-10 w-full border-0 bg-transparent px-4 py-2 text-lg font-light tracking-0.04em text-white placeholder:text-secondary focus:border-0"
                                              id="contacts-{{ $field_name }}"
                                              name="{{ $field_name }}"
                                              placeholder="{{ __('storefront/contacts.placeholders.' . $field_name) }}"
                                              aria-describedby="contacts-{{ $field_name }}-error"
                                              minlength="{{ filled($field['min_length'] ?? null) ? (int) $field['min_length'] : '' }}"
                                              maxlength="{{ filled($field['max_length'] ?? null) ? (int) $field['max_length'] : '' }}"
                                              @required($is_required)>{{ old($field_name) }}</textarea>
                                @else
                                    <input class="input w-full border-0 bg-transparent px-4 py-2 text-lg font-light tracking-0.04em text-white placeholder:text-secondary focus:border-0"
                                           id="contacts-{{ $field_name }}"
                                           name="{{ $field_name }}"
                                           type="{{ $field_name === 'email' ? 'email' : ($field_name === 'phone' ? 'tel' : 'text') }}"
                                           value="{{ old($field_name) }}"
                                           placeholder="{{ __('storefront/contacts.placeholders.' . $field_name) }}"
                                           aria-describedby="contacts-{{ $field_name }}-error"
                                           minlength="{{ filled($field['min_length'] ?? null) ? (int) $field['min_length'] : '' }}"
                                           maxlength="{{ filled($field['max_length'] ?? null) ? (int) $field['max_length'] : '' }}"
                                        @required($is_required) />
                                @endif

                                <span class="_error label-text-alt text-error"
                                      aria-live="polite"
                                      id="contacts-{{ $field_name }}-error">{{ __('storefront/contacts.validation.invalid') }}</span>
                            </label>
                        @endif
                    @endforeach

                    @php
                        $file_field = is_array($form_fields['file'] ?? null) ? $form_fields['file'] : [];
                        $file_accepts = collect((array) ($file_field['allowed_types'] ?? []))
                            ->map(fn (string $type): string => '.' . $type)
                            ->implode(',');
                    @endphp

                    @if(($file_field['enabled'] ?? false))
                        <label class="input-floating w-full">
                            @if(($file_field['required'] ?? false))
                                <span class="absolute top-0 left-0">
                                    *
                                </span>
                            @endif
                            <input class="input border-(--btn-border)"
                                   id="contacts-file"
                                   name="file"
                                   type="file"
                                   accept="{{ $file_accepts }}"
                                   aria-label="file-input"
                                   aria-describedby="contacts-file-error"
                                @required(($file_field['required'] ?? false)) />
                            <span class="_error label-text-alt text-error"
                                  aria-live="polite"
                                  id="contacts-file-error">{{ __('storefront/contacts.validation.invalid_file') }}</span>
                        </label>
                    @endif

                    <button class="btn w-full text-2xl" type="submit">
                        {{ __('storefront/contacts.buttons.submit') }}
                    </button>
                </form>
            </div>

            @if(!empty($contacts_data['images']))
                <div class="flex flex-wrap items-end justify-center gap-4 md:gap-6" aria-label="{{ __('storefront/contacts.labels.images') }}">
                    @foreach($contacts_data['images'] as $image)
                        <x-storefront::common.img
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

            <div class="mt-8 grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-[auto_minmax(0,1fr)]" aria-label="{{ __('storefront/contacts.labels.contact_information') }}">
                <x-storefront::contacts.working-hours
                    :working_hours="$working_hours"
                    :phones="$contacts_data['phones'] ?? []"
                    :emails="$contacts_data['emails'] ?? []"
                />

                <div class="flex flex-wrap gap-4">
                    @foreach($contacts_data['addresses'] ?? [] as $address)
                        <article class="flex flex-col gap-y-2 max-bp1920px:w-full">
                            <div class="card">
                                <div class="card-body p-4 md:px-8 md:py-4">
                                    <h4 class="font-inter font-medium text-2xl leading-tight normal-case">
                                        {{ $address['title'] }}
                                    </h4>

                                    <p class="whitespace-pre-line text-lg font-semibold leading-tight">{{ $address['value'] }}</p>

                                    @if(filled($address['description'] ?? null))
                                        <p class="text-lg leading-tight text-secondary font-light">{{ $address['description'] }}</p>
                                    @endif
                                </div>
                            </div>

                            @if(filled($address['url'] ?? null))
                                <a class="btn w-full text-2xl"
                                   href="{{ $address['url'] }}"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    {{ __('storefront/contacts.buttons.open_address') }}
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
