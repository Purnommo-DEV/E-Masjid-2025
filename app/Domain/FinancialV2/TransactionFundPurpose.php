<?php

namespace App\Domain\FinancialV2;

enum TransactionFundPurpose: string
{
    case OrphanSupport = 'orphan_support';
    case DhuafaAssistance = 'dhuafa_assistance';
    case OrphanAndDhuafaSupport = 'orphan_and_dhuafa_support';
    case PoorMustahikAssistance = 'poor_mustahik_assistance';
    case CommunitySocialAssistance = 'community_social_assistance';
    case EducationAssistance = 'education_assistance';
    case HealthcareAssistance = 'healthcare_assistance';
    case HumanitarianDisasterAssistance = 'humanitarian_disaster_assistance';
    case BereavementSupport = 'bereavement_support';
    case DawahReligiousActivities = 'dawah_religious_activities';
    case MosqueActivityOperations = 'mosque_activity_operations';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OrphanSupport => 'Santunan Anak Yatim',
            self::DhuafaAssistance => 'Bantuan Duafa',
            self::OrphanAndDhuafaSupport => 'Santunan Anak Yatim dan Duafa',
            self::PoorMustahikAssistance => 'Bantuan Fakir Miskin / Mustahik',
            self::CommunitySocialAssistance => 'Bantuan Sosial Warga',
            self::EducationAssistance => 'Bantuan Pendidikan',
            self::HealthcareAssistance => 'Bantuan Kesehatan / Pengobatan',
            self::HumanitarianDisasterAssistance => 'Bantuan Kemanusiaan / Bencana',
            self::BereavementSupport => 'Santunan Kematian / Takziah',
            self::DawahReligiousActivities => 'Kegiatan Dakwah dan Keagamaan',
            self::MosqueActivityOperations => 'Operasional Kegiatan Masjid',
            self::Other => 'Lainnya',
        };
    }
}
