<div class="category__content-container">
    @include('storefront.pages.partials.category.products-list')

    @include('storefront.pages.partials.category.load-more-btn')

    {{ $paginator?->links('pagination::tailwind') }}
</div>

<script>
    window.app_params = {
        ...(window.app_params ?? {}),
        ...@js([
                'next_page' => $paginator?->currentPage() !== null ? $paginator?->currentPage() + 1 : null,
                'is_has_more_pages' => $is_has_more_pages ?? null,
            ])
    };
</script>
