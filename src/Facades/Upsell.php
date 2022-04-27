<?php

namespace DoubleThreeDigital\SimpleCommerce\Facades;

use DoubleThreeDigital\SimpleCommerce\Contracts\UpsellRepository;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array all()
 * @method static \Statamic\Entries\EntryCollection query()
 * @method static \DoubleThreeDigital\SimpleCommerce\Contracts\Upsell find(string $id)
 * @method static \DoubleThreeDigital\SimpleCommerce\Contracts\Upsell findByCode(string $code)
 * @method static \DoubleThreeDigital\SimpleCommerce\Contracts\Upsell create(array $data = [], string $site = '')
 */
class Upsell extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UpsellRepository::class;
    }
}
