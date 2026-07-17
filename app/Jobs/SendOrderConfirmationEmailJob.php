<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\Cart\CartItemCollection;
use App\Models\Order;
use App\Service\EmailService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderConfirmationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [1, 2];

    public function __construct(
        private readonly int $orderId,
        private readonly array $cartItems
    ) {}

    public function handle(EmailService $emailService): void
    {
        $order = Order::find($this->orderId);

        if (! $order) {
            Log::warning('Skipping order confirmation email job because order was not found', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $cartItemCollection = new CartItemCollection();

        foreach ($this->cartItems as $item) {
            $cartItemCollection->addItem($item);
        }

        $sent = $emailService->sendOrderConfirmationEmail($order, $cartItemCollection);

        if (! $sent) {
            throw new Exception('Order confirmation email attempt failed.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $order = Order::find($this->orderId);

        Log::error('Order confirmation email failed after retries', [
            'order_number' => $order?->order_number,
            'email' => $order?->shipping_email,
            'attempts' => $this->tries,
            'message' => $exception?->getMessage(),
        ]);
    }
}
