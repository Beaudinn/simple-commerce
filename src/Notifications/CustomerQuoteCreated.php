<?php

namespace DoubleThreeDigital\SimpleCommerce\Notifications;

use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerQuoteCreated extends Notification
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
	    $action = new LoginAction( $this->order->customer()->resource());
	    $action->guard('web')->response(redirect(route('quotations.show', $this->order->resource()->id)));
	    $link_show = MagicLink::create($action)->url;

        return (new MailMessage)
	        ->from('info@drukhoek.nl', $this->order->site()->attributes()['name'])
	        ->bcc('verkoop@xpressing.nl')
	        ->subject(trans('strings.notification.quote.created.subject', ['order_number' => $this->order->orderNumber()]))
	        ->view('simple-commerce::emails.customer_quote_created', [
                'order' => $this->order,
		        'values' => $this->values,
		        'link_show' => $link_show
            ]);
    }
}
