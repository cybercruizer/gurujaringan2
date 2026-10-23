<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlogMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_views_are_deduplicated_per_session_and_store_referrer_domain_only(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Analytics article',
            'content' => 'Published article content.',
            'published_at' => now()->subMinute(),
        ]);

        $this->withHeader('referer', 'https://www.search.example/results?q=private')
            ->get(route('posts.show', $post))
            ->assertOk();

        $this->withHeader('referer', 'https://another.example/path?token=secret')
            ->get(route('posts.show', $post))
            ->assertOk();

        $this->assertDatabaseCount('post_views', 1);
        $this->assertDatabaseHas('post_views', [
            'post_id' => $post->id,
            'referrer_domain' => 'www.search.example',
        ]);
    }

    public function test_internal_referrers_are_not_recorded_and_drafts_do_not_count_as_views(): void
    {
        $author = User::factory()->create();
        $publishedPost = $author->posts()->create([
            'title' => 'Internal visit',
            'content' => 'Published content.',
            'published_at' => now()->subMinute(),
        ]);
        $draft = $author->posts()->create([
            'title' => 'Draft preview',
            'content' => 'Draft content.',
        ]);

        $this->withHeader('referer', 'http://gurujaringan2.localhost/posts/previous')
            ->get(route('posts.show', $publishedPost))
            ->assertOk();

        $this->actingAs($author)
            ->get(route('posts.show', $draft))
            ->assertOk();

        $this->assertDatabaseCount('post_views', 1);
        $this->assertDatabaseHas('post_views', [
            'post_id' => $publishedPost->id,
            'referrer_domain' => null,
        ]);
    }

    public function test_admin_dashboard_shows_blog_metrics_and_top_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $writer = User::factory()->create();
        $popularPost = $writer->posts()->create([
            'title' => 'Popular article',
            'content' => 'Popular content.',
            'published_at' => now()->subMinute(),
        ]);
        $writer->posts()->create([
            'title' => 'Unpublished draft',
            'content' => 'Draft content.',
        ]);
        $page = $writer->posts()->create([
            'title' => 'Published page',
            'type' => 'page',
            'content' => 'Page content.',
            'published_at' => now()->subMinute(),
        ]);
        $popularPost->views()->create(['referrer_domain' => 'search.example']);
        $popularPost->views()->create(['referrer_domain' => 'search.example']);
        $page->views()->create(['referrer_domain' => null]);

        $pendingComment = $popularPost->comments()->make(['content' => 'Pending comment.']);
        $pendingComment->user()->associate($writer);
        $pendingComment->save();

        $approvedComment = $popularPost->comments()->make(['content' => 'Approved comment.']);
        $approvedComment->user()->associate($writer);
        $approvedComment->approved_at = now();
        $approvedComment->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('3', false)
            ->assertSee('Popular article')
            ->assertSee('search.example')
            ->assertSee('Published articles')
            ->assertSee('Published pages')
            ->assertSee('Approved comments')
            ->assertSee('Awaiting moderation')
            ->assertDontSee('Unpublished draft')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics === [
                'total_views' => 3,
                'published_articles' => 1,
                'published_pages' => 1,
                'pending_comments' => 1,
                'approved_comments' => 1,
            ]);
    }

    public function test_dashboard_filters_metrics_and_chart_by_selected_period(): void
    {
        $this->travelTo('2026-10-22 13:30:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $writer = User::factory()->create();
        $post = $writer->posts()->create([
            'title' => 'Filterable article',
            'content' => 'Article content.',
            'published_at' => now()->subDay(),
        ]);
        $timestamps = [
            '2026-10-22 09:00:00',
            '2026-10-21 12:00:00',
            '2026-10-16 12:00:00',
            '2026-10-15 12:00:00',
            '2026-10-01 12:00:00',
            '2026-09-30 12:00:00',
            '2026-07-01 12:00:00',
            '2025-10-22 12:00:00',
        ];

        foreach ($timestamps as $timestamp) {
            DB::table('post_views')->insert([
                'post_id' => $post->id,
                'referrer_domain' => 'search.example',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        foreach (['2026-10-22 10:00:00', '2026-10-10 10:00:00'] as $timestamp) {
            DB::table('comments')->insert([
                'post_id' => $post->id,
                'user_id' => $writer->id,
                'content' => 'A dated comment.',
                'approved_at' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $this->actingAs($admin);

        foreach ([
            'today' => ['views' => 1, 'pending_comments' => 1, 'buckets' => 14],
            'last_7_days' => ['views' => 3, 'pending_comments' => 1, 'buckets' => 7],
            'this_month' => ['views' => 5, 'pending_comments' => 2, 'buckets' => 22],
            'this_year' => ['views' => 7, 'pending_comments' => 2, 'buckets' => 10],
            'all_time' => ['views' => 8, 'pending_comments' => 2, 'buckets' => 2],
        ] as $period => $expected) {
            $this->get(route('admin.dashboard', ['period' => $period]))
                ->assertOk()
                ->assertViewHas('period', $period)
                ->assertViewHas('metrics', function (array $metrics) use ($expected): bool {
                    $this->assertSame($expected['views'], $metrics['total_views']);
                    $this->assertSame($expected['pending_comments'], $metrics['pending_comments']);

                    return true;
                })
                ->assertViewHas('chart', function (array $chart) use ($period, $expected): bool {
                    $this->assertSame($expected['views'], array_sum($chart['values']));
                    $this->assertCount($expected['buckets'], $chart['labels']);

                    if ($period === 'this_year') {
                        $this->assertSame([
                            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                            'Jul', 'Aug', 'Sep', 'Oct',
                        ], $chart['labels']);
                    }

                    return true;
                })
                ->assertViewHas('topContent', function ($topContent) use ($expected): bool {
                    $this->assertSame($expected['views'], (int) ($topContent->first()->view_count ?? 0));

                    return true;
                })
                ->assertViewHas('topReferrals', function ($topReferrals) use ($expected): bool {
                    $this->assertSame($expected['views'], (int) ($topReferrals->first()->referral_count ?? 0));

                    return true;
                });
        }
    }

    public function test_dashboard_rejects_an_invalid_period_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->followingRedirects()
            ->get(route('admin.dashboard', ['period' => 'last_decade']))
            ->assertOk()
            ->assertSee('The selected period is invalid.');
    }

    public function test_non_admin_cannot_access_the_metrics_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
