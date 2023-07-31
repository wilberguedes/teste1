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

namespace Modules\Core\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\Tag;
use Modules\Core\QueryBuilder\Parser;

class Tags extends Optionable
{
    /**
     * The type the tags are intended for.
     */
    protected ?string $type = null;

    /**
     * @param  string  $field
     * @param  string|null  $label
     * @param  null|array  $operators
     */
    public function __construct($field, $label = null, $operators = null)
    {
        parent::__construct($field, $label, $operators);

        $this->options(function () {
            return Tag::query()->when($this->type, fn (Builder $query) => $query->withType($this->type))
                ->get()
                ->mapWithKeys(fn (Tag $tag) => [
                    $tag->id => $tag->name,
                ]);
        })->query($this->getQuery(...));
    }

    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'multi-select';
    }

    /**
     * Add the type the tags are intended for.
     */
    public function forType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the query for the filter.
     */
    protected function getQuery($builder, $value, $condition, $sqlOperator, $rule, Parser $parser)
    {
        return $builder->whereHas(
            'tags',
            function ($query) use ($value, $rule, $sqlOperator, $parser, $condition) {
                $query->when(
                    $this->type,
                    fn (Builder $query) => $query->withType($this->type)
                );

                $rule->query->rule = 'id';

                return $parser->convertToQuery($query, $rule, $value, $sqlOperator['operator'], $condition);
            }
        );
    }
}
