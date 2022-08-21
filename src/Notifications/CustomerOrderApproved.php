<?php

namespace DoubleThreeDigital\SimpleCommerce\Notifications;

use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerOrderApproved extends Notification
{
    use Queueable;

    protected $order;

	protected $values;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order, $values = [])
    {
        $this->order = $order;
        $this->values = $values;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
	        ->from('info@drukhoek.nl', $this->order->site()->attributes()['name'])
	        ->cc('verkoop@xpressing.nl')
	        ->subject(trans('strings.notification.order.confirmation.subject', ['order_number' => $this->order->orderNumber()]))
	        ->view('simple-commerce::emails.customer_order_approved', [
                'order' => $this->order,
		        'values' => $this->values,
            ]);
    }
}
