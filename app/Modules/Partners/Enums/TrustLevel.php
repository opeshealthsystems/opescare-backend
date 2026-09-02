<?php

namespace App\Modules\Partners\Enums;

enum TrustLevel: string
{
    case LEVEL_0_UNVERIFIED = 'level_0_unverified';
    case LEVEL_1_REGISTERED = 'level_1_registered';
    case LEVEL_2_DOCUMENT_VERIFIED = 'level_2_document_verified';
    case LEVEL_3_OPERATIONAL_VERIFIED = 'level_3_operational_verified';
    case LEVEL_4_CLINICAL_TRUSTED = 'level_4_clinical_trusted';
    case LEVEL_5_GOVERNANCE_TRUSTED = 'level_5_governance_trusted';
}
