<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Services\Blog\BlogRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Accepts a reader comment on a blog post. Every comment — including one from
 * a signed-in user — is stored 'pending' and appears only after a platform
 * admin approves it (/admin/blog/comments).
 *
 * Three bot defences, none of which needs third-party JS:
 *   - honeypot: a visually hidden "website" field humans leave empty;
 *   - time-trap: the form carries an encrypted render timestamp; a submit
 *     faster than blog.comments.min_seconds, older than a day, or with a
 *     forged token is dropped;
 *   - throttle: per keyed IP hash, blog.comments.per_minute and per_day.
 * A trapped bot sees the same "thanks, awaiting moderation" message a human
 * does, so it learns nothing; nothing is stored.
 */
class BlogCommentController extends Controller
{
    public function store(Request $request, string $slug, BlogRepository $posts): RedirectResponse
    {
        $locale = in_array($request->input('locale'), BlogRepository::LOCALES, true) ? $request->input('locale') : app()->getLocale();
        $post = $posts->find($locale, $slug);
        if ($post === null) {
            abort(404);
        }

        $back = BlogController::postUrl($post) . '#comments';
        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

        // Honeypot + time-trap: silent success, nothing stored.
        if (filled($request->input('website')) || ! $this->timeTrapPasses((string) $request->input('ts'))) {
            return redirect()->to($back)->with('blog_comment_status', 'pending');
        }

        $minuteKey = 'blog-comment:min:' . $ipHash;
        $dayKey = 'blog-comment:day:' . $ipHash;
        if (RateLimiter::tooManyAttempts($minuteKey, (int) config('blog.comments.per_minute', 3))
            || RateLimiter::tooManyAttempts($dayKey, (int) config('blog.comments.per_day', 20))) {
            return redirect()->to($back)->withErrors(['body' => __('blog.comments.throttled')])->withInput($request->except('ts'));
        }

        $max = (int) config('blog.comments.max_length', 2000);
        $data = $request->validateWithBag('default', [
            'author_name'  => ['required', 'string', 'min:2', 'max:80'],
            'author_email' => ['required', 'string', 'email', 'max:190'],
            'body'         => ['required', 'string', 'min:2', "max:{$max}"],
            'consent'      => ['accepted'],
            'parent_id'    => ['nullable', 'uuid'],
        ], [], [
            'author_name'  => __('blog.comments.name'),
            'author_email' => __('blog.comments.email'),
            'body'         => __('blog.comments.comment'),
            'consent'      => __('blog.comments.consent_short'),
        ]);

        $parentId = null;
        if (! empty($data['parent_id'])) {
            // One level of replies, and only to an approved top-level comment on this post.
            $parent = BlogComment::query()
                ->whereKey($data['parent_id'])
                ->where('post_slug', $post['slug'])->where('locale', $post['locale'])
                ->whereNull('parent_id')->approved()->first();
            $parentId = $parent?->id;
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($dayKey, 86400);

        BlogComment::create([
            'post_slug'       => $post['slug'],
            'locale'          => $post['locale'],
            'parent_id'       => $parentId,
            'author_name'     => trim(strip_tags($data['author_name'])),
            'author_email'    => mb_strtolower(trim($data['author_email'])),
            'body'            => trim($data['body']),
            'status'          => 'pending',
            'ip_hash'         => $ipHash,
            'user_agent_hash' => hash_hmac('sha256', (string) $request->userAgent(), (string) config('app.key')),
            'user_id'         => $request->user()?->getAuthIdentifier(),
        ]);

        return redirect()->to($back)->with('blog_comment_status', 'pending');
    }

    private function timeTrapPasses(string $token): bool
    {
        try {
            $rendered = (int) Crypt::decryptString($token);
        } catch (Throwable) {
            return false;
        }
        $age = now()->getTimestamp() - $rendered;

        return $age >= (int) config('blog.comments.min_seconds', 3)
            && $age <= (int) config('blog.comments.max_age_seconds', 86400);
    }
}
