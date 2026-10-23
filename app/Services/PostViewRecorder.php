<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;

class PostViewRecorder
{
    public function record(Post $post, Request $request): void
    {
        $viewedPostIds = $request->session()->get('viewed_post_ids', []);

        if (in_array($post->getKey(), $viewedPostIds, true)) {
            return;
        }

        $post->views()->create([
            'referrer_domain' => $this->externalReferrerDomain($request),
        ]);

        $request->session()->put(
            'viewed_post_ids',
            [...$viewedPostIds, $post->getKey()],
        );
    }

    private function externalReferrerDomain(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');
        $scheme = $referrer === null ? null : parse_url($referrer, PHP_URL_SCHEME);
        $host = $referrer === null ? null : parse_url($referrer, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host)) {
            return null;
        }

        $host = mb_strtolower(rtrim($host, '.'));

        if ($host === mb_strtolower($request->getHost())) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        return $host;
    }
}
