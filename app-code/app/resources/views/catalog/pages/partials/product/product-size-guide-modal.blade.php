@if(filled($product_view_data['size_guide']))
    <div class="product-size-guide__modal overlay modal overlay-open:opacity-100 overlay-open:duration-300 hidden p-0"
         id="fullscreen-modal"
         role="dialog"
         tabindex="-1">
        <div class="modal-dialog max-w-full lg:max-w-180 bp1440px:max-w-300 md:overflow-hidden p-0">
            <div class="modal-shadow modal-content h-full max-h-none md:max-h-190 lg:max-h-200 bp1440px:max-h-11/12 justify-between px-3.5 py-5.5 md:p-7.5 bp1440px:px-20 bp1440px:py-15">
                <div class="modal-body grow max-md:overflow-y-hidden p-0">
                    <div class="grid grid-cols-1 md:grid-cols-[auto_1fr] grid-rows-[repeat(5,auto)] md:grid-rows-[repeat(4,auto)] gap-y-4 md:gap-y-5 md:gap-x-6 bp1440px:gap-x-5">
                        <div class="md:col-start-1 md:col-end-3 xl:col-start-2 xl:col-end-3 flex items-center xl:items-start justify-between gap-x-2 md:mb-2 bp1440px:mb-3">
                            <h3 class="modal-title">
                                {{ $product_view_data['size_guide']['title'] }}
                            </h3>

                            <button type="button" class="btn btn-text btn-circle btn-sm"
                                    aria-label="Close"
                                    data-overlay="#fullscreen-modal">
                                <span class="custom-icon icon-[tabler--x]"></span>
                            </button>
                        </div>

                        <div class="md:col-start-1 md:col-end-3 xl:col-start-2 xl:col-end-3">
                            {!! $product_view_data['size_guide']['short_description'] !!}
                        </div>

                        <div class="md:col-start-2 md:col-end-3 md:row-start-3 md:row-end-4 md:self-end w-full overflow-x-auto">
                            <table class="table">
                                <thead>
                                <tr>
                                    @foreach($product_view_data['size_guide']['table_rows'] as $table_cols)
                                        @continue($loop->first === false)

                                        @foreach($table_cols as $table_col)
                                            <th class="{{ filled($table_col) ? 'border border-opacity-light-gray-40%' : '' }}">
                                                {{ $table_col }}
                                            </th>
                                        @endforeach
                                    @endforeach
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($product_view_data['size_guide']['table_rows'] as $table_cols)
                                    @continue($loop->first === true)

                                    <tr>
                                        @foreach($table_cols as $table_col)
                                            <td class="border border-opacity-light-gray-40%">
                                                {{ $table_col }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <x-catalog::common.img
                            class="md:col-start-1 md:col-end-2 md:row-start-3 md:row-end-4 lg:row-end-5 xl:row-start-1 size-full lg:max-w-70 bp1440px:max-w-120 grow object-contain"
                            :urls_data="$product_view_data['size_guide']['image']['urls']"
                            :size="$product_view_data['size_guide']['image']['width']"
                            :max-density="3"
                            sizes="100vw"
                            width="{{ $product_view_data['size_guide']['image']['width'] }}"
                            height="{{ $product_view_data['size_guide']['image']['height'] }}"
                            alt="{{ strip_tags($product_view_data['size_guide']['title']) }}"
                        />

                        <div class="md:col-start-1 md:col-end-3 xl:col-start-2 xl:col-end-3">
                            <div class="text-light-gray mb-2">
                                {{ $product_view_data['size_guide']['full_description_title'] }}
                            </div>

                            <div class="min-h-50 h-full border border-white p-2">
                                {!! $product_view_data['size_guide']['full_description'] !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
