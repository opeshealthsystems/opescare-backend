<?php

namespace App\Modules\Support\Services;

use App\Models\KnowledgeBaseArticle;

/**
 * KnowledgeBaseService — Manages support knowledge base articles.
 *
 * The knowledge base provides self-service answers for facility staff,
 * reducing support ticket volume. Articles are public to authenticated
 * users within their role scope.
 *
 * No PHI is stored in knowledge base articles.
 */
class KnowledgeBaseService
{
    public function search(string $query, string $role = null): \Illuminate\Database\Eloquent\Collection
    {
        $q = KnowledgeBaseArticle::where('is_published', true)
            ->where(function ($qb) use ($query) {
                $qb->where('title', 'ilike', "%{$query}%")
                   ->orWhere('content', 'ilike', "%{$query}%")
                   ->orWhere('tags', 'ilike', "%{$query}%");
            });

        if ($role) {
            $q->where(function ($qb) use ($role) {
                $qb->whereJsonContains('audience_roles', $role)
                   ->orWhereNull('audience_roles');
            });
        }

        return $q->orderByDesc('view_count')->limit(20)->get();
    }

    public function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return KnowledgeBaseArticle::where('is_published', true)
            ->where('category', $category)
            ->orderBy('sort_order')
            ->get();
    }

    public function recordView(string $articleId): void
    {
        KnowledgeBaseArticle::where('id', $articleId)
            ->increment('view_count');
    }

    public function publish(string $articleId, string $publishedBy): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::findOrFail($articleId);
        $article->update([
            'is_published' => true,
            'published_by' => $publishedBy,
            'published_at' => now(),
        ]);
        return $article->fresh();
    }

    public function unpublish(string $articleId): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::findOrFail($articleId);
        $article->update(['is_published' => false]);
        return $article->fresh();
    }
}
