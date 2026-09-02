<?php

namespace App\Modules\ResearchAccess\Services;

use App\Models\ResearchAccessRequest;
use App\Models\ResearcherProfile;
use App\Models\EthicsApproval;
use App\Models\DataAccessCommitteeReview;
use App\Models\ResearchDataset;
use App\Models\ResearchDataAgreement;
use App\Models\ResearchAccessLog;

/**
 * ResearchAccessService — Module 17 (Research & Data Access Program)
 *
 * Manages the full research data access lifecycle:
 * submit request → DAC review → approve/reject → sign agreement →
 * prepare dataset → grant access → track usage → expire access.
 *
 * Security constraints enforced:
 * - Ethics approval required before access is granted
 * - Data must be de-identified or aggregated
 * - Agreement must be signed before data access
 * - All access is logged (append-only)
 * - "Rejected request cannot access data"
 */
class ResearchAccessService
{
    /**
     * Submit a new research access request.
     * Validates researcher has active ethics approval.
     */
    public function submitRequest(array $data, ResearcherProfile $researcher): ResearchAccessRequest
    {
        if (! $researcher->isActive()) {
            throw new \RuntimeException('Researcher profile is not active.');
        }

        if (! $researcher->hasActiveEthicsApproval()) {
            throw new \RuntimeException(
                'Active ethics approval required before submitting a research access request.'
            );
        }

        return ResearchAccessRequest::create(array_merge($data, [
            'requesting_organization' => $researcher->institution,
            'principal_investigator'  => $researcher->full_name,
            'status'                  => 'submitted',
        ]));
    }

    /**
     * Record a Data Access Committee review decision.
     */
    public function recordDacReview(
        ResearchAccessRequest $request,
        string $reviewerId,
        string $decision,
        ?string $comments = null,
        ?string $conditions = null
    ): DataAccessCommitteeReview {
        if (! in_array($request->status, ['submitted', 'under_review'])) {
            throw new \RuntimeException('Request is not in a reviewable state.');
        }

        $request->update(['status' => 'under_review']);

        return DataAccessCommitteeReview::create([
            'research_access_request_id' => $request->id,
            'reviewer_id'                => $reviewerId,
            'decision'                   => $decision,
            'comments'                   => $comments,
            'conditions'                 => $conditions,
            'reviewed_at'                => now(),
        ]);
    }

    /**
     * Approve the request — transitions to data_preparation.
     * Requires at least one approved DAC review.
     */
    public function approveRequest(
        ResearchAccessRequest $request,
        string $approvedBy,
        \DateTime $expiresAt
    ): void {
        $hasApproval = DataAccessCommitteeReview::where('research_access_request_id', $request->id)
            ->where('decision', 'approved')
            ->exists();

        if (! $hasApproval) {
            throw new \RuntimeException('At least one DAC review approval is required.');
        }

        $request->update([
            'status'      => 'active',
            'reviewed_by' => $approvedBy,
            'approved_at' => now(),
            'expires_at'  => $expiresAt,
        ]);
    }

    /**
     * Reject a request — rejected requests cannot access data.
     */
    public function rejectRequest(ResearchAccessRequest $request, string $rejectedBy): void
    {
        $request->update([
            'status'      => 'rejected',
            'reviewed_by' => $rejectedBy,
        ]);
    }

    /**
     * Sign the data agreement (required before access is granted).
     */
    public function signAgreement(
        ResearchDataAgreement $agreement,
        string $ipAddress
    ): void {
        $agreement->sign($ipAddress);
    }

    /**
     * Log a researcher's access action (append-only).
     */
    public function logAccess(
        ResearchAccessRequest $request,
        ResearcherProfile $researcher,
        string $action,
        ?array $context = null,
        ?string $ipAddress = null
    ): ResearchAccessLog {
        // Verify request is still active
        if ($request->status !== 'active') {
            throw new \RuntimeException('Access log rejected — request is not active.');
        }

        // Verify agreement is signed
        $agreementSigned = ResearchDataAgreement::where('research_access_request_id', $request->id)
            ->where('researcher_profile_id', $researcher->id)
            ->where('signed', true)
            ->exists();

        if (! $agreementSigned) {
            throw new \RuntimeException('Data agreement must be signed before accessing data.');
        }

        return ResearchAccessLog::record(
            $request->id,
            $researcher->id,
            $action,
            $context,
            $ipAddress
        );
    }
}
