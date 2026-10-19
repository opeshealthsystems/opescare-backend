<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Services\Blog\BlogRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * /admin/blog/comments — the moderation queue for blog comments.
 * Platform admins only (platform.admin middleware, see routes/blog.php).
 */
class BlogCommentModerationController extends Controller
{
    public function index(Request $request, BlogRepository $posts)
    {
        $status = in_array($request->query('status'), BlogComment::STATUSES, true) ? $request->query('status') : 'pending';

        $comments = BlogComment::query()
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $counts = BlogComment::query()
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status')
            ->all();

        $titles = [];
        foreach ($comments as $c) {
            $titles[$c->locale . ':' . $c->post_slug] ??= ($posts->find($c->locale, $c->post_slug)['title'] ?? $c->post_slug);
        }

        return view('portals.admin.blog.comments', [
            'comments' => $comments,
            'status'   => $status,
            'counts'   => $counts,
            'titles'   => $titles,
        ]);
    }

    public function updateStatus(Request $request, BlogComment $comment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(BlogComment::STATUSES)],
        ]);

        $comment->status = $data['status'];
        $comment->save();

        return back()->with('status', __('blog.admin.updated'));
    }

    public function destroy(BlogComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('status', __('blog.admin.deleted'));
    }
}
