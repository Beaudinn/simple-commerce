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


	public function scopeRunwaySearch($query, $searchTerm)
	{
		return $query->where('email', 'LIKE', "%{$searchTerm}%")
			->orWhere('id', 'LIKE', "%{$searchTerm}%")
			->orWhere('company_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
			->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
			->orWhereHas('orders', function ($query) use ($searchTerm) {
				$query->where('order_number', 'LIKE', "%{$searchTerm}%");
			});
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
