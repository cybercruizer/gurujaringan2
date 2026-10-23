<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        return ($post->published_at !== null && $post->published_at->lessThanOrEqualTo(now()))
            || $user?->is($post->user) === true;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->is($post->user);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->is($post->user);
    }
}
