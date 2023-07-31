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

namespace Modules\Notes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Comments\Concerns\HasComments;
use Modules\Core\Media\HasAttributesWithPendingMedia;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;
use Modules\Core\Timeline\Timelineable;
use Modules\Notes\Database\Factories\NoteFactory;

class Note extends Model
{
    use HasComments,
        Resourceable,
        HasAttributesWithPendingMedia,
        HasFactory,
        Timelineable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'body',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'int',
    ];

    /**
     * The columns for the model that are searchable.
     */
    protected static array $searchableColumns = [
        'body' => 'like',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = $model->user_id ?: auth()->id();
        });

        static::deleting(function ($model) {
            $model->purge();
        });
    }

    /**
     * Get all of the contacts that are assigned this note.
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\Models\Contact::class, 'noteable');
    }

    /**
     * Get all of the companies that are assigned this note.
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\Models\Company::class, 'noteable');
    }

    /**
     * Get all of the deals that are assigned this note.
     */
    public function deals(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Deals\Models\Deal::class, 'noteable');
    }

    /**
     * Get the note owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\Models\User::class);
    }

    /**
     * Get the attributes that may contain pending media
     */
    public function attributesWithPendingMedia(): string
    {
        return 'body';
    }

    /**
     * Get the timeline component for front-end
     */
    public function getTimelineComponent(): string
    {
        return 'record-tab-timeline-note';
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->withCount(['comments'])->with([
            'companies.nextActivity',
            'contacts.nextActivity',
            'deals.nextActivity',
            'user',
        ]);
    }

    /**
     * Purge the note data.
     */
    public function purge(): void
    {
        foreach (['contacts', 'companies', 'deals'] as $relation) {
            $this->{$relation}()->withTrashed()->detach();
        }
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }
}
