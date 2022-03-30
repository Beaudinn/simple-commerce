<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GenericResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function toArray($request)
	{
		return parent::toArray($request);
	}
}
