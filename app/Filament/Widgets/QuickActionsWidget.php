<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'stats' => [
                'projects_count' => Project::count(),
                'employees_count' => Employee::count(),
                'today_timesheets' => \App\Models\Timesheet::whereDate('check_in_date', Carbon::today())->count(),
            ]
        ];
    }
}
