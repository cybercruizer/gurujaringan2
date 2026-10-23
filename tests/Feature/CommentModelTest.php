<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CommentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_comments_have_post_user_and_nested_reply_relationships(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $comment = $this->createComment($post, $commenter, [
            'content' => 'A root comment.',
            'approved_at' => now(),
        ]);
        $reply = $this->createComment($post, $author, [
            'parent_id' => $comment->id,
            'content' => 'A nested reply.',
            'approved_at' => now(),
        ]);

        $this->assertTrue($comment->post->is($post));
        $this->assertTrue($comment->user->is($commenter));
        $this->assertTrue($comment->replies->first()->is($reply));
        $this->assertTrue($reply->parent->is($comment));
        $this->assertTrue($author->comments->contains($reply));
        $this->assertTrue($post->comments->contains($comment));
        $this->assertInstanceOf(Carbon::class, $comment->approved_at);
    }

    public function test_approved_scope_excludes_pending_comments(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create([
            'title' => 'Public article',
            'content' => 'Article content.',
            'published_at' => now()->subMinute(),
        ]);
        $approvedComment = $this->createComment($post, $author, [
            'content' => 'Approved comment.',
            'approved_at' => now(),
        ]);
        $this->createComment($post, $author, [
            'content' => 'Pending comment.',
        ]);

        $this->assertSame([$approvedComment->id], Comment::approved()->pluck('id')->all());
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
}
