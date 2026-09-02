<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;

class DocumentTemplateService
{
    /**
     * Create a new draft document template.
     */
    public function createTemplate(array $data): DocumentTemplate
    {
        return DocumentTemplate::create(array_merge($data, [
            'status' => 'draft',
            'version' => $data['version'] ?? '1.0'
        ]));
    }

    /**
     * Submit a template draft for clinical and compliance review.
     */
    public function submitForReview(string $id): DocumentTemplate
    {
        $template = DocumentTemplate::findOrFail($id);
        if ($template->status !== 'draft') {
            throw new \LogicException('Only draft templates can be submitted for review.');
        }

        $template->update(['status' => 'in_review']);
        return $template;
    }

    /**
     * Approve a reviewed template.
     */
    public function approveTemplate(string $id, string $approvedByUserId): DocumentTemplate
    {
        $template = DocumentTemplate::findOrFail($id);
        if ($template->status !== 'in_review') {
            throw new \LogicException('Templates must be in review before approval.');
        }

        $template->update([
            'status' => 'approved',
            'approved_by' => $approvedByUserId
        ]);
        return $template;
    }

    /**
     * Publish an approved template, archiving any older version of the same template code.
     */
    public function publishTemplate(string $id): DocumentTemplate
    {
        $template = DocumentTemplate::findOrFail($id);
        if ($template->status !== 'approved') {
            throw new \LogicException('Only approved templates can be published.');
        }

        // Archive older active versions of the same template code
        DocumentTemplate::where('template_code', $template->template_code)
            ->where('status', 'published')
            ->where('id', '!=', $id)
            ->update([
                'status' => 'archived',
                'archived_at' => now()
            ]);

        $template->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        return $template;
    }

    /**
     * Rollback to a specific template version by making it published.
     */
    public function rollbackTemplate(string $id): DocumentTemplate
    {
        $template = DocumentTemplate::findOrFail($id);
        
        // Archive the currently published version
        DocumentTemplate::where('template_code', $template->template_code)
            ->where('status', 'published')
            ->update([
                'status' => 'archived',
                'archived_at' => now()
            ]);

        $template->update([
            'status' => 'published',
            'published_at' => now(),
            'archived_at' => null
        ]);

        return $template;
    }
}
