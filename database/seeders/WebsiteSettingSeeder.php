<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

final class WebsiteSettingSeeder extends Seeder
{
    /** @var array<string, array{string, string, string, bool}> */
    private const SETTINGS = [
        'church_name' => ['general', 'JKI Hananeel Cinta', 'text', true],
        'church_tagline' => ['general', 'Menara penjaga kota yang berdiri dalam doa, kasih, dan pengharapan.', 'text', true],
        'church_description' => ['church_profile', 'JKI Hananeel Cinta dipanggil untuk berjaga, membangun, dan membawa kasih Tuhan bagi keluarga, kota, serta bangsa-bangsa.', 'textarea', true],
        'church_vision' => ['church_profile', 'Menjadi menara penjaga kota yang teguh dalam doa, peka melihat kebutuhan zaman, dan menghadirkan kasih Tuhan.', 'textarea', true],
        'church_mission' => ['church_profile', 'Membangun jemaat yang berjaga dalam doa, berakar dalam Firman, melayani dengan kasih, dan membawa pemulihan bagi kota serta bangsa-bangsa.', 'textarea', true],
        'church_history' => ['church_profile', 'Hananeel menggambarkan sebuah menara yang berdiri sebagai tanda pemulihan dan pengharapan bagi kota. Identitas ini mengingatkan kami untuk setia berjaga dalam doa, membangun kehidupan, serta menghadirkan kasih Tuhan bagi setiap generasi.', 'richtext', true],
        'church_address' => ['contact', "Gereja JKI Hananeel Cinta\nBlok Jl. Pangeran Tubagus Angke No.2 13, RT.13/RW.7, Jelambar Baru, Kec. Grogol petamburan, Kota Jakarta Barat, Daerah Khusus Ibukota Jakarta 11460", 'textarea', true],
        'church_phone' => ['contact', '', 'text', true],
        'church_whatsapp' => ['contact', '', 'text', true],
        'church_email' => ['contact', '', 'email', true],
        'church_instagram' => ['social_media', 'https://www.instagram.com/jkihananeelcinta?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==', 'url', true],
        'church_youtube' => ['social_media', 'https://youtube.com/@jkihananeelcinta?si=3LvFKnaoGAndKpFK', 'url', true],
        'church_maps_url' => ['contact', '', 'url', true],
        'church_service_schedule' => ['church_profile', "Ibadah Raya — Minggu, 07:00 WIB dan 10:00 WIB\nSekolah Minggu — Minggu, 07:00 WIB dan 10:00 WIB\nHoly Spirit Night — Selasa, 19:00 WIB\nRock Jakarta (Youth Service) — Sabtu, 17:00 WIB\nMezbah Keluarga — sesuai jadwal masing-masing lokasi", 'textarea', true],
        'logo' => ['branding', '', 'image', true],
        'favicon' => ['branding', '', 'image', true],
        'hero_image' => ['branding', '', 'image', true],
        'footer_description' => ['general', 'Menara penjaga kota yang berdiri dalam doa, kasih, dan pengharapan.', 'textarea', true],
        'privacy_policy' => ['legal', '', 'richtext', true],
        'seo_title' => ['seo', 'JKI Hananeel Cinta', 'text', true],
        'seo_description' => ['seo', 'JKI Hananeel Cinta, menara penjaga kota yang berdiri dalam doa, kasih, dan pengharapan.', 'textarea', true],
        'mobile_maintenance_mode' => ['mobile_api', '0', 'boolean', true],
        'mobile_minimum_version' => ['mobile_api', '', 'text', true],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => [$group, $value, $type, $isPublic]) {
            WebsiteSetting::query()->updateOrCreate(['key' => $key], compact('group', 'value', 'type') + ['is_public' => $isPublic]);
        }
    }
}
