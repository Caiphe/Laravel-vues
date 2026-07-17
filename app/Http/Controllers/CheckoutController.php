<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Service\CartService;
use App\Service\EmailService;
use App\Service\OrderService;
use App\Service\PaymentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\DTO\Cart\CartItemCollection;

class CheckoutController extends Controller
{
    private const ORDER_PROCESSING_CACHE_STATUS = 'order_processing';
    private const EMAIL_MAX_ATTEMPTS = 3;
    private const EMAIL_RETRY_DELAY_MS = 300;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
        private readonly EmailService $emailService,
        private readonly CartService $cartService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Checkout');
    }

    /**
     * @throws Exception
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        if ($this->orderIsBeingProcessed($request)) {
            return redirect()->back()->withErrors(['order' => 'An order is already being processed. Please wait.']);
        }

        $cart = $this->cartService->createCartFromRequest($request);

        $this->startProcessingOrder($request);

        try {
            $paymentResponse = $this->paymentService->processPayment(
                $cart->getPaymentDetails(),
                $cart->getTotal()
            );

            if (! $paymentResponse->success) {
                $this->stopProcessingOrder($request);

                return redirect()->back()->withErrors(['payment' => $paymentResponse->message]);
            }

            $order = $this->orderService->createOrder($cart, $request->user(), $paymentResponse);
            $this->sendOrderConfirmationWithRetry($order, $cart->getItems());

            $this->stopProcessingOrder($request);

            return redirect()->route('checkout.confirmation', $order);
        } catch (Exception $e) {
            $this->stopProcessingOrder($request);

            // Log the exception properly to avoid malformed JSON
            Log::error('Order processing failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function confirmation(Order $order): Response
    {
        $order->load('items.product');

        return Inertia::render('OrderConfirmation', [
            'order' => $order,
        ]);
    }

    private function orderIsBeingProcessed(StoreOrderRequest $request): bool
    {
        return $request->session()->has(self::ORDER_PROCESSING_CACHE_STATUS);
    }

    private function startProcessingOrder(StoreOrderRequest $request): void
    {
        $request->session()->put(self::ORDER_PROCESSING_CACHE_STATUS, true);
    }

    private function stopProcessingOrder(StoreOrderRequest $request): void
    {
        $request->session()->forget(self::ORDER_PROCESSING_CACHE_STATUS);
    }

    private function sendOrderConfirmationWithRetry(Order $order, CartItemCollection $cartItems): void
    {
        try {
            retry(self::EMAIL_MAX_ATTEMPTS, function () use ($order, $cartItems) {
                $sent = $this->emailService->sendOrderConfirmationEmail($order, $cartItems);

                if (! $sent) {
                    throw new Exception('Order confirmation email attempt failed.');
                }

                return true;
            }, self::EMAIL_RETRY_DELAY_MS);
        } catch (Exception $e) {
            Log::error('Order confirmation email failed after retries', [
                'order_number' => $order->order_number,
                'email' => $order->shipping_email,
                'attempts' => self::EMAIL_MAX_ATTEMPTS,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
