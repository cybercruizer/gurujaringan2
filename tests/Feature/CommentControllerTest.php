<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_comment_is_saved_pending_approval(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($commenter)->post(
            route('comments.store', $post),
            $this->validCommentInput(['content' => 'A thoughtful comment.']),
        );

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('status', 'Your comment was submitted and is awaiting moderation.');
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'parent_id' => null,
            'author_name' => 'A reader',
            'author_email' => 'reader@example.test',
            'content' => 'A thoughtful comment.',
            'approved_at' => null,
        ]);
    }

    public function test_comment_submission_redirects_to_the_page_url(): void
    {
        $author = User::factory()->create();
        $page = $author->posts()->create([
            'title' => 'About us',
            'type' => 'page',
            'content' => 'About our team.',
            'published_at' => now()->subMinute(),
        ]);

        $this->post(route('comments.store', $page), $this->validCommentInput([
            'content' => 'A comment on the page.',
        ]))
            ->assertRedirect(route('pages.show', $page));

        $this->assertDatabaseHas('comments', [
            'post_id' => $page->id,
            'user_id' => null,
            'author_name' => 'A reader',
            'author_email' => 'reader@example.test',
            'content' => 'A comment on the page.',
        ]);
    }

    public function test_guest_can_submit_a_comment_that_stays_private_until_admin_approval(): void
    {
        $author = User::factory()->create();
        $administrator = User::factory()->create(['is_admin' => true]);
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        $payload = $this->validCommentInput([
            'author_name' => 'Guest Reader',
            'author_email' => 'guest@example.test',
            'content' => 'A guest comment.',
        ]);

        $this->post(route('comments.store', $post), $payload)
            ->assertRedirect(route('posts.show', $post))
            ->assertSessionHas('status', 'Your comment was submitted and is awaiting moderation.');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => null,
            'author_name' => 'Guest Reader',
            'author_email' => 'guest@example.test',
            'content' => 'A guest comment.',
            'approved_at' => null,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('Guest Reader')
            ->assertDontSee('guest@example.test');

        $this->actingAs($administrator)
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('Guest Reader')
            ->assertSee('guest@example.test');

        $this->from(route('posts.show', $post))
            ->post(route('comments.store', $post), $payload)
            ->assertRedirect(route('posts.show', $post))
            ->assertSessionHasErrors([
                'captcha_answer' => 'The CAPTCHA answer is incorrect or has expired.',
            ]);

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_comment_submission_rejects_a_filled_honeypot(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($commenter)
            ->from(route('posts.show', $post))
            ->post(route('comments.store', $post), $this->validCommentInput([
                'content' => 'A spam comment.',
                'website' => 'https://spam.example',
            ]))
            ->assertRedirect(route('posts.show', $post))
            ->assertSessionHasErrors(['website' => 'The website field is prohibited.']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_submission_rejects_a_reply_to_another_posts_comment(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $otherPost = $author->posts()->create([
            'title' => 'Another article',
            'content' => 'Other content.',
            'published_at' => now()->subMinute(),
        ]);
        $otherPostComment = $this->createComment($otherPost, $commenter, [
            'content' => 'A comment on the other article.',
            'approved_at' => now(),
        ]);

        $this->actingAs($commenter)
            ->from(route('posts.show', $post))
            ->post(route('comments.store', $post), $this->validCommentInput([
                'content' => 'A reply on the wrong article.',
                'parent_id' => $otherPostComment->id,
            ]))
            ->assertRedirect(route('posts.show', $post))
            ->assertSessionHasErrors([
                'parent_id' => 'Choose an approved comment on this post to reply to.',
            ]);

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_commenter_can_reply_to_an_approved_top_level_comment(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $parent = $this->createComment($post, $author, [
            'content' => 'An approved comment.',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($commenter)->post(route('comments.store', $post), $this->validCommentInput([
            'content' => 'A useful reply.',
            'parent_id' => $parent->id,
        ]));

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'parent_id' => $parent->id,
            'content' => 'A useful reply.',
            'approved_at' => null,
        ]);
    }

    public function test_comment_submission_rejects_unpublished_posts_and_missing_content(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $draft = $author->posts()->create([
            'title' => 'Private draft',
            'content' => 'Draft content.',
        ]);
        $publishedPost = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($commenter)
            ->post(route('comments.store', $draft), ['content' => 'Comment on a draft.'])
            ->assertForbidden();

        $this->actingAs($commenter)
            ->from(route('posts.show', $publishedPost))
            ->post(route('comments.store', $publishedPost), $this->validCommentInput(['content' => '']))
            ->assertRedirect(route('posts.show', $publishedPost))
            ->assertSessionHasErrors('content');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_submission_rejects_an_incorrect_captcha_answer(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        $this->from(route('posts.show', $post))
            ->post(route('comments.store', $post), $this->validCommentInput([
                'content' => 'A comment with a wrong answer.',
                'captcha_answer' => 9,
            ]))
            ->assertRedirect(route('posts.show', $post))
            ->assertSessionHasErrors([
                'captcha_answer' => 'The CAPTCHA answer is incorrect or has expired.',
            ]);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comments_can_be_disabled_independently_for_posts_and_pages(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Closed article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
            'comments_enabled' => false,
        ]);
        $page = $author->posts()->create([
            'title' => 'Closed page',
            'type' => 'page',
            'content' => 'Page content.',
            'published_at' => now()->subMinute(),
            'comments_enabled' => false,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Comments are closed for this post.')
            ->assertDontSee('name="author_name"', false);
        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('Comments are closed for this page.')
            ->assertDontSee('name="author_name"', false);

        foreach ([$post, $page] as $content) {
            $this->post(route('comments.store', $content), $this->validCommentInput([
                'content' => 'This should not be accepted.',
            ]))->assertForbidden();
        }

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_post_author_can_disable_comments_from_the_editor(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Editable article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($author)
            ->get(route('posts.edit', $post))
            ->assertOk()
            ->assertSee('name="comments_enabled"', false)
            ->assertSee('Allow comments');

        $this->actingAs($author)
            ->put(route('posts.update', $post), [
                'title' => 'Editable article',
                'content' => 'Article content.',
                'comments_enabled' => '0',
            ])
            ->assertRedirect(route('posts.show', $post));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'comments_enabled' => false,
        ]);
    }

    public function test_admin_can_approve_a_comment_and_it_appears_on_the_post(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $administrator = User::factory()->create();
        $administrator->forceFill(['is_admin' => true])->save();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $comment = $this->createComment($post, $commenter, [
            'content' => 'A moderated comment.',
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('A moderated comment.');

        $this->actingAs($administrator)
            ->patch(route('admin.comments.approve', $comment))
            ->assertRedirect();

        $this->assertNotNull($comment->fresh()->approved_at);
        $this->actingAs($commenter)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('A moderated comment.');
    }

    public function test_admin_can_reject_a_comment_and_delete_its_replies(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $administrator = User::factory()->create();
        $administrator->forceFill(['is_admin' => true])->save();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $comment = $this->createComment($post, $commenter, [
            'content' => 'A comment to reject.',
        ]);
        $reply = $this->createComment($post, $author, [
            'parent_id' => $comment->id,
            'content' => 'A reply to reject.',
        ]);

        $this->actingAs($administrator)
            ->delete(route('admin.comments.destroy', $comment))
            ->assertRedirect();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    }

    public function test_only_admins_can_open_comment_moderation_or_approve_comments(): void
    {
        $author = User::factory()->create();
        $regularUser = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $comment = $this->createComment($post, $regularUser, [
            'content' => 'Pending comment.',
        ]);

        $this->get(route('admin.comments.index'))
            ->assertRedirect(route('login'));
        $this->actingAs($regularUser)
            ->get(route('admin.comments.index'))
            ->assertForbidden();
        $this->actingAs($regularUser)
            ->patch(route('admin.comments.approve', $comment))
            ->assertForbidden();
        $this->actingAs($regularUser)
            ->delete(route('admin.comments.destroy', $comment))
            ->assertForbidden();

        $this->assertNull($comment->fresh()->approved_at);
    }

    public function test_comment_list_paginates_threads_and_renders_content_as_text(): void
    {
        $author = User::factory()->create();
        $reader = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        foreach (range(1, 11) as $number) {
            $this->createComment($post, $author, [
                'content' => $number === 1 ? '<script>alert("unsafe")</script>' : "Approved thread {$number}.",
                'approved_at' => now(),
                'created_at' => now()->addSeconds($number),
            ]);
        }

        $pending = $this->createComment($post, $reader, [
            'content' => 'Pending moderation comment.',
        ]);
        $guestComment = $post->comments()->make([
            'author_name' => '<script>alert("unsafe name")</script>',
            'author_email' => 'private@example.test',
            'content' => 'An approved guest comment.',
        ]);
        $guestComment->forceFill([
            'approved_at' => now(),
            'created_at' => now()->addSeconds(12),
        ])->save();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Approved thread 11.')
            ->assertSee('&lt;script&gt;alert(&quot;unsafe name&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("unsafe name")</script>', false)
            ->assertDontSee($guestComment->author_email)
            ->assertSee('An approved guest comment.')
            ->assertDontSee('<script>alert("unsafe")</script>', false)
            ->assertDontSee('Approved thread 1.')
            ->assertDontSee($pending->content, false)
            ->assertSee('name="website"', false)
            ->assertSee('name="author_name"', false)
            ->assertSee('name="author_email"', false)
            ->assertSee('name="captcha_answer"', false)
            ->assertSee('<details class="group flex flex-col gap-3"', false)
            ->assertSee('Reply to')
            ->assertSee('Submit comment');

        $this->get(route('posts.show', ['post' => $post, 'page' => 2]))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_guest_comment_submission_is_rate_limited_to_five_per_minute(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('comments.store', $post), $this->validCommentInput([
                'content' => "Comment attempt {$attempt}.",
            ]))
                ->assertRedirect(route('posts.show', $post));
        }

        $this->post(route('comments.store', $post), $this->validCommentInput([
            'content' => 'The sixth comment attempt.',
        ]))
            ->assertTooManyRequests();

        $this->assertDatabaseCount('comments', 5);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createComment(Post $post, User $user, array $attributes): Comment
    {
        $comment = $post->comments()->make($attributes);
        $comment->user()->associate($user);
        $comment->forceFill($attributes);
        $comment->save();

        return $comment;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validCommentInput(array $attributes = []): array
    {
        $token = Str::random(40);
        $this->withSession([
            'comment_captcha' => [
                $token => [
                    'answer' => 8,
                    'expires_at' => now()->addMinutes(30)->timestamp,
                ],
            ],
        ]);

        return array_merge([
            'author_name' => 'A reader',
            'author_email' => 'reader@example.test',
            'content' => 'A valid comment.',
            'captcha_token' => $token,
            'captcha_answer' => 8,
        ], $attributes);
    }
}
