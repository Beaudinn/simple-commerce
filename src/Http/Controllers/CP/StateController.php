<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers\CP;


use DoubleThreeDigital\Runway\Runway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Action;

class StateController
{
	public function edit(Request $request)
	{
		$request->validate([
			'state' => 'required',
			'resource' => 'required',
			'currentRecord' => 'required',
			'values' => 'array',
		]);
		$resource = Runway::findResource($request->resource);
		$record = $resource->model()->where($resource->routeKey(), $request->currentRecord[$resource->routeKey()])->first();

		$stateClass = new ($request->state)($record);

		$blueprint = $stateClass->blueprint($record);

		$fields = $blueprint
			->fields()
			->addValues($record->toArray())
			->preProcess();

		return [
			'blueprint' => $blueprint->toPublishArray(),
			'values' =>  $fields->values()->all(),
			'meta' => $fields->meta(),
			'actions' => Action::for($stateClass),
		];
	}

	public function update(Request $request)
	{
		$request->validate([
			'handle' => 'required',
			'state' => 'required',
			'resource' => 'required',
			'currentRecord' => 'required',
			'values' => 'array',
		]);

		$resource = Runway::findResource($request->resource);
		$record = $resource->model()->where($resource->routeKey(), $request->currentRecord[$resource->routeKey()])->first();


		$stateClass = new ($request->state)($record);

		$blueprint = $stateClass->blueprint($record);

		$fields = $blueprint
			->fields()
			->addValues($request->values)
			->process();

		//try {
			$record[$request->handle]->transitionTo($request->state, $fields->values()->all());
			//$payment->state->transition(new CreatedToFailed($payment, 'error message'));

			//$values = array_merge($request->values, $fields->values()->all());

			//$order->disableLogging();
			//activity()
			//	->performedOn($supplierOrder)
			//	->log('Status changed to '.$stateClass->name());
			//$order->enableLogging();

			session()->flash('success', 'Status aangepast');
			return;
		//} catch (ApiException $e) {
		//
		//	Log::channel('slack')->critical('States API change error', [
		//		'message' => $e->getMessage()
		//	]);
		//	return ;
		//} catch (\Throwable $e) {
		//
		//	session()->flash('error', $e->getMessage());
		//	Log::channel('slack')->critical('States change error', ['response' => $e]);
		//	return ;
		//}



		$fieldtype = FieldtypeRepository::find($request->type);

		$blueprint = $this->blueprint($fieldtype->configBlueprint());

		$fields = $blueprint
			->fields()
			->addValues($request->values)
			->process();

		$values = array_merge($request->values, $fields->values()->all());

	}
}
