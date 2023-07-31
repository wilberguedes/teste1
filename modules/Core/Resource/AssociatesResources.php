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

namespace Modules\Core\Resource;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Facades\Innoclapps;

trait AssociatesResources
{
    /**
     * Attach the given associations to the given resource
     *
     * @param  string|\Modules\Core\Resource\Resource  $resource
     * @param  int  $primaryRecordId
     * @param  array  $associations
     * @return void
     */
    protected function attachAssociations($resource, $primaryRecordId, $associations)
    {
        $this->saveAssociations($resource, $primaryRecordId, $associations, 'attach');
    }

    /**
     * Sync the given associations to the given resource
     *
     * @param  string|\Modules\Core\Resource\Resource  $resource
     * @param  int  $primaryRecordId
     * @param  array  $associations
     * @return void
     */
    protected function syncAssociations($resource, $primaryRecordId, $associations)
    {
        $this->saveAssociations($resource, $primaryRecordId, $associations, 'sync');
    }

    /**
     * Sync the given associations to the given resource
     *
     * @param  string|\Modules\Core\Resource\Resource  $resource
     * @param  int  $primaryRecordId
     * @param  array  $associations
     * @param  string  $method
     * @return void
     */
    protected function saveAssociations($resource, $primaryRecordId, $associations, $method)
    {
        $forResource = is_string($resource) ? Innoclapps::resourceByName($resource) : $resource;

        foreach ($associations as $resourceName => $ids) {
            if (! is_array($ids)) {
                continue;
            }

            // [ 'associations' => [ 'contacts' => [1,2] ]]
            if ($resourceName === 'associations') {
                $this->saveAssociations($forResource, $primaryRecordId, $associations, $method);

                continue;
            }

            $forResource->newModel()
                ->find($primaryRecordId)
                ->{Innoclapps::resourceByName($resourceName)->associateableName()}()
                ->{$method}($ids);
        }
    }

    /**
     * Filter the given associations for saving for the given resource
     *
     * @param  string|\Modules\Core\Resource\Resource  $resource $resource The resource the associations will be attached
     * @param  array  $associations
     * @return array
     */
    protected function filterAssociations($resource, $associations)
    {
        $forResource = is_string($resource) ? Innoclapps::resourceByName($resource) : $resource;

        // When the special 'associations' key exists in the associations array, we will merge
        // the 'associations' key with the rest of the associations provided and then continue
        // with all the filtering
        // for example:

        /*

        $associations = ['associations'=>['companies'=>[1,2]]] becomes ['companies'=>[1,2]]

        or

        $associations = [
            'contacts'=>[2,3],
            'companies'=>[2,4],
            'associations'=>['companies'=>[1,2], 'contacts'=>[5]]
        ]

        becomes

        $associations = [
            'contacts'=>[2,3,5],
            'companies'=>[1,2,4,2], (array_unique is performed when quering)
        ]
        */

        return collect($associations)->when(array_key_exists('associations', $associations), function ($collection) {
            return $collection->mergeRecursive($collection['associations'])->forget('associations');
        })->mapWithKeys(function ($values, $resourceName) {
            return [$resourceName => ['values' => $values, 'resource' => Innoclapps::resourceByName($resourceName)]];
        })->each(function ($data, $resourceName) use ($forResource) {
            if (! $data['resource'] || ! $data['resource']->canBeAssociated($forResource->name())) {
                abort(
                    400,
                    "The provided resource name \"$resourceName\" cannot be associated to the {$forResource->singularLabel()}"
                );
            }
        })->mapWithKeys(function ($data, $resourceName) {
            return [$resourceName => $data['resource']->newQuery()->findMany(
                array_unique($data['values'] ?? [])
            )];
        })->map(function ($models) {
            return $models->reject(fn ($model) => Gate::denies('view', $model))->modelKeys();
        })->all();
    }
}
