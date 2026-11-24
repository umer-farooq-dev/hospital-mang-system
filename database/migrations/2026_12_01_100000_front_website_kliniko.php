<?php

use App\Models\FrontService;
use App\Models\FrontSetting;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public website on the Kliniko design: services, case studies and news share the
 * front_services table (type), every text, image, section and colour is a front setting.
 * Defaults are French (Burkina Faso); the admin replaces them from Front CMS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_services', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->default(FrontService::SERVICE)->after('id')->index();
            $table->string('category')->nullable()->after('name');
            $table->longText('description')->nullable()->after('short_description');
            $table->date('published_at')->nullable()->after('description');
            $table->unsignedInteger('sort_order')->default(0)->after('published_at');
        });

        $home = FrontSetting::HOME_PAGE;
        $settings = [
            // appearance (Burkina Faso flag)
            'theme_color_primary' => '#009E49', 'theme_color_secondary' => '#EF2B2D', 'theme_color_accent' => '#FCD116',
            'theme_color_dark' => '#00602C', 'theme_color_title' => '#10231A',
            'home_sections' => json_encode(FrontSetting::HOME_SECTIONS),
            'page_banner_image' => '',
            // hero
            'home_page_subtitle' => 'Votre santé, notre priorité',
            'home_page_video_url' => '', 'home_page_video_image' => '',
            // about block
            'home_page_experience_label' => "Années d'expérience",
            'home_page_circle_text' => 'des soins de qualité pour tous • à votre écoute • ',
            'home_page_about_points' => "Équipe médicale qualifiée\nUrgences 24h/24 et 7j/7\nLaboratoire et imagerie sur place\nPrise en charge des assurances",
            // process
            'home_page_step_1_image' => '', 'home_page_step_2_image' => '', 'home_page_step_3_image' => '', 'home_page_step_4_image' => '',
            // section headings
            'section_services_sub' => 'Nos services', 'section_services_title' => 'Des soins complets pour chaque besoin',
            'section_process_sub' => 'Votre parcours', 'section_process_title' => 'Comment se déroule votre prise en charge',
            'section_process_text' => "Un parcours simple et clair, de la prise de rendez-vous jusqu'au suivi après le traitement.",
            'section_appointment_sub' => 'Rendez-vous',
            'section_doctors_sub' => 'Nos médecins', 'section_doctors_title' => 'Des médecins engagés pour votre santé',
            'section_cases_sub' => 'Nos réalisations', 'section_cases_title' => 'Notre expertise au service des patients',
            'section_testimonials_sub' => 'Témoignages', 'section_testimonials_title' => 'Ce que disent nos patients',
            'section_blog_sub' => 'Actualités', 'section_blog_title' => "Conseils santé et nouvelles de l'hôpital",
        ];
        foreach ($settings as $key => $value) {
            FrontSetting::firstOrCreate(['key' => $key], ['value' => $value, 'type' => $home]);
        }

        // demo pictures of the old design (no upload behind them) fall back to the theme pictures
        foreach (FrontSetting::whereIn('key', array_keys(FrontSetting::IMAGE_KEYS))->get() as $row) {
            if (! $row->media()->exists()) {
                $row->update(['value' => '']);
            }
        }

        // replace the InfyHMS English demo texts, never a text the hospital already wrote
        $demo = [
            'home_page_title' => ['Des soins de qualité, proches de vous et de votre famille', 'Find Local Specialists'],
            'home_page_description' => ['Consultations, examens, hospitalisation et urgences 24h/24 : nos équipes qualifiées vous accueillent avec des équipements modernes.', 'Our medical clinic provides'],
            'home_page_experience' => ['15+', '10'],
            'home_page_certified_doctor_text' => ['À propos de nous', 'Quality Healthcare'],
            'home_page_certified_doctor_title' => ['Des soins de confiance pour toute la communauté', 'Have Certified'],
            'home_page_certified_doctor_description' => ["Nous accompagnons les patients à chaque étape : prévention, diagnostic, traitement et suivi. Médecins, infirmiers et techniciens travaillent ensemble pour une prise en charge sûre, humaine et accessible.", 'Our medical clinic provides'],
            'home_page_box_title' => ['Une question ?', 'Free Consulting'],
            'home_page_box_description' => ['Notre équipe vous répond rapidement pour toute demande médicale ou administrative.', 'Proin gravida'],
            'home_page_certified_box_title' => ['Pourquoi nous choisir ?', 'Certified Doctor'],
            'home_page_certified_box_description' => ["Des médecins expérimentés, un plateau technique complet et un accueil attentif : nous mettons tout en œuvre pour que chaque patient soit soigné rapidement et dignement.", 'Proin gravida'],
            'home_page_step_1_title' => ['Prise de rendez-vous', 'Check Doctor Profile'],
            'home_page_step_1_description' => ["Réservez en ligne, par téléphone ou à l'accueil, au moment qui vous convient.", 'Proin gravida'],
            'home_page_step_2_title' => ['Consultation', 'Request Consulting'],
            'home_page_step_2_description' => ['Un médecin vous reçoit, écoute vos symptômes et réalise un examen complet.', 'Proin gravida'],
            'home_page_step_3_title' => ['Examens et diagnostic', 'Receive Consulting'],
            'home_page_step_3_description' => ['Analyses de laboratoire et imagerie sont réalisées sur place pour un diagnostic rapide et fiable.', 'Proin gravida'],
            'home_page_step_4_title' => ['Traitement et suivi', 'Get Your Solution'],
            'home_page_step_4_description' => ["Vous recevez un traitement adapté et un suivi régulier jusqu'à votre guérison.", 'Proin gravida'],
            'appointment_title' => ['Prenez rendez-vous avec nos spécialistes', 'Contact Now and Get'],
            'appointment_description' => ['Choisissez le service, le médecin et la date : notre équipe confirme votre rendez-vous rapidement.', 'Proin gravida'],
            'about_us_title' => ['Un hôpital au service de la population', 'About For'],
            'about_us_description' => ["Notre hôpital réunit consultations, urgences, hospitalisation, bloc opératoire, laboratoire, imagerie et pharmacie. Chaque jour, nos équipes soignent enfants et adultes avec rigueur et bienveillance.", ' will teach physicians'],
            'about_us_mission' => ['Offrir à chacun des soins de qualité, accessibles et respectueux, avec une organisation moderne et transparente.', 'We are providing advanced'],
        ];
        foreach ($demo as $key => [$value, $infyStart]) {
            $row = FrontSetting::where('key', $key)->first();
            if ($row && ($row->value === null || $row->value === '' || str_starts_with($row->value, $infyStart) || str_contains($row->value, $infyStart))) {
                $row->update(['value' => $value]);
            }
        }

        // InfyHMS demo hospital details -> neutral French placeholders until the hospital enters its own
        $hospital = [
            'about_us' => ['Over past', 'Hôpital moderne au service de la population : consultations, urgences 24h/24, maternité, laboratoire, imagerie et chirurgie, avec des équipes qualifiées et attentionnées.'],
            'hospital_address' => ['16/A', 'Ouagadougou, Burkina Faso'],
            'hospital_phone' => ['+++91', '+226 25 00 00 00'],
            'hospital_from_day' => ['Mon to Fri', 'Lundi - Samedi (urgences 7j/7)'],
            'hospital_from_time' => ['9 AM', '07h00 - 19h00'],
        ];
        foreach ($hospital as $key => [$demoStart, $value]) {
            $row = Setting::where('key', $key)->first();
            if ($row && str_starts_with((string) $row->value, $demoStart)) {
                $row->update(['value' => $value]);
            }
        }

        // phone fields start on Burkina Faso (+226) unless the hospital already chose a country
        $code = Setting::where('key', 'country_code')->first();
        if ($code && $code->value === '+91') {
            $code->update(['value' => '+226']);
            Setting::where('key', 'country_name')->first()?->update(['value' => 'bf']);
        }

        if (FrontService::where('short_description', 'like', 'image Cardiology Proin%')->exists()) {
            FrontService::where('short_description', 'like', 'image Cardiology Proin%')->get()->each->delete();
        }
        if (! FrontService::exists()) {
            $this->seedContent();
        }
    }

    private function seedContent(): void
    {
        $rows = [
            [FrontService::SERVICE, 'Médecine générale', null, "Consultations pour adultes et enfants, dépistage et suivi des maladies chroniques.",
                "La médecine générale est la porte d'entrée de l'hôpital. Nos médecins examinent, diagnostiquent et orientent chaque patient vers le bon spécialiste si nécessaire.\n\n- Paludisme, fièvres et infections\n- Hypertension et diabète\n- Bilans de santé et certificats médicaux\n- Suivi des maladies chroniques"],
            [FrontService::SERVICE, 'Urgences 24h/24', null, 'Une équipe disponible jour et nuit pour les urgences médicales et chirurgicales.',
                "Le service des urgences accueille les patients à toute heure. Le tri à l'arrivée permet de traiter en priorité les situations les plus graves.\n\n- Accueil et tri 24h/24\n- Réanimation et stabilisation\n- Liaison directe avec le bloc et le laboratoire"],
            [FrontService::SERVICE, 'Maternité et pédiatrie', null, 'Suivi de grossesse, accouchement et soins des nouveau-nés et des enfants.',
                "Nous accompagnons les mères avant, pendant et après l'accouchement, et suivons la santé des enfants.\n\n- Consultations prénatales\n- Salle d'accouchement et césarienne\n- Vaccination et consultations pédiatriques"],
            [FrontService::SERVICE, 'Laboratoire', null, "Analyses biologiques réalisées sur place, résultats rapides et fiables.",
                "Notre laboratoire réalise les examens de biologie courants et spécialisés, avec contrôle qualité.\n\n- Hématologie et biochimie\n- Parasitologie (goutte épaisse, TDR)\n- Sérologies et bactériologie"],
            [FrontService::SERVICE, 'Imagerie médicale', null, 'Radiographie et échographie pour un diagnostic précis.',
                "L'imagerie complète l'examen clinique et guide le traitement.\n\n- Radiographie numérique\n- Échographie générale et obstétricale\n- Comptes rendus par un médecin"],
            [FrontService::SERVICE, 'Chirurgie et bloc opératoire', null, 'Interventions programmées et urgentes dans un bloc équipé.',
                "Nos chirurgiens et anesthésistes prennent en charge les interventions programmées et les urgences chirurgicales.\n\n- Chirurgie générale et viscérale\n- Chirurgie gynécologique et obstétricale\n- Suivi post-opératoire en hospitalisation"],
            [FrontService::CASE_STUDY, 'Prise en charge rapide du paludisme grave', 'Pédiatrie', "Un circuit dédié aux enfants fébriles réduit le délai entre l'arrivée et le traitement.",
                "Dès l'accueil, les enfants fébriles sont orientés vers un circuit court : test rapide, avis médical et traitement sans attente.\n\n- Test de diagnostic rapide à l'arrivée\n- Traitement débuté dans la première heure\n- Suivi jusqu'à la sortie"],
            [FrontService::CASE_STUDY, 'Suivi des patients diabétiques', 'Médecine interne', 'Un suivi régulier qui limite les complications et les hospitalisations.',
                "Chaque patient diabétique bénéficie d'un dossier de suivi, de contrôles réguliers et de conseils adaptés.\n\n- Contrôle de la glycémie et de la tension\n- Éducation thérapeutique\n- Dépistage des complications"],
            [FrontService::CASE_STUDY, 'Accouchements sécurisés', 'Maternité', 'Une équipe et un bloc disponibles à toute heure pour les mères et les bébés.',
                "Sages-femmes, gynécologues et anesthésistes travaillent ensemble pour sécuriser chaque naissance.\n\n- Surveillance du travail\n- Césarienne en urgence si nécessaire\n- Soins du nouveau-né"],
            [FrontService::POST, 'Se protéger du paludisme en saison des pluies', 'Prévention', 'Moustiquaires, assainissement et consultation rapide : les bons gestes.',
                "La saison des pluies augmente le risque de paludisme. Quelques gestes simples protègent toute la famille.\n\n- Dormir sous une moustiquaire imprégnée\n- Éliminer les eaux stagnantes\n- Consulter dès les premiers signes de fièvre"],
            [FrontService::POST, "L'importance des consultations prénatales", 'Maternité', 'Un suivi régulier pendant la grossesse protège la mère et l’enfant.',
                "Les consultations prénatales permettent de dépister tôt les risques et de préparer l'accouchement.\n\n- Au moins quatre consultations pendant la grossesse\n- Échographies et analyses recommandées\n- Vaccination et supplémentation"],
            [FrontService::POST, 'Hypertension : un contrôle simple qui sauve des vies', 'Conseils santé', 'Faites contrôler votre tension régulièrement, même sans symptômes.',
                "L'hypertension est souvent silencieuse. Un contrôle régulier permet de la traiter avant les complications.\n\n- Contrôler sa tension au moins une fois par an\n- Réduire le sel et pratiquer une activité physique\n- Suivre le traitement prescrit"],
        ];
        foreach ($rows as $i => [$type, $name, $category, $short, $long]) {
            FrontService::create([
                'type' => $type, 'name' => $name, 'category' => $category, 'short_description' => $short,
                'description' => $long, 'sort_order' => $i, 'published_at' => $type === FrontService::POST ? now()->subDays($i)->toDateString() : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('front_services', fn (Blueprint $table) => $table->dropColumn(['type', 'category', 'description', 'published_at', 'sort_order']));
    }
};
