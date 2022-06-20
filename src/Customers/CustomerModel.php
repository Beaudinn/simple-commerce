<?php

namespace DoubleThreeDigital\SimpleCommerce\Customers;

use App\Models\Address;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerModel extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $guarded = [];


    protected $casts = [
        'data' => 'json',
    ];

	protected $appends = [
		'title',
	];


	public function getTitleAttribute()
	{
		if($this->company_name){
			return "{$this->company_name} {$this->first_name} {$this->last_name}";
		}
		return "{$this->first_name} {$this->last_name}";
	}



	public function getMorphClass()
	{
		return 'customer';
	}

    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'customer_id');
    }

	/**
	 * Get all attached addresses to the model.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function addresses(): MorphMany
	{
		return $this->morphMany(Address::class, 'addressable', 'addressable_type', 'addressable_id');
	}
}
