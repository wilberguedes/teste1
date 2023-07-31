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

namespace Modules\Billable\Services;

use Illuminate\Support\Arr;
use Modules\Billable\Enums\TaxType;
use Modules\Billable\Models\Billable;
use Modules\Billable\Models\BillableProduct;
use Modules\Billable\Models\Product;
use Modules\Core\Facades\ChangeLogger;

class BillableService
{
    /**
     * Save the billable data in storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billableable
     * @return \Modules\Billable\Models\Billable
     */
    public function save(array $data, $billableable)
    {
        $products = $this->setProductsDefaults($data['products'] ?? []);
        $billable = $billableable->billable()->firstOrNew();

        $billable->fill(array_merge([
            'note' => null,
            'terms' => null,
        ], with($this->determineTaxType($data, $billable->exists), function ($taxType) {
            return $taxType !== false ? ['tax_type' => $taxType] : [];
        })))->save();

        foreach ($products as $line) {
            // Remove the id from the line so it won't be passed to the firstOrCreate method
            $id = Arr::pull($line, 'id');

            // Use only the needed attributes
            $line = Arr::only($line, BillableProduct::formAttributes());

            // Update existing product
            if ($id) {
                $this->updateBillableProduct(
                    $line,
                    $id,
                    $billable,
                );

                continue;
            }

            // Handle new products creation
            if (! isset($line['product_id'])) {
                // In case the product exists with a given name use the existing product instead
                $product = Product::withTrashed()
                    ->firstOrCreate(
                        ['name' => $line['name']],
                        array_merge($line, ['is_active' => true])
                    );

                if ($product->trashed()) {
                    $product->restore();
                }

                $billable->products()->create(
                    array_merge($line, [
                        'product_id' => $product->getKey(),
                        'unit_price' => $line['unit_price'] ?: 0,
                    ])
                );

                continue;
            }

            // Regularly product selected from dropdown
            $billable->products()->create($line);
        }

        if (count($data['removed_products'] ?? []) > 0) {
            $this->removeProducts($data['removed_products']);
        }

        return $this->updateTotalBillableableColumn($billable, $billableable);
    }

    protected function updateBillableProduct($line, $id, $billable)
    {
        // When user enter new product on existing product selected
        $product = Product::withTrashed()
            ->firstOrCreate(
                ['name' => $line['name']],
                array_merge($line, ['is_active' => true])
            );

        if ($product->trashed()) {
            $product->restore();
        }

        $billable->products()
            ->find($id)
            ->fill(
                array_merge($line, ['product_id' => $product->getKey()]) // In case new product created, update the product_id attribute
            )->save();
    }

    /**
     * Determine the billable tax type
     *
     * @param  array  $data
     * @param  bool  $exists
     * @return false|\Modules\Billable\Enums\TaxType
     */
    protected function determineTaxType($data, $exists)
    {
        $taxType = false;

        if ($exists && isset($data['tax_type']) && ! empty($data['tax_type'])) {
            $taxType = $data['tax_type'];
        } elseif (! $exists) {
            $taxType = empty($data['tax_type'] ?? null) ? Billable::defaultTaxType() : $data['tax_type'];
        }

        if (is_string($taxType)) {
            $taxType = TaxType::find($taxType);
        }

        return $taxType;
    }

    /**
     * Set the products defaults
     */
    protected function setProductsDefaults(array $products): array
    {
        foreach ($products as $index => $line) {
            $products[$index] = array_merge($line, [
                'display_order' => $line['display_order'] ?? $index + 1,
                'discount_type' => $line['discount_type'] ?? BillableProduct::defaultDiscountType(),
                'tax_label' => $line['tax_label'] ?? BillableProduct::defaultTaxLabel(),
                'tax_rate' => $line['tax_rate'] ?? BillableProduct::defaultTaxRate(),
            ]);

            // When the product name is not set and the product_id exists
            // we will use the name from the actual product_id, useful when creating products via Zapier
            if (isset($line['product_id']) && ! isset($line['name'])) {
                $products[$index]['name'] = Product::find($line['product_id'])->name;
            }
        }

        return $products;
    }

    /**
     * Remove the given products id's from the products billable.
     */
    public function removeProducts(array $products): void
    {
        BillableProduct::whereIn('id', $products)->get()->each->delete();
    }

    /**
     * Update the billable billableable total column (if using)
     *
     * @param  \Modules\Billable\Models\Billable  $billable
     * @param  \Modules\Core\Models\Model  $billableable
     * @return \Modules\Billable\Models\Billable
     */
    protected function updateTotalBillableableColumn($billable, $billableable)
    {
        $totalColumn = $billableable->totalColumn();

        if (! $totalColumn) {
            return $billable;
        }

        if ((int) $billableable->{$totalColumn} == 0 && $billable->rawTotal() == 0) {
            return $billable;
        }

        $billableable->{$totalColumn} = $billable->rawTotal();

        if (! $billableable->wasRecentlyCreated) {
            $billableable->save();
        } else {
            ChangeLogger::disabled(function () use ($billableable) {
                $billableable->save();
            });
        }

        return $billable;
    }
}
