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

namespace Modules\Core\Changelog;

use Carbon\CarbonInterval;
use DateInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\Facades\ChangeLogger;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\ActivityLogStatus;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;
use Spatie\Activitylog\LogOptions;

/**
 * There is code here from spatie \Spatie\Activitylog\Traits\LogsActivity trait
 * because it was causing conflicts with the activities relationship, we are using "changelog" relationship not "activities"
 * All the code is the same, except that the abstract method "getActivitylogOptions" is not included here
 * as well the "activities" relationship method definition, see the "From spatie LogsActivity trait" comments
 */
trait LogsModelChanges
{
    use LogsPivotEvents;

    // From spatie LogsActivity trait
    public static array $changesPipes = [];

    protected array $oldAttributes = [];

    protected ?LogOptions $activitylogOptions;

    public bool $enableLoggingModelsEvents = true;

    /**
     * Boot LogsModelChanges trait
     */
    protected static function bootLogsModelChanges(): void
    {
        static::addLogChange(new CustomFieldsChangesLogger(static::class));

        static::deleted(function ($model) {
            if (! $model->usesSoftDeletes() || $model->isForceDeleting()) {
                $model->changelog()->delete();
            }
        });

        // From spatie LogsActivity trait
        // Hook into eloquent events that only specified in $eventToBeRecorded array,
        // checking for "updated" event hook explicitly to temporary hold original
        // attributes on the model as we'll need them later to compare against.

        static::eventsToBeRecorded()->each(function ($eventName) {
            if ($eventName === 'updated') {
                static::updating(function (Model $model) {
                    $oldValues = (new static())->setRawAttributes($model->getRawOriginal());
                    $model->oldAttributes = static::logChanges($oldValues);
                });
            }

            static::$eventName(function (Model $model) use ($eventName) {
                $model->activitylogOptions = $model->getActivitylogOptions();

                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                $changes = $model->attributeValuesToBeLogged($eventName);

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogNameToUse();

                // Submitting empty description will cause place holder replacer to fail.
                if ($description == '') {
                    return;
                }

                if ($model->isLogEmpty($changes) && ! $model->activitylogOptions->submitEmptyLogs) {
                    return;
                }

                // User can define a custom pipelines to mutate, add or remove from changes
                // each pipe receives the event carrier bag with changes and the model in
                // question every pipe should manipulate new and old attributes.
                $event = app(Pipeline::class)
                    ->send(new EventLogBag($eventName, $model, $changes, $model->activitylogOptions))
                    ->through(static::$changesPipes)
                    ->thenReturn();

                // Actual logging
                $logger = app(ActivityLogger::class)
                    ->useLog($logName)
                    ->event($eventName)
                    ->performedOn($model)
                    ->withProperties($event->changes);

                if (method_exists($model, 'tapActivity')) {
                    $logger->tap([$model, 'tapActivity'], $eventName);
                }

                $logger->log($description);

                // Reset log options so the model can be serialized.
                $model->activitylogOptions = null;
            });
        });
    }

