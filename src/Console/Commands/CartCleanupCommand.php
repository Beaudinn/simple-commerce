<?php

namespace DoubleThreeDigital\SimpleCommerce\Console\Commands;

use DoubleThreeDigital\SimpleCommerce\Orders\States\Draft;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class CartCleanupCommand extends Command
{
    use RunsInPlease;

    protected $name = 'sc:cart-cleanup {days}';
    protected $description = 'Cleanup carts older than 14 days.';

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
	        $this->info('Go..');
            // $this->hasArgument('days') ? now()->subDays($this->argument('days') ?? 2) : now()->subDays(2)
            (new $orderModelClass)
	            ->whereState('state', Draft::class)
                ->where('is_paid', 0)
	            ->where(function ($query){
		            $query->where('agent_ip', '165.22.195.209');
		            $query->orWhere('created_at', '<', now()->subDays(3));
	            })
                ->each(function ($model) {
                    $this->line("Deleting order: {$model->id}");

                    $model->delete();
                });

            return;
        }

        return $this->error('Unable to cleanup carts with provided cart driver.');
    }
}
