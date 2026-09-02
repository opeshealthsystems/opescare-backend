<?php

namespace App\Modules\Partners\Enums;

enum PartnerStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case MORE_INFORMATION_REQUIRED = 'more_information_required';
    case VERIFIED = 'verified';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case LIMITED = 'limited';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    case ARCHIVED = 'archived';
}
