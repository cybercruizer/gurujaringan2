<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private const array PERIODS = [
        'today' => 'Today',
        'last_7_days' => 'Last 7 days',
        'this_month' => 'This month',
        'this_year' => 'This year',
        'all_time' => 'All time',
    ];

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'required', 'string', Rule::in(array_keys(self::PERIODS))],
        ]);
        $period = $validated['period'] ?? 'all_time';
        $now = CarbonImmutable::now();
        $earliestView = $period === 'all_time'
            ? PostView::query()->min('created_at')
            : null;
        $rangeStart = $this->periodStart($period, $now, $earliestView);
        $rangeEnd = $now;
        $viewQuery = PostView::query()->whereBetween('created_at', [$rangeStart, $rangeEnd]);

        $metrics = [
            'total_views' => (clone $viewQuery)->count(),
            'published_articles' => Post::published()->where('type', 'article')->count(),
            'published_pages' => Post::published()->where('type', 'page')->count(),
            'pending_comments' => Comment::query()
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->whereNull('approved_at')
                ->count(),
            'approved_comments' => Comment::query()
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->whereNotNull('approved_at')
                ->count(),
        ];

        $bucketFormat = $this->bucketFormat($period);
        $bucketExpression = $this->bucketExpression($bucketFormat);
        $groupedViews = (clone $viewQuery)
            ->selectRaw($bucketExpression.' as bucket, COUNT(*) as view_count')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('view_count', 'bucket');
        $chart = $this->buildChart($period, $rangeStart, $now, $groupedViews);

        $topContent = (clone $viewQuery)
            ->select('post_id')
            ->selectRaw('COUNT(*) as view_count')
            ->whereHas('post', fn ($query) => $query->published())
            ->with('post:id,title,slug,type')
            ->groupBy('post_id')
            ->orderByDesc('view_count')
            ->orderBy('post_id')
            ->limit(5)
            ->get();

        $topReferrals = (clone $viewQuery)
            ->select('referrer_domain')
            ->selectRaw('COUNT(*) as referral_count')
            ->whereNotNull('referrer_domain')
            ->groupBy('referrer_domain')
            ->orderByDesc('referral_count')
            ->orderBy('referrer_domain')
            ->limit(5)
            ->get();

        return view('admin.index', [
            'metrics' => $metrics,
            'topContent' => $topContent,
            'topReferrals' => $topReferrals,
            'chart' => $chart,
            'period' => $period,
            'periods' => self::PERIODS,
        ]);
    }

    private function periodStart(string $period, CarbonImmutable $now, ?string $earliestView): CarbonImmutable
    {
        return match ($period) {
            'today' => $now->startOfDay(),
            'last_7_days' => $now->subDays(6)->startOfDay(),
            'this_month' => $now->startOfMonth(),
            'this_year' => $now->startOfYear(),
            'all_time' => $earliestView === null
                ? $now->startOfYear()
                : CarbonImmutable::parse($earliestView)->startOfYear(),
        };
    }

    private function bucketFormat(string $period): string
    {
        return match ($period) {
            'today' => '%Y-%m-%d %H',
            'last_7_days', 'this_month' => '%Y-%m-%d',
            'this_year' => '%Y-%m',
            'all_time' => '%Y',
        };
    }

    private function bucketExpression(string $format): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('{$format}', created_at)",
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '{$format}')",
            'pgsql' => "TO_CHAR(created_at, '{$this->postgresFormat($format)}')",
            default => throw new \RuntimeException('Dashboard metrics do not support the configured database driver.'),
        };
    }

    private function postgresFormat(string $format): string
    {
        return str_replace(['%Y', '%m', '%d', '%H'], ['YYYY', 'MM', 'DD', 'HH24'], $format);
    }

    /**
     * @param  Collection<string, int|string>  $groupedViews
     * @return array{labels: list<string>, values: list<int>, max: int}
     */
    private function buildChart(
        string $period,
        CarbonImmutable $rangeStart,
        CarbonImmutable $now,
        Collection $groupedViews,
    ): array {
        $cursor = match ($period) {
            'today' => $rangeStart->startOfHour(),
            'last_7_days', 'this_month' => $rangeStart->startOfDay(),
            'this_year' => $rangeStart->startOfMonth(),
            'all_time' => $rangeStart->startOfYear(),
        };
        $end = match ($period) {
            'today' => $now->startOfHour(),
            'last_7_days', 'this_month' => $now->startOfDay(),
            'this_year' => $now->startOfMonth(),
            'all_time' => $now->startOfYear(),
        };
        $interval = match ($period) {
            'today' => 'hour',
            'last_7_days', 'this_month' => 'day',
            'this_year' => 'month',
            'all_time' => 'year',
        };

        $labels = [];
        $values = [];

        while ($cursor <= $end) {
            $key = match ($period) {
                'today' => $cursor->format('Y-m-d H'),
                'last_7_days', 'this_month' => $cursor->format('Y-m-d'),
                'this_year' => $cursor->format('Y-m'),
                'all_time' => $cursor->format('Y'),
            };
            $labels[] = match ($interval) {
                'hour' => $cursor->format('g A'),
                'day' => $cursor->format('M j'),
                'month' => $cursor->format('M'),
                'year' => $cursor->format('Y'),
            };
            $values[] = (int) ($groupedViews[$key] ?? 0);
            $cursor = match ($interval) {
                'hour' => $cursor->addHour(),
                'day' => $cursor->addDay(),
                'month' => $cursor->addMonth(),
                'year' => $cursor->addYear(),
            };
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'max' => max($values ?: [0]),
        ];
    }
}
