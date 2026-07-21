export const $DELIVERY_METHOD = {
    NOVA_POSHTA: 'nova_poshta',
    NOVA_POSHTA_POSHTOMAT: 'nova_poshta_poshtomat',
    NOVA_POSHTA_COURIER: 'nova_poshta_courier',
    UKR_POSHTA: 'ukr_poshta',
    PICKUP_STORE: 'pickup_store',
} as const;

export const $CHECKOUT_FIELD_NAME = {
    NO_CALL: 'no_call',
    DELIVERY_METHOD: 'delivery_method',
    PAYMENT_METHOD: 'payment_method',
} as const;

export type CheckoutDeliveryMethod = (typeof $DELIVERY_METHOD)[keyof typeof $DELIVERY_METHOD];
