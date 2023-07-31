<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

namespace Modules\Core\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Core\Resource\Http\CreateResourceRequest;
use Modules\Core\Resource\Http\ResourcefulRequest;
use Modules\Core\Resource\Http\UpdateResourceRequest;

class ResourcefulController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(ResourcefulRequest $request): JsonResponse
    {
        // Resourceful index flag
        $this->authorize('viewAny', $request->resource()::$model);

        return $this->response(
            $request->toResponse(
                $request->resource()->resourcefulHandler($request)->index()
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateResourceRequest $request): JsonResponse
    {
        // Resourceful store flag
        $this->authorize('create', $request->resource()::$model);

        $record = $request->resource()->displayQuery()->find(
            $request->resource()->resourcefulHandler($request)->store()->getKey()
        );

        // Set that this record was recently created as the property value is lost
        // because we are re-querying the record again after creation
        $record->wasRecentlyCreated = true;

        return $this->response(
            $request->toResponse($record),
            201
        );
    }

    /**
     * Display resource record.
     */
    public function show(ResourcefulRequest $request): JsonResponse
    {
        // Resourceful show flag
        $this->authorize('view', $request->record());

        $record = $request->resource()
            ->resourcefulHandler($request)
            ->show($request->resourceId());

        $record->loadMissing($request->getWith());

        return $this->response(
            $request->toResponse($record)
        );
    }

    /**
     * Update resource record in storage.
     */
    public function update(UpdateResourceRequest $request): JsonResponse
    {
        // Resourceful update flag
        $this->authorize('update', $request->record());

        $request->resource()->resourcefulHandler($request)->update($request->record());

        $record = $request->resource()->displayQuery()
            ->with($request->getWith())
            ->find($request->resourceId());

        return $this->response(
            $request->toResponse($record)
        );
    }

    /**
     * Remove resource record from storage.
     */
    public function destroy(ResourcefulRequest $request): JsonResponse
    {
        // Resourceful destroy flag
        $this->authorize('delete', $request->record());

        $content = DB::transaction(function () use ($request) {
            return $request->resource()->resourcefulHandler($request)->delete($request->record());
        });

        return $this->response($content, empty($content) ? 204 : 200);
    }
}
