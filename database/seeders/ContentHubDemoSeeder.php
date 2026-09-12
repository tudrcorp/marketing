<?php

namespace Database\Seeders;

use App\Marketing\ContentPillar;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostFormat;
use App\Marketing\ContentPostPriority;
use App\Marketing\ContentPostStatus;
use App\Models\Brand;
use App\Models\ContentPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de prueba del panel de contenido multimarca.
 *
 * Genera cuatro marcas y una cartera de piezas repartidas entre estados,
 * prioridades y bloqueos, incluyendo días deliberadamente saturados para
 * poder validar el semáforo de carga laboral del calendario.
 *
 * Ejecutar con: php artisan db:seed --class=ContentHubDemoSeeder
 */
class ContentHubDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->brands() as $brandData) {
            $posts = $brandData['posts'];
            unset($brandData['posts']);

            $brand = Brand::query()->updateOrCreate(
                ['slug' => $brandData['slug']],
                $brandData,
            );

            $brand->posts()->delete();

            foreach ($posts as $position => $post) {
                ContentPost::query()->create([
                    ...$post,
                    'brand_id' => $brand->id,
                    'board_position' => $position,
                ]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function brands(): array
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        return [
            [
                'name' => 'TDG Salud',
                'slug' => 'tdg-salud',
                'color_hex' => '#f9b17a',
                'brand_voice' => 'Cercana, confiable y didáctica. Habla de salud sin tecnicismos y siempre cierra invitando a agendar.',
                'drive_url' => 'https://drive.google.com/drive/folders/tdg-salud',
                'canva_url' => 'https://www.canva.com/design/tdg-salud',
                'ctas' => [
                    ['label' => 'Agenda', 'value' => 'Agenda tu cita hoy mismo por WhatsApp 👉'],
                    ['label' => 'Plan familiar', 'value' => 'Protege a tu familia desde $XX al mes.'],
                ],
                'hashtag_groups' => [
                    ['label' => 'Generales', 'value' => '#TuDoctorGroup #SaludPreventiva #BienestarFamiliar'],
                    ['label' => 'Campaña', 'value' => '#ChequeoAnual #SaludSinExcusas'],
                ],
                'quick_links' => [
                    ['label' => 'Sitio web', 'value' => 'https://tudoctorgroup.com'],
                    ['label' => 'WhatsApp', 'value' => 'https://wa.me/000000000'],
                ],
                'posts' => [
                    $this->post('5 señales de que necesitas un chequeo', ContentPostStatus::Idea, ContentPostPriority::Medium, ContentPostFormat::Carousel, ContentPillar::Educational, $monday->copy()->addDays(1)->setTime(9, 0)),
                    $this->post('Reel: mitos sobre la presión arterial', ContentPostStatus::Writing, ContentPostPriority::High, ContentPostFormat::Reel, ContentPillar::Educational, $monday->copy()->addDays(2)->setTime(11, 30)),
                    $this->post('Testimonio paciente — plan familiar', ContentPostStatus::Design, ContentPostPriority::Urgent, ContentPostFormat::Reel, ContentPillar::Testimonial, $monday->copy()->addDays(2)->setTime(16, 0), ContentPostBlocker::MissingClientVideo),
                    $this->post('Carrusel: cómo agendar en 3 pasos', ContentPostStatus::Approval, ContentPostPriority::High, ContentPostFormat::Carousel, ContentPillar::Promotional, $monday->copy()->addDays(2)->setTime(18, 0)),
                    $this->post('Historia: encuesta de síntomas', ContentPostStatus::Scheduled, ContentPostPriority::Low, ContentPostFormat::Stories, ContentPillar::Entertainment, $monday->copy()->addDays(2)->setTime(20, 0)),
                    $this->post('Post estático: horario de sedes', ContentPostStatus::Scheduled, ContentPostPriority::Medium, ContentPostFormat::Static, ContentPillar::Promotional, $monday->copy()->addDays(2)->setTime(8, 0)),
                    $this->post('Detrás de cámaras: nuevo consultorio', ContentPostStatus::Published, ContentPostPriority::Low, ContentPostFormat::Reel, ContentPillar::BehindTheScenes, $monday->copy()->subDays(3)->setTime(10, 0)),
                    $this->post('Tips de hidratación (evergreen)', ContentPostStatus::Idea, ContentPostPriority::Low, ContentPostFormat::Static, ContentPillar::Educational, null, ContentPostBlocker::None, evergreen: true),
                    $this->post('Plantilla: bienvenida de temporada', ContentPostStatus::Idea, ContentPostPriority::Medium, ContentPostFormat::Carousel, ContentPillar::Inspirational, null, ContentPostBlocker::None, replicable: true),
                ],
            ],
            [
                'name' => 'TDG Viajes',
                'slug' => 'tdg-viajes',
                'color_hex' => '#38bdf8',
                'brand_voice' => 'Aspiracional y ágil. Vende experiencias, no itinerarios. Tono joven, mucho video vertical.',
                'drive_url' => 'https://drive.google.com/drive/folders/tdg-viajes',
                'canva_url' => 'https://www.canva.com/design/tdg-viajes',
                'ctas' => [
                    ['label' => 'Cotiza', 'value' => 'Cotiza tu próximo viaje sin compromiso ✈️'],
                ],
                'hashtag_groups' => [
                    ['label' => 'Generales', 'value' => '#TDGViajes #ViajaSeguro #AsistenciaEnViaje'],
                ],
                'quick_links' => [
                    ['label' => 'Catálogo', 'value' => 'https://tudoctorgroup.com/viajes'],
                ],
                'posts' => [
                    $this->post('Reel: qué cubre la asistencia en viaje', ContentPostStatus::Writing, ContentPostPriority::Urgent, ContentPostFormat::Reel, ContentPillar::Educational, $monday->copy()->addDays(3)->setTime(12, 0)),
                    $this->post('Carrusel destinos de temporada', ContentPostStatus::Design, ContentPostPriority::High, ContentPostFormat::Carousel, ContentPillar::Promotional, $monday->copy()->addDays(3)->setTime(15, 0), ContentPostBlocker::MissingArt),
                    $this->post('Checklist de maleta', ContentPostStatus::Approval, ContentPostPriority::Medium, ContentPostFormat::Static, ContentPillar::Educational, $monday->copy()->addDays(3)->setTime(17, 0), ContentPostBlocker::InReview),
                    $this->post('Historia: encuesta próximo destino', ContentPostStatus::Scheduled, ContentPostPriority::Low, ContentPostFormat::Stories, ContentPillar::Entertainment, $monday->copy()->addDays(3)->setTime(19, 0)),
                    $this->post('Reel testimonio — viaje familiar', ContentPostStatus::Scheduled, ContentPostPriority::High, ContentPostFormat::Reel, ContentPillar::Testimonial, $monday->copy()->addDays(3)->setTime(9, 30)),
                    $this->post('Post: alianzas con aerolíneas', ContentPostStatus::Idea, ContentPostPriority::Medium, ContentPostFormat::Static, ContentPillar::Promotional, $monday->copy()->addDays(3)->setTime(7, 30)),
                    $this->post('Carrusel: seguro vs. asistencia', ContentPostStatus::Idea, ContentPostPriority::High, ContentPostFormat::Carousel, ContentPillar::Educational, $monday->copy()->addDays(3)->setTime(21, 0)),
                    $this->post('Banco comodín: paisaje inspiracional', ContentPostStatus::Idea, ContentPostPriority::Low, ContentPostFormat::Static, ContentPillar::Inspirational, null, ContentPostBlocker::None, evergreen: true),
                    $this->post('Publicado: resumen del mes', ContentPostStatus::Published, ContentPostPriority::Low, ContentPostFormat::Carousel, ContentPillar::BehindTheScenes, $monday->copy()->subDays(5)->setTime(13, 0)),
                ],
            ],
            [
                'name' => 'TDG Corporativo',
                'slug' => 'tdg-corporativo',
                'color_hex' => '#676f9d',
                'brand_voice' => 'Institucional y sobria. Enfocada en confianza, alianzas y resultados medibles.',
                'drive_url' => 'https://drive.google.com/drive/folders/tdg-corporativo',
                'canva_url' => 'https://www.canva.com/design/tdg-corporativo',
                'ctas' => [
                    ['label' => 'Alianzas', 'value' => 'Conversemos sobre una alianza corporativa.'],
                ],
                'hashtag_groups' => [
                    ['label' => 'Generales', 'value' => '#TDGCorporativo #AlianzasEstratégicas'],
                ],
                'quick_links' => [
                    ['label' => 'LinkedIn', 'value' => 'https://www.linkedin.com/company/tudoctorgroup'],
                ],
                'posts' => [
                    $this->post('Anuncio: nueva alianza regional', ContentPostStatus::Approval, ContentPostPriority::Urgent, ContentPostFormat::Static, ContentPillar::Promotional, $monday->copy()->addDays(4)->setTime(10, 0), ContentPostBlocker::InReview),
                    $this->post('Carrusel: resultados del trimestre', ContentPostStatus::Design, ContentPostPriority::High, ContentPostFormat::Carousel, ContentPillar::Educational, $monday->copy()->addDays(4)->setTime(14, 0)),
                    $this->post('Reel: equipo TDG en acción', ContentPostStatus::Writing, ContentPostPriority::Medium, ContentPostFormat::Reel, ContentPillar::BehindTheScenes, $monday->copy()->addDays(7)->setTime(11, 0)),
                    $this->post('Post: certificación obtenida', ContentPostStatus::Scheduled, ContentPostPriority::Medium, ContentPostFormat::Static, ContentPillar::Promotional, $monday->copy()->addDays(8)->setTime(9, 0)),
                    $this->post('Plantilla institucional replicable', ContentPostStatus::Idea, ContentPostPriority::Low, ContentPostFormat::Static, ContentPillar::Inspirational, null, ContentPostBlocker::None, replicable: true),
                    $this->post('Pieza vencida sin publicar', ContentPostStatus::Design, ContentPostPriority::Urgent, ContentPostFormat::Carousel, ContentPillar::Promotional, $monday->copy()->subDays(4)->setTime(9, 0), ContentPostBlocker::MissingArt),
                ],
            ],
            [
                'name' => 'TDG Bienestar',
                'slug' => 'tdg-bienestar',
                'color_hex' => '#34d399',
                'brand_voice' => 'Cálida y motivadora. Hábitos pequeños, constancia y comunidad.',
                'drive_url' => 'https://drive.google.com/drive/folders/tdg-bienestar',
                'canva_url' => 'https://www.canva.com/design/tdg-bienestar',
                'ctas' => [
                    ['label' => 'Comunidad', 'value' => 'Únete a la comunidad de bienestar TDG 💚'],
                ],
                'hashtag_groups' => [
                    ['label' => 'Generales', 'value' => '#TDGBienestar #HábitosSaludables'],
                ],
                'quick_links' => [
                    ['label' => 'Blog', 'value' => 'https://tudoctorgroup.com/blog'],
                ],
                'posts' => [
                    $this->post('Rutina de 10 minutos', ContentPostStatus::Writing, ContentPostPriority::Medium, ContentPostFormat::Reel, ContentPillar::Educational, $monday->copy()->addDays(9)->setTime(7, 0)),
                    $this->post('Historia: reto de hidratación', ContentPostStatus::Scheduled, ContentPostPriority::Low, ContentPostFormat::Stories, ContentPillar::Entertainment, $monday->copy()->addDays(10)->setTime(8, 0)),
                    $this->post('Carrusel: alimentación consciente', ContentPostStatus::Approval, ContentPostPriority::High, ContentPostFormat::Carousel, ContentPillar::Educational, $monday->copy()->addDays(11)->setTime(12, 0)),
                    $this->post('Testimonio de la comunidad', ContentPostStatus::Idea, ContentPostPriority::Medium, ContentPostFormat::Reel, ContentPillar::Testimonial, $monday->copy()->addDays(12)->setTime(17, 0), ContentPostBlocker::MissingClientVideo),
                    $this->post('Comodín: frase motivacional', ContentPostStatus::Idea, ContentPostPriority::Low, ContentPostFormat::Static, ContentPillar::Inspirational, null, ContentPostBlocker::None, evergreen: true),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function post(
        string $title,
        ContentPostStatus $status,
        ContentPostPriority $priority,
        ContentPostFormat $format,
        ContentPillar $pillar,
        ?Carbon $scheduledAt,
        ContentPostBlocker $blocker = ContentPostBlocker::None,
        bool $evergreen = false,
        bool $replicable = false,
    ): array {
        return [
            'title' => $title,
            'copy' => 'Copy de ejemplo para "'.$title.'". Sustituir por el texto definitivo antes de aprobar.',
            'status' => $status,
            'priority' => $priority,
            'format' => $format,
            'pillar' => $pillar,
            'blocker' => $blocker,
            'scheduled_at' => $scheduledAt,
            'published_at' => $status === ContentPostStatus::Published ? $scheduledAt : null,
            'time_spent_seconds' => $status === ContentPostStatus::Idea ? 0 : random_int(600, 9000),
            'is_evergreen' => $evergreen,
            'is_replicable' => $replicable,
        ];
    }
}
