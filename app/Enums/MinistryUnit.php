<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MinistryUnit: string
{
    use HasOptions;

    case LoverAndWorshiper = 'lover_and_worshiper';
    case SundaySchool = 'sunday_school';
    case Dancer = 'dancer';
    case Multimedia = 'multimedia';
    case Youth = 'youth';
    case Prayer = 'prayer';
    case MK = 'mk';
    case Usher = 'usher';
    case DapurCinta = 'dapur_cinta';
    case Parking = 'parking';
    case Secretariat = 'secretariat';
    case WowSchool = 'wow_school';
    case Tutoring = 'tutoring';
    case LumbungRaja = 'lumbung_raja';
    case HancinStore = 'hancin_store';
    case Maintenance = 'maintenance';
    case Shofar = 'shofar';
    case Pastoral = 'pastoral';

    public function label(): string
    {
        return match ($this) {
            self::LoverAndWorshiper => 'Lover and Worshiper',
            self::SundaySchool => 'Sekolah Minggu',
            self::Dancer => 'Dancer',
            self::Multimedia => 'Multimedia',
            self::Youth => 'Youth',
            self::Prayer => 'Pendoa',
            self::MK => 'MK',
            self::Usher => 'Usher',
            self::DapurCinta => 'Dapur Cinta',
            self::Parking => 'Parkir',
            self::Secretariat => 'Sekretariat',
            self::WowSchool => 'Sekolah WOW',
            self::Tutoring => 'Bimbel',
            self::LumbungRaja => 'Lumbung Raja',
            self::HancinStore => 'Hancin Store',
            self::Maintenance => 'Maintenance',
            self::Shofar => 'Shofar',
            self::Pastoral => 'Pastoral',
        };
    }
}
