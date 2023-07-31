<?php

namespace Tests\Fixtures;

use Modules\Core\Filters\Text;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Table\BooleanColumn;
use Modules\Core\Table\Column;
use Modules\Core\Table\DateTimeColumn;
use Modules\Core\Table\Table;

class EventTable extends Table
{
    public function __construct($query = null, $request = null)
    {
        parent::__construct(
            $query ?: (new Event)->newQuery(),
            $request ?: app(ResourceRequest::class)
        );
    }

    public function columns(): array
    {
        return [
            Column::make('title', 'Title'),
            DateTimeColumn::make('start', 'Start Date'),
            DateTimeColumn::make('emd', 'End Date'),
            BooleanColumn::make('is_all_day', 'All Day'),
            Column::make('total_guests', 'Total Guests'),
        ];
    }

    public function filters(ResourceRequest $request): array
    {
        return [
            Text::make('title', 'Title'),
            Text::make('description', 'Description')->canSee(function () {
                return false;
            }),
        ];
    }
}
