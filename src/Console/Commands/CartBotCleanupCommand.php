<?php

namespace DoubleThreeDigital\SimpleCommerce\Console\Commands;

use DoubleThreeDigital\SimpleCommerce\Orders\States\Draft;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class CartBotCleanupCommand extends Command
{
    use RunsInPlease;

    protected $name = 'sc:cart-bot-cleanup';
    protected $description = 'Cleanup carts created by bots';

    public function handle()
    {
        $this->info('Cleaning up..');

        //if (isset(SimpleCommerce::orderDriver()['collection'])) {
        //    Entry::whereCollection(SimpleCommerce::orderDriver()['collection'])
        //        ->where('is_paid', false)
        //        ->filter(function ($entry) {
        //            return $entry->date()->isBefore(now()->subDays(14));
        //        })
        //        ->each(function ($entry) {
        //            $this->line("Deleting order: {$entry->id()}");
		//
        //            $entry->delete();
        //        });
		//
        //    return;
        //}

        if (isset(SimpleCommerce::orderDriver()['model'])) {
            $orderModelClass = SimpleCommerce::orderDriver()['model'];

            (new $orderModelClass)
                ->query()
	            ->whereState('state', Draft::class)
                ->where('is_bot', true)
                ->each(function ($model) {
                    $this->line("Deleting order: {$model->id}");

                    $model->delete();
                });

            return;
        }

        return $this->error('Unable to cleanup carts with provided cart driver.');
    }
}
