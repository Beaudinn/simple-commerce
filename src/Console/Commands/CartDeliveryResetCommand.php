<?php

namespace DoubleThreeDigital\SimpleCommerce\Console\Commands;

use DoubleThreeDigital\SimpleCommerce\Orders\States\Draft;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class CartDeliveryResetCommand extends Command
{
    use RunsInPlease;

    protected $name = 'sc:cart-delivery-reset';
    protected $description = 'Reset delivery date around 21:30 a clock.';

    public function handle()
    {
        $this->info('Reseting delivery date..');


        if (isset(SimpleCommerce::orderDriver()['model'])) {
            $orderModelClass = SimpleCommerce::orderDriver()['model'];

            (new $orderModelClass)
                ->query()
	            ->whereState('state', Draft::class)
                ->where('is_paid', false)
                ->each(function ($model) {
                    $this->line("Reseting delivery at for cart: {$model->id}");

                    $model->delivery_at = null;
	                $model->shipping_method = null;
                    $model->save();
                });

            return;
        }

        return $this->error('Unable to cleanup carts with provided cart driver.');
    }
}
