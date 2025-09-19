<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderDelayed extends Notification
{
    use Queueable;

    protected $order;
    public function __construct($order) { $this->order = $order; }

    public function via($notifiable) { return ['database']; }

    public function toArray($notifiable)
    {
        return [
            'message' => "Order #{$this->order->id} is delayed.",
            'order_id' => $this->order->id,
            'status' => $this->order->status,
        ];
    }
}
