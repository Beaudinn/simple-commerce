<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers\CP;


use DoubleThreeDigital\Runway\Runway;
use Illuminate\Http\Request;
use Statamic\Facades\Action;
use Symfony\Component\HttpFoundation\Response;

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
			'values' => $fields->values()->all(),
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


		$fields = $stateClass->blueprint($record)->fields()->addValues($request->values); //$request->values?

		$fields->validate();

		$values = $fields->process();

		//try {
		$response = 'not used yet';
		$record[$request->handle]->transitionTo($request->state, $values->values()->all());
		//$payment->state->transition(new CreatedToFailed($payment, 'error message'));

		//$values = array_merge($request->values, $fields->values()->all());

		//$order->disableLogging();
		//activity()
		//	->performedOn($supplierOrder)
		//	->log('Status changed to '.$stateClass->name());
		//$order->enableLogging();

		if ($redirect = $stateClass->redirect($record, $values)) {
			return ['redirect' => $redirect];
		} elseif ($download = $stateClass->download($record, $values)) {
			return $download instanceof Response ? $download : response()->download($download);
		}

		if (is_string($response)) {
			return ['message' => $response];
		}

		return $response ?: [];
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
