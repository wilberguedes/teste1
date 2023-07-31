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

namespace Modules\Billable\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Billable\Models\Product;
use Modules\Users\Models\User;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->can('view all products')) {
            return true;
        }

        if ((int) $product->created_by === (int) $user->id) {
            return true;
        }

        if ($user->can('view team products')) {
            return $user->managesAnyTeamsOf($product->created_by);
        }

        return false;
    }

    /**
     * Determine if the given user can create products.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->can('edit all products')) {
            return true;
        }

        if ($user->can('edit own products')) {
            return (int) $user->id === (int) $product->created_by;
        }

        if ($user->can('edit team products')) {
            return $user->managesAnyTeamsOf($product->created_by);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        if ($user->can('delete any product')) {
            return true;
        }

        if ($user->can('delete own products')) {
            return (int) $user->id === (int) $product->created_by;
        }

        if ($user->can('delete team products')) {
            return $user->managesAnyTeamsOf($product->created_by);
        }

        return false;
    }

    /**
     * Determine whether the user can export products.
     */
    public function export(User $user): bool
    {
        return $user->can('export products');
    }
}
