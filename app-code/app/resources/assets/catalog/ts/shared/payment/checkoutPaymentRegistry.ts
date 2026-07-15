export type CheckoutPaymentHandler = (
    response: Record<string, unknown>,
    showError: (message: string) => void,
) => Promise<boolean>;

const paymentHandlers = new Map<string, CheckoutPaymentHandler>();

export const registerCheckoutPaymentHandler = (paymentMethod: string, handler: CheckoutPaymentHandler): void => {
    paymentHandlers.set(paymentMethod, handler);
};

export const handleCheckoutPayment = async (
    paymentMethod: string,
    response: Record<string, unknown>,
    showError: (message: string) => void,
): Promise<boolean> => {
    const handler = paymentHandlers.get(paymentMethod);

    if (!handler) {
        return false;
    }

    return handler(response, showError);
};
