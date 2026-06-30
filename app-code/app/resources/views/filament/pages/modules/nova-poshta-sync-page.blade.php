<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ __('admin/modules/nova_poshta.sections.sync.title') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('admin/modules/nova_poshta.sections.sync.description') }}
            </p>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">
                        {{ __('admin/modules/nova_poshta.stats.regions') }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ \Modules\NovaPoshta\Models\NovaPoshtaRegion::query()->count() }}
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">
                        {{ __('admin/modules/nova_poshta.stats.cities') }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ \Modules\NovaPoshta\Models\NovaPoshtaCity::query()->count() }}
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">
                        {{ __('admin/modules/nova_poshta.stats.post_offices') }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ \Modules\NovaPoshta\Models\NovaPoshtaPostOffice::query()->count() }}
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">
                        {{ __('admin/modules/nova_poshta.stats.poshtomats') }}
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ \Modules\NovaPoshta\Models\NovaPoshtaPoshtomat::query()->count() }}
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ __('admin/modules/nova_poshta.sections.summary.title') }}
            </h2>

            @if(! empty($last_sync_summary))
                <pre class="mt-4 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($last_sync_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('admin/modules/nova_poshta.sections.summary.empty') }}
                </p>
            @endif
        </section>
    </div>
</x-filament-panels::page>
