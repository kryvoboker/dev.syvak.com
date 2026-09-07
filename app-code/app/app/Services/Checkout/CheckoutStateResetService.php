<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use Modules\NovaPoshta\Support\NovaPoshtaCheckoutStateService;
use Modules\UkrPoshta\Support\UkrPoshtaCheckoutStateService;

final readonly class CheckoutStateResetService
{
    public function __construct(
        private CheckoutSelectionStateService $checkout_selection_state_service,
        private NovaPoshtaCheckoutStateService $nova_poshta_checkout_state_service,
        private UkrPoshtaCheckoutStateService $ukr_poshta_checkout_state_service,
    ) {
    }

    public function resetAfterOrder(): void
    {
        $this->checkout_selection_state_service->resetAfterOrder();
        $this->nova_poshta_checkout_state_service->clear();
        $this->ukr_poshta_checkout_state_service->clear();
    }
}
