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

namespace Modules\Billable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Billable\Criteria\ViewAuthorizedProductsCriteria;
use Modules\Billable\Http\Resources\ProductResource;
use Modules\Billable\Models\Product;
use Modules\Core\Criteria\RequestCriteria;
use Modules\Core\Http\Controllers\ApiController;

class ActiveProductController extends ApiController
{
    /**
     * Search for active products
     */
    public function handle(): JsonResponse
    {
        $products = Product::criteria(RequestCriteria::class)
            ->criteria(ViewAuthorizedProductsCriteria::class)
            ->active()
            ->get();

        return $this->response(ProductResource::collection($products));
    }
}
