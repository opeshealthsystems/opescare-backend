<?php

namespace App\Modules\Partners\Enums;

enum PartnerType: string
{
    case GOVERNMENT = 'government';
    case HEALTHCARE_FACILITY = 'healthcare_facility';
    case HEALTHCARE_PROFESSIONAL = 'healthcare_professional';
    case PHARMACY_AND_SUPPLY = 'pharmacy_and_supply';
    case INSURANCE_AND_PAYMENT = 'insurance_and_payment';
    case ACADEMIC_AND_RESEARCH = 'academic_and_research';
    case TECHNOLOGY_AND_INTEROPERABILITY = 'technology_and_interoperability';
    case CIVIL_SOCIETY = 'civil_society';
    case LEGAL_AND_GOVERNANCE = 'legal_and_governance';
}
