<?php

namespace DoubleThreeDigital\SimpleCommerce\Upsells;

use DoubleThreeDigital\SimpleCommerce\Contracts\Upsell;
use DoubleThreeDigital\SimpleCommerce\Contracts\UpsellRepository as RepositoryContract;
use DoubleThreeDigital\SimpleCommerce\Exceptions\UpsellNotFound;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Support\Arr;
use Statamic\Facades\Entry;
use Statamic\Facades\Stache;

class EntryUpsellRepository implements RepositoryContract
{
    protected $collection;

    public function __construct()
    {
        $this->collection = SimpleCommerce::upsellDriver()['collection'];
    }

    public function all()
    {
        return Entry::whereCollection($this->collection)->all();
    }

    public function find($id): ?Upsell
    {
        $entry = Entry::find($id);

        if (! $entry) {
            throw new UpsellNotFound("Upsell [{$id}] could not be found.");
        }

        return app(Upsell::class)
            ->resource($entry)
            ->id($entry->id())
            ->code($entry->slug())
            ->options($entry->get('options'))
            ->multiple($entry->get('multiple'))
            ->data(array_merge(
                $entry->data()->except(['options', 'multiple'])->toArray(),
                [
                    'site' => optional($entry->site())->handle(),
                    'slug' => $entry->slug(),
                    'published' => $entry->published(),
                ]
            ));
    }

    public function findByCode(string $code): ?Upsell
    {
        $entry = Entry::query()
            ->where('collection', $this->collection)
            ->where('slug', $code)
            ->first();

        if (! $entry) {
            throw new UpsellNotFound("Upsell [{$code}] could not be found.");
        }

        return $this->find($entry->id());
    }

    public function make(): Upsell
    {
        return app(Upsell::class);
    }

    public function save($upsell): void
    {
        $entry = $upsell->resource();

        if (! $entry) {
            $entry = Entry::make()
                ->id(Stache::generateId())
                ->collection($this->collection);
        }

        if ($upsell->get('site')) {
            $entry->site($upsell->get('site'));
        }

        $entry->slug($upsell->code());

        if ($upsell->get('published')) {
            $entry->published($upsell->get('published'));
        }

        $entry->data(
            array_merge(
                Arr::except($upsell->data()->toArray(), ['id', 'site', 'slug', 'published']),
                [
                    'value' => $upsell->value(),
                    'type' => $upsell->type(),
                ]
            )
        );

        $entry->save();

        $upsell->id = $entry->id();
        $upsell->code = $entry->slug();
        $upsell->value = $entry->get('value');
        $upsell->type = $entry->get('type');
        $upsell->resource = $entry;

        $upsell->merge([
            'site' => $entry->site()->handle(),
            'slug' => $entry->slug(),
            'published' => $entry->published(),
        ]);
    }

    public function delete($upsell): void
    {
        $upsell->resource()->delete();
    }

    protected function isUsingEloquentDriverWithIncrementingIds(): bool
    {
        return config('statamic.eloquent-driver.entries.model') === \Statamic\Eloquent\Entries\EntryModel::class;
    }

    public static function bindings(): array
    {
        return [];
    }
}