    /**
     * Model has many changelogs
     */
    public function changelog(): MorphMany
    {
        return $this->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'subject');
    }

    /**
     * Add identifier for the log, used to create components for front-end
     *
     *
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        // https://github.com/spatie/laravel-activitylog/issues/1034
        // Why in the world is called twice?
        $activity->identifier ??= $eventName;
        $activity->log_name = ChangeLogger::MODEL_LOG_NAME;
    }

    /**
     * Get the changelog options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->changelogAttributes())
            ->logExcept($this->changelogAttributeToIgnore())
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly($this->usesTimestamps() ? [$this->getUpdatedAtColumn()] : [])
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the changelog attributes
     */
    protected function changelogAttributes(): array
    {
        if (property_exists(static::class, 'changelogAttributes')) {
            return static::$changelogAttributes;
        }

        return [];
    }

    /**
     * Get the changelog attributes to ignore
     */
    protected function changelogAttributeToIgnore(): array
    {
        if (property_exists(static::class, 'changelogAttributeToIgnore')) {
            return static::$changelogAttributeToIgnore;
        }

        return [];
    }

    /**
     * Log dirty attributes on the latest model log
     *
     * @param  array  $attributes
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public static function logDirtyAttributesOnLatestLog($attributes, $model)
    {
        // We will get the latest created log for the current request, for example
        // if user updated other model fields, there will be log for changes and we will
        // use this log properties to merge the actual custom field changes in the same log
        $latestLog = ChangeLogger::getLatestCreatedLog();

        if (! $latestLog) {
            // In this case, only multi optionable field updated,
            // we will create brand new log for the model
            ChangeLogger::onModel($model, $attributes)
                ->identifier('updated')
                ->log();

            return;
        }

        if ($latestLog->subject->is($model)) {
            $latestLog->properties = collect(array_merge_recursive(
                $latestLog->properties->toArray(),
                $attributes
            ));

            $latestLog->save();
        }
    }

    // From spatie LogsActivity trait

    public static function addLogChange(LoggablePipe $pipe): void
    {
        static::$changesPipes[] = $pipe;
    }

    public function isLogEmpty(array $changes): bool
    {
        return empty($changes['attributes'] ?? []) && empty($changes['old'] ?? []);
    }

    public function disableLogging(): self
    {
        $this->enableLoggingModelsEvents = false;

        return $this;
    }

    public function enableLogging(): self
    {
        $this->enableLoggingModelsEvents = true;

        return $this;
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if (! empty($this->activitylogOptions->descriptionForEvent)) {
            return ($this->activitylogOptions->descriptionForEvent)($eventName);
        }

        return $eventName;
    }

    public function getLogNameToUse(): ?string
    {
        if (! empty($this->activitylogOptions->logName)) {
            return $this->activitylogOptions->logName;
        }

        return config('activitylog.default_log_name');
    }

    /**
     * Get the event names that should be recorded.
     **/
    protected static function eventsToBeRecorded(): Collection
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        $logStatus = app(ActivityLogStatus::class);

        if (! $this->enableLoggingModelsEvents || $logStatus->disabled()) {
            return false;
        }

        if (! in_array($eventName, ['created', 'updated'])) {
            return true;
        }

        // Do not log update event if the model is restoring
        if ($this->isRestoring()) {
            return false;
        }

        // Do not log update event if only ignored attributes are changed.
        return (bool) count(Arr::except($this->getDirty(), $this->activitylogOptions->dontLogIfAttributesChangedOnly));
    }

    /**
     * Determines if the model is restoring.
     **/
    protected function isRestoring(): bool
    {
        $deletedAtColumn = method_exists($this, 'getDeletedAtColumn')
            ? $this->getDeletedAtColumn()
            : 'deleted_at';

        return $this->isDirty($deletedAtColumn) && count($this->getDirty()) === 1;
    }

    /**
     * Determines what attributes needs to be logged based on the configuration.
     **/
    public function attributesToBeLogged(): array
    {
        $this->activitylogOptions = $this->getActivitylogOptions();

        $attributes = [];

        // Check if fillable attributes will be logged then merge it to the local attributes array.
        if ($this->activitylogOptions->logFillable) {
            $attributes = array_merge($attributes, $this->getFillable());
        }

        // Determine if unguarded attributes will be logged.
        if ($this->shouldLogUnguarded()) {
            // Get only attribute names, not intrested in the values here then guarded
            // attributes. get only keys than not present in guarded array, because
            // we are logging the unguarded attributes and we cant have both!

            $attributes = array_merge($attributes, array_diff(array_keys($this->getAttributes()), $this->getGuarded()));
        }

        if (! empty($this->activitylogOptions->logAttributes)) {
            // Filter * from the logAttributes because will deal with it separately
            $attributes = array_merge($attributes, array_diff($this->activitylogOptions->logAttributes, ['*']));

            // If there's * get all attributes then merge it, dont respect $guarded or $fillable.
            if (in_array('*', $this->activitylogOptions->logAttributes)) {
                $attributes = array_merge($attributes, array_keys($this->getAttributes()));
            }
        }

        if ($this->activitylogOptions->logExceptAttributes) {
            // Filter out the attributes defined in ignoredAttributes out of the local array
            $attributes = array_diff($attributes, $this->activitylogOptions->logExceptAttributes);
        }

        return $attributes;
    }

    public function shouldLogUnguarded(): bool
    {
        if (! $this->activitylogOptions->logUnguarded) {
            return false;
        }

        // This case means all of the attributes are guarded
        // so we'll not have any unguarded anyway.
        return ! (in_array('*', $this->getGuarded()));
    }

    /**
     * Determines values that will be logged based on the difference.
     **/
    public function attributeValuesToBeLogged(string $processingEvent): array
    {
        // no loggable attributes, no values to be logged!
        if (! count($this->attributesToBeLogged())) {
            return [];
        }

        $properties['attributes'] = static::logChanges(

            // if the current event is retrieved, get the model itself
            // else get the fresh default properties from database
            // as wouldn't be part of the saved model instance.
            $processingEvent == 'retrieved'
                ? $this
                : (
                    $this->exists
                        ? $this->fresh() ?? $this
                        : $this
                )
        );

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            // Fill the attributes with null values.
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            // Populate the old key with keys from database and from old attributes.
            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);

            // Fail safe.
            $this->oldAttributes = [];
        }

        if ($this->activitylogOptions->logOnlyDirty && isset($properties['old'])) {
            // Get difference between the old and new attributes.
            $properties['attributes'] = array_udiff_assoc(
                $properties['attributes'],
                $properties['old'],
                function ($new, $old) {
                    // Strict check for php's weird behaviors
                    if ($old === null || $new === null) {
                        return $new === $old ? 0 : 1;
                    }

                    // Handles Date interval comparisons since php cannot use spaceship
                    // Operator to compare them and will throw ErrorException.
                    if ($old instanceof DateInterval) {
                        return CarbonInterval::make($old)->equalTo($new) ? 0 : 1;
                    } elseif ($new instanceof DateInterval) {
                        return CarbonInterval::make($new)->equalTo($old) ? 0 : 1;
                    }

                    return $new <=> $old;
                }
            );

            $properties['old'] = collect($properties['old'])
                ->only(array_keys($properties['attributes']))
                ->all();
        }

        if (static::eventsToBeRecorded()->contains('deleted') && $processingEvent == 'deleted') {
            $properties['old'] = $properties['attributes'];
            unset($properties['attributes']);
        }

        return $properties;
    }

    public static function logChanges(Model $model): array
    {
        $changes = [];
        $attributes = $model->attributesToBeLogged();

        foreach ($attributes as $attribute) {
            if (Str::contains($attribute, '.')) {
                $changes += self::getRelatedModelAttributeValue($model, $attribute);

                continue;
            }

            if (Str::contains($attribute, '->')) {
                Arr::set(
                    $changes,
                    str_replace('->', '.', $attribute),
                    static::getModelAttributeJsonValue($model, $attribute)
                );

                continue;
            }

            $changes[$attribute] = in_array($attribute, $model->activitylogOptions->attributeRawValues)
                ? $model->getAttributeFromArray($attribute)
                : $model->getAttribute($attribute);

            if (is_null($changes[$attribute])) {
                continue;
            }

            if ($model->isDateAttribute($attribute)) {
                $changes[$attribute] = $model->serializeDate(
                    $model->asDateTime($changes[$attribute])
                );
            }

            if ($model->hasCast($attribute)) {
                $cast = $model->getCasts()[$attribute];

                if (function_exists('enum_exists') && enum_exists($cast)) {
                    if (method_exists($model, 'getStorableEnumValue')) {
                        $changes[$attribute] = $model->getStorableEnumValue($changes[$attribute]);
                    } else {
                        // ToDo: DEPRECATED - only here for Laravel 8 support
                        $changes[$attribute] = $changes[$attribute] instanceof \BackedEnum
                            ? $changes[$attribute]->value
                            : $changes[$attribute]->name;
                    }
                }

                if ($model->isCustomDateTimeCast($cast) || $model->isImmutableCustomDateTimeCast($cast)) {
                    $changes[$attribute] = $model->asDateTime($changes[$attribute])->format(explode(':', $cast, 2)[1]);
                }
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        $relatedModelNames = explode('.', $attribute);
        $relatedAttribute = array_pop($relatedModelNames);

        $attributeName = [];
        $relatedModel = $model;

        do {
            $attributeName[] = $relatedModelName = static::getRelatedModelRelationName($relatedModel, array_shift($relatedModelNames));

            $relatedModel = $relatedModel->{$relatedModelName} ?? $relatedModel->{$relatedModelName}();
        } while (! empty($relatedModelNames));

        $attributeName[] = $relatedAttribute;

        return [implode('.', $attributeName) => $relatedModel->{$relatedAttribute} ?? null];
    }

    protected static function getRelatedModelRelationName(Model $model, string $relation): string
    {
        return Arr::first([
            $relation,
            Str::snake($relation),
            Str::camel($relation),
        ], function (string $method) use ($model): bool {
            return method_exists($model, $method);
        }, $relation);
    }

    protected static function getModelAttributeJsonValue(Model $model, string $attribute): mixed
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);
        $modelAttribute = collect($model->getAttribute($modelAttribute));

        return data_get($modelAttribute, implode('.', $path));
    }
}
