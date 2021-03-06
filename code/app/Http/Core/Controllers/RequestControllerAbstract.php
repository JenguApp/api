<?php
declare(strict_types=1);

namespace App\Http\Core\Controllers;

use App\Contracts\Repositories\Request\RequestRepositoryContract;
use App\Events\Request\LocationRequestedItemSelectedEvent;
use App\Http\Core\Controllers\Traits\HasIndexRequests;
use App\Http\Core\Requests;
use App\Models\BaseModelAbstract;
use App\Models\Request\Request;
use App\Traits\CanGetAndUnset;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Class RequestControllerAbstract
 * @package App\Http\Core\Controllers
 */
abstract class RequestControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests, CanGetAndUnset;

    /**
     * @var RequestRepositoryContract
     */
    private RequestRepositoryContract $repository;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * RequestControllerAbstract constructor.
     * @param RequestRepositoryContract $repository
     * @param Dispatcher $dispatcher
     */
    public function __construct(RequestRepositoryContract $repository, Dispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Requests\Request\IndexRequest $request
     * @return LengthAwarePaginator|Collection
     */
    public function index(Requests\Request\IndexRequest $request)
    {
        $radius = $request->input('radius');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if ($radius && $latitude && $longitude) {
            $filter = $this->filter($request);
            $filter [] = [
                'completed_by_id',
                null,
                null,
            ];
            $filter [] = [
                'canceled_at',
                null,
                null,
            ];
            return $this->repository->findAllAroundLocation((float) $latitude, (float) $longitude, (float) $radius, $filter, $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int)$request->input('page', 1));
        }

        return $this->repository->findAll($this->filter($request), $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int)$request->input('page', 1));
    }

    /**
     * @param Requests\Request\RetrieveRequest $request
     * @param Request $requestModel
     * @return Request
     */
    public function show(Requests\Request\RetrieveRequest $request, Request $requestModel)
    {
        return $requestModel->load($this->expand($request));
    }

    /**
     * @param Requests\Request\StoreRequest $request
     * @return ResponseFactory|Response
     */
    public function store(Requests\Request\StoreRequest $request)
    {
        $data = $request->json()->all();
        $data['requested_by_id'] = Auth::user()->id;
        /** @var Request $model */
        $model = $this->repository->create($data);
        $model->load('requestedItems');
        foreach ($model->requestedItems as $requestedItem) {
            if ($requestedItem->parent_requested_item_id) {
                $this->dispatcher->dispatch(new LocationRequestedItemSelectedEvent($requestedItem));
            }
        }
        return response($model, 201)->header('Location', route('v1.requests.show', ['request' => $model]));
    }

    /**
     * @param Requests\Request\UpdateRequest $request
     * @param Request $requestModel
     * @return BaseModelAbstract
     */
    public function update(Requests\Request\UpdateRequest $request, Request $requestModel)
    {
        $data = $request->json()->all();

        $accept = (bool) $this->getAndUnset($data, 'accept', false);
        if ($accept) {
            $data['completed_by_id'] = Auth::user()->id;
        }

        $completed = (bool) $this->getAndUnset($data, 'completed', false);
        if ($completed) {
            $data['completed_at'] = Carbon::now();
        }

        return $this->repository->update($requestModel, $data);
    }
}