<?php

namespace Database\Seeders;

use App\Models\AgenciaOnboardingPlantilla;
use Illuminate\Database\Seeder;

/**
 * Seeder de plantillas de onboarding de la agencia.
 * Generado desde la BD el 2026-06-07 02:34.
 * Usa updateOrCreate por slug: re-ejecutar actualiza sin duplicar.
 */
class AgenciaOnboardingPlantillaSeeder extends Seeder
{
    public function run(): void
    {
        $plantillas = array (
  0 => 
  array (
    'nombre' => 'Diseno Web Shopify - Prototipo',
    'slug' => 'shopify-prototipo',
    'tipo_servicio' => 'shopify_prototipo',
    'descripcion' => 'Onboarding completo para tienda Shopify - Prototipo. 7 secciones: identidad visual, contenido, catálogo, comercial, dominio/accesos, referencias y cierre.',
    'dias_habiles_estimados' => 20,
    'secciones' => 
    array (
      0 => 
      array (
        'key' => 'identidad_visual',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'logo_principal',
            'tipo' => 'archivo_multiple',
            'label' => 'Logo principal (vectorial + PNG)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'isotipo',
            'tipo' => 'archivo_multiple',
            'label' => 'Isotipo / símbolo (si lo tienes)',
            'requerido' => false,
          ),
          2 => 
          array (
            'key' => 'paleta_colores',
            'tipo' => 'texto',
            'label' => 'Paleta de colores (HEX/RGB)',
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'tipografias',
            'tipo' => 'texto',
            'label' => 'Tipografías (nombre o link Google Fonts)',
            'requerido' => true,
          ),
          4 => 
          array (
            'key' => 'manual_marca',
            'tipo' => 'archivo_unico',
            'label' => 'Manual de marca (PDF, opcional)',
            'requerido' => false,
          ),
          5 => 
          array (
            'key' => 'tagline',
            'tipo' => 'texto_corto',
            'label' => 'Tagline / eslogan',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Identidad visual de marca',
        'subtitulo' => 'Logo, colores, tipografía y manual',
      ),
      1 => 
      array (
        'key' => 'contenido_textos',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'quienes_somos',
            'tipo' => 'texto_largo',
            'label' => 'Quiénes somos / Nuestra historia (100-250 palabras)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'mision_vision',
            'tipo' => 'texto_largo',
            'label' => 'Misión y visión (si las tienes)',
            'requerido' => false,
          ),
          2 => 
          array (
            'key' => 'politica_envio',
            'tipo' => 'texto_largo',
            'label' => 'Política de envíos',
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'politica_cambios',
            'tipo' => 'texto_largo',
            'label' => 'Política de cambios y devoluciones',
            'requerido' => true,
          ),
          4 => 
          array (
            'key' => 'politica_privacidad',
            'tipo' => 'texto_largo',
            'label' => 'Política de privacidad y T&C',
            'requerido' => true,
          ),
          5 => 
          array (
            'key' => 'faqs',
            'tipo' => 'texto_largo',
            'label' => 'Preguntas frecuentes (mínimo 5)',
            'requerido' => true,
          ),
          6 => 
          array (
            'key' => 'datos_contacto',
            'tipo' => 'texto',
            'label' => 'Email + teléfono + horario de atención',
            'requerido' => true,
          ),
          7 => 
          array (
            'key' => 'redes_sociales',
            'tipo' => 'texto',
            'label' => 'Links a redes sociales activas',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Contenido y textos del sitio',
        'subtitulo' => 'Lo que se va a leer en tu tienda',
      ),
      2 => 
      array (
        'key' => 'catalogo_productos',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'planilla_productos',
            'tipo' => 'productos_constructor',
            'label' => 'Tus productos (subilos uno por uno)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'colecciones',
            'tipo' => 'texto_largo',
            'label' => 'Lista de colecciones / categorías a mostrar',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'banners_home',
            'tipo' => 'archivo_multiple',
            'label' => 'Banners para Home (alta resolución, mín. 1920 px ancho)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Catálogo de productos',
        'subtitulo' => 'Datos e imágenes para la carga',
      ),
      3 => 
      array (
        'key' => 'comercial_tributario',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'razon_social',
            'tipo' => 'texto',
            'label' => 'Razón social + RUT + giro + representante legal',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'emite',
            'tipo' => 'select',
            'label' => '¿Emite?',
            'opciones' => 
            array (
              0 => 'Boleta',
              1 => 'Factura',
              2 => 'Ambas',
            ),
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'proveedor_dte',
            'tipo' => 'select',
            'label' => 'Proveedor DTE',
            'opciones' => 
            array (
              0 => 'Bsale',
              1 => 'Lioren',
              2 => 'Facture',
              3 => 'Otro',
            ),
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'pasarela',
            'tipo' => 'select',
            'label' => 'Pasarela de pago',
            'opciones' => 
            array (
              0 => 'Transbank Webpay',
              1 => 'Mercado Pago',
              2 => 'Flow',
              3 => 'Khipu',
              4 => 'Asesorar',
            ),
            'requerido' => true,
          ),
          4 => 
          array (
            'key' => 'cuenta_deposito',
            'tipo' => 'texto',
            'label' => 'Cuenta bancaria de depósito (banco, tipo, N°, titular)',
            'requerido' => true,
          ),
          5 => 
          array (
            'key' => 'despacho_zonas',
            'tipo' => 'texto_largo',
            'label' => 'Comunas/regiones de despacho + tarifas',
            'requerido' => true,
          ),
          6 => 
          array (
            'key' => 'courier',
            'tipo' => 'select',
            'label' => 'Empresa de courier',
            'opciones' => 
            array (
              0 => 'Starken',
              1 => 'Chilexpress',
              2 => 'Bluexpress',
              3 => 'Propio',
              4 => 'Mixto',
            ),
            'requerido' => true,
          ),
          7 => 
          array (
            'key' => 'retiro_tienda',
            'tipo' => 'texto',
            'label' => 'Retiro en tienda (dirección + horario, si aplica)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Configuración comercial y tributaria',
        'subtitulo' => 'Lo necesario para que la tienda pueda vender legalmente',
      ),
      4 => 
      array (
        'key' => 'dominio_accesos',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'dominio',
            'tipo' => 'texto_corto',
            'label' => 'Dominio (ej. tutienda.cl)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'proveedor_dominio',
            'tipo' => 'texto_corto',
            'label' => 'Dónde lo compraste (NIC.cl, GoDaddy, etc.)',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'correos_institucionales',
            'tipo' => 'texto',
            'label' => '¿Qué correos profesionales necesitas? (ej. contacto@, ventas@)',
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'pixeles',
            'tipo' => 'texto',
            'label' => 'IDs de Meta Pixel, GA4, Google Ads, TikTok Pixel (si los tienes)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Dominio, correos y accesos técnicos',
        'subtitulo' => 'Para que la tienda viva en tu dominio',
      ),
      5 => 
      array (
        'key' => 'referencias_visuales',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'sitios_referencia',
            'tipo' => 'texto_largo',
            'label' => '3 a 5 sitios que te gustan + qué te gusta de cada uno',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'tablero_referencias',
            'tipo' => 'texto',
            'label' => 'Link de RRSS (Instagram, Facebook, u otra)',
            'requerido' => false,
          ),
          2 => 
          array (
            'key' => 'tono_comunicacion',
            'tipo' => 'select',
            'label' => 'Tono de comunicación',
            'opciones' => 
            array (
              0 => 'Cercano',
              1 => 'Técnico',
              2 => 'Premium',
              3 => 'Lúdico',
              4 => 'Formal',
            ),
            'requerido' => true,
          ),
        ),
        'titulo' => 'Inspiración y referencias visuales',
        'subtitulo' => 'Para alinear el estilo desde el inicio',
      ),
      6 => 
      array (
        'key' => 'cierre',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'observaciones',
            'tipo' => 'texto_largo',
            'label' => 'Observaciones finales / comentarios para BigStudio',
            'requerido' => false,
          ),
          1 => 
          array (
            'key' => 'material_listo',
            'tipo' => 'confirmacion',
            'label' => 'Confirmo que el material está 100% completo y BigStudio puede arrancar el reloj del proyecto',
            'requerido' => true,
          ),
        ),
        'titulo' => 'Material listo',
        'subtitulo' => 'Cuando tengas todo, avísanos y arrancamos',
      ),
    ),
    'activo' => true,
  ),
  1 => 
  array (
    'nombre' => 'Gestión de Campañas Meta Ads',
    'slug' => 'meta-ads-mensual',
    'tipo_servicio' => 'meta_ads',
    'descripcion' => 'Onboarding para servicio mensual de campañas Facebook + Instagram Ads',
    'dias_habiles_estimados' => 7,
    'secciones' => 
    array (
      0 => 
      array (
        'key' => 'objetivos_negocio',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'objetivo_principal',
            'tipo' => 'select',
            'label' => 'Objetivo principal',
            'opciones' => 
            array (
              0 => 'Aumentar ventas online',
              1 => 'Generar leads/contactos',
              2 => 'Aumentar tráfico al sitio',
              3 => 'Reconocimiento de marca',
              4 => 'Llamadas/conversaciones a WhatsApp',
            ),
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'kpi_clave',
            'tipo' => 'texto',
            'label' => 'KPI principal a optimizar (ej. ROAS 3, CPL <$5.000)',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'meta_mensual',
            'tipo' => 'texto',
            'label' => 'Meta concreta para los primeros 3 meses',
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'temporadas_clave',
            'tipo' => 'texto_largo',
            'label' => 'Temporadas/eventos clave del año (Black Friday, día del padre, etc.)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Objetivos del negocio',
        'subtitulo' => 'Qué quieres lograr con las campañas',
      ),
      1 => 
      array (
        'key' => 'producto_oferta',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'productos_top',
            'tipo' => 'texto_largo',
            'label' => 'Top 5 productos/servicios a destacar',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'ticket_promedio',
            'tipo' => 'texto_corto',
            'label' => 'Ticket promedio actual (CLP)',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'margen',
            'tipo' => 'texto_corto',
            'label' => 'Margen aproximado por venta (%)',
            'requerido' => false,
          ),
          3 => 
          array (
            'key' => 'oferta_actual',
            'tipo' => 'texto_largo',
            'label' => 'Oferta o promo principal a comunicar',
            'requerido' => true,
          ),
          4 => 
          array (
            'key' => 'diferenciales',
            'tipo' => 'texto_largo',
            'label' => 'Por qué te compran a ti y no a la competencia (3-5 razones)',
            'requerido' => true,
          ),
        ),
        'titulo' => 'Producto y oferta',
        'subtitulo' => 'Qué vamos a anunciar y cómo',
      ),
      2 => 
      array (
        'key' => 'audiencias',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'cliente_ideal',
            'tipo' => 'texto_largo',
            'label' => 'Describe a tu cliente ideal (edad, género, ocupación, intereses)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'zonas_geograficas',
            'tipo' => 'texto',
            'label' => 'Zonas geográficas (regiones, comunas)',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'competencia',
            'tipo' => 'texto_largo',
            'label' => 'Top 3 competidores (cuentas Instagram + por qué los miras)',
            'requerido' => false,
          ),
          3 => 
          array (
            'key' => 'audiencias_existentes',
            'tipo' => 'texto',
            'label' => '¿Tienes lista de clientes/leads para subir como audiencia personalizada?',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Audiencias',
        'subtitulo' => 'A quién le vamos a hablar',
      ),
      3 => 
      array (
        'key' => 'creatividades',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'fotos_producto',
            'tipo' => 'archivo_multiple',
            'label' => 'Fotos de producto en alta resolución',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'videos',
            'tipo' => 'archivo_multiple',
            'label' => 'Videos cortos del producto/marca (mp4)',
            'requerido' => false,
          ),
          2 => 
          array (
            'key' => 'lifestyle',
            'tipo' => 'archivo_multiple',
            'label' => 'Fotos lifestyle (producto en uso, ambientes)',
            'requerido' => false,
          ),
          3 => 
          array (
            'key' => 'logo_brand',
            'tipo' => 'archivo_unico',
            'label' => 'Logo en vectorial + PNG',
            'requerido' => true,
          ),
          4 => 
          array (
            'key' => 'tono_comunicacion',
            'tipo' => 'select',
            'label' => 'Tono de los anuncios',
            'opciones' => 
            array (
              0 => 'Cercano y emocional',
              1 => 'Técnico y directo',
              2 => 'Premium y aspiracional',
              3 => 'Lúdico y divertido',
              4 => 'Formal y profesional',
            ),
            'requerido' => true,
          ),
        ),
        'titulo' => 'Creatividades y material',
        'subtitulo' => 'Lo visual que vamos a usar',
      ),
      4 => 
      array (
        'key' => 'tecnico_accesos',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'business_manager',
            'tipo' => 'texto',
            'label' => '¿Tienes Business Manager en Meta? Compartir ID',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'pagina_facebook',
            'tipo' => 'texto',
            'label' => 'URL de Página de Facebook',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'instagram_pro',
            'tipo' => 'texto',
            'label' => 'Username de Instagram Pro/Business',
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'pixel_meta',
            'tipo' => 'texto',
            'label' => '¿Tienes Meta Pixel instalado? ID del pixel',
            'requerido' => false,
          ),
          4 => 
          array (
            'key' => 'catalogo_productos',
            'tipo' => 'texto',
            'label' => '¿Tienes catálogo de productos en Meta? ID del catálogo',
            'requerido' => false,
          ),
          5 => 
          array (
            'key' => 'metodo_pago_meta',
            'tipo' => 'select',
            'label' => 'Método de pago de Meta Ads',
            'opciones' => 
            array (
              0 => 'Tarjeta propia',
              1 => 'Tarjeta BigStudio (con cargo)',
              2 => 'No tengo aún',
            ),
            'requerido' => true,
          ),
        ),
        'titulo' => 'Accesos técnicos',
        'subtitulo' => 'Para que podamos trabajar',
      ),
      5 => 
      array (
        'key' => 'presupuesto',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'presupuesto_mensual',
            'tipo' => 'texto',
            'label' => 'Presupuesto mensual para inversión en Meta (CLP)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'distribucion_inicial',
            'tipo' => 'select',
            'label' => 'Estrategia de inversión inicial',
            'opciones' => 
            array (
              0 => 'Test agresivo en primeras 2 semanas',
              1 => 'Crecimiento gradual y controlado',
              2 => 'Confío en la recomendación BigStudio',
            ),
            'requerido' => true,
          ),
        ),
        'titulo' => 'Presupuesto',
        'subtitulo' => 'Cuánto vamos a invertir',
      ),
      6 => 
      array (
        'key' => 'cierre',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'observaciones',
            'tipo' => 'texto_largo',
            'label' => 'Observaciones / cosas que debamos saber',
            'requerido' => false,
          ),
          1 => 
          array (
            'key' => 'material_listo',
            'tipo' => 'confirmacion',
            'label' => 'Confirmo que la información está completa y autorizo a BigStudio a iniciar las campañas según lo acordado',
            'requerido' => true,
          ),
        ),
        'titulo' => 'Material listo',
        'subtitulo' => 'Para arrancar campañas',
      ),
    ),
    'activo' => true,
  ),
  2 => 
  array (
    'nombre' => 'SEO Mensual',
    'slug' => 'seo-mensual',
    'tipo_servicio' => 'seo_mensual',
    'descripcion' => 'Onboarding para servicio mensual de SEO',
    'dias_habiles_estimados' => 10,
    'secciones' => 
    array (
      0 => 
      array (
        'key' => 'objetivos_seo',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'objetivo_principal',
            'tipo' => 'select',
            'label' => 'Objetivo principal',
            'opciones' => 
            array (
              0 => 'Posicionar nuevas keywords',
              1 => 'Defender keywords actuales',
              2 => 'Recuperar tráfico perdido',
              3 => 'Aumentar autoridad de dominio',
              4 => 'Conversión orgánica',
            ),
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'mercado_objetivo',
            'tipo' => 'texto',
            'label' => 'Mercado objetivo (país, idioma)',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'competidores_organicos',
            'tipo' => 'texto_largo',
            'label' => 'Top 3 competidores que aparecen en Google por tus keywords',
            'requerido' => true,
          ),
        ),
        'titulo' => 'Objetivos SEO',
        'subtitulo' => 'Qué buscamos lograr',
      ),
      1 => 
      array (
        'key' => 'sitio_actual',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'url_sitio',
            'tipo' => 'texto',
            'label' => 'URL del sitio web',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'cms',
            'tipo' => 'select',
            'label' => 'Plataforma del sitio',
            'opciones' => 
            array (
              0 => 'Shopify',
              1 => 'WordPress',
              2 => 'WooCommerce',
              3 => 'Wix',
              4 => 'Custom (PHP/Node)',
              5 => 'Otro',
            ),
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'edad_sitio',
            'tipo' => 'texto_corto',
            'label' => 'Antigüedad del sitio (años)',
            'requerido' => false,
          ),
          3 => 
          array (
            'key' => 'trabajo_seo_previo',
            'tipo' => 'texto_largo',
            'label' => '¿Han hecho SEO antes? Si sí, qué y cuándo',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Estado actual del sitio',
        'subtitulo' => 'Punto de partida',
      ),
      2 => 
      array (
        'key' => 'keywords',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'keywords_objetivo',
            'tipo' => 'texto_largo',
            'label' => 'Lista de 10-20 keywords que te interesa posicionar',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'temas_blog',
            'tipo' => 'texto_largo',
            'label' => 'Temas de blog que te interesa cubrir',
            'requerido' => false,
          ),
          2 => 
          array (
            'key' => 'paginas_clave',
            'tipo' => 'texto_largo',
            'label' => 'URLs específicas a optimizar prioritariamente',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Keywords e ideas',
        'subtitulo' => 'Por qué te buscan',
      ),
      3 => 
      array (
        'key' => 'accesos_tecnicos',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'google_search_console',
            'tipo' => 'texto',
            'label' => '¿Tienes Search Console? Email para agregarnos como editor',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'google_analytics',
            'tipo' => 'texto',
            'label' => '¿Tienes GA4? Email para agregarnos',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'cms_acceso',
            'tipo' => 'texto',
            'label' => 'Usuario/acceso de editor al CMS',
            'requerido' => true,
          ),
          3 => 
          array (
            'key' => 'hosting_acceso',
            'tipo' => 'texto',
            'label' => 'Acceso al hosting (para sitemap, robots, etc.)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Accesos técnicos',
        'subtitulo' => 'Para auditar y optimizar',
      ),
      4 => 
      array (
        'key' => 'recursos_contenido',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'frecuencia_contenido',
            'tipo' => 'select',
            'label' => 'Frecuencia de publicación deseada',
            'opciones' => 
            array (
              0 => '1 contenido al mes',
              1 => '2 contenidos al mes',
              2 => '4 contenidos al mes',
              3 => '1 por semana o más',
            ),
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'redactor_propio',
            'tipo' => 'select',
            'label' => '¿Quién escribe los contenidos?',
            'opciones' => 
            array (
              0 => 'BigStudio (incluido)',
              1 => 'Cliente con guía BigStudio',
              2 => 'Contenidos ya existen, solo optimización',
            ),
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'fotos_disponibles',
            'tipo' => 'archivo_multiple',
            'label' => 'Banco de fotos/imágenes propias del cliente',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Contenido y producción',
        'subtitulo' => 'Capacidad para nuevos contenidos',
      ),
      5 => 
      array (
        'key' => 'cierre',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'observaciones',
            'tipo' => 'texto_largo',
            'label' => 'Observaciones',
            'requerido' => false,
          ),
          1 => 
          array (
            'key' => 'material_listo',
            'tipo' => 'confirmacion',
            'label' => 'Confirmo que toda la información está lista para que BigStudio arranque',
            'requerido' => true,
          ),
        ),
        'titulo' => 'Material listo',
        'subtitulo' => 'Para arrancar SEO',
      ),
    ),
    'activo' => true,
  ),
  3 => 
  array (
    'nombre' => 'Mantención Mensual Shopify',
    'slug' => 'mantencion-shopify',
    'tipo_servicio' => 'mantencion',
    'descripcion' => 'Onboarding para servicio mensual de mantención de tienda Shopify',
    'dias_habiles_estimados' => 5,
    'secciones' => 
    array (
      0 => 
      array (
        'key' => 'alcance',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'tareas_recurrentes',
            'tipo' => 'texto_largo',
            'label' => 'Tareas que necesitas que hagamos cada mes (cambios de portada, banners, lanzamientos, etc.)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'cantidad_solicitudes',
            'tipo' => 'select',
            'label' => 'Volumen de solicitudes esperado al mes',
            'opciones' => 
            array (
              0 => 'Hasta 5 solicitudes',
              1 => '5 a 10 solicitudes',
              2 => '10 a 20 solicitudes',
              3 => '20+ (plan custom)',
            ),
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'turn_around',
            'tipo' => 'select',
            'label' => 'Tiempo de respuesta esperado',
            'opciones' => 
            array (
              0 => 'Same day (24 hrs)',
              1 => '48 hrs hábiles',
              2 => '3-5 días hábiles',
            ),
            'requerido' => true,
          ),
        ),
        'titulo' => 'Alcance de la mantención',
        'subtitulo' => 'Qué necesitas mes a mes',
      ),
      1 => 
      array (
        'key' => 'accesos_shopify',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'url_tienda',
            'tipo' => 'texto',
            'label' => 'URL de la tienda Shopify (.myshopify.com y dominio real)',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'colaborador_acceso',
            'tipo' => 'texto',
            'label' => 'Email para invitarnos como colaborador en Shopify',
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'theme_actual',
            'tipo' => 'texto_corto',
            'label' => 'Nombre del theme actual',
            'requerido' => false,
          ),
          3 => 
          array (
            'key' => 'apps_instaladas',
            'tipo' => 'texto_largo',
            'label' => 'Apps principales que usas (para no romper integraciones)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Accesos Shopify',
        'subtitulo' => 'Para trabajar en tu tienda',
      ),
      2 => 
      array (
        'key' => 'comunicacion',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'contraparte',
            'tipo' => 'texto',
            'label' => 'Nombre + email de la única contraparte que envía solicitudes',
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'canal_solicitud',
            'tipo' => 'select',
            'label' => 'Canal preferido para enviar tareas',
            'opciones' => 
            array (
              0 => 'Email a hola@bigstudio.cl',
              1 => 'WhatsApp',
              2 => 'Trello/ClickUp/Notion compartido',
            ),
            'requerido' => true,
          ),
          2 => 
          array (
            'key' => 'reportes',
            'tipo' => 'select',
            'label' => 'Frecuencia de reporte de tareas hechas',
            'opciones' => 
            array (
              0 => 'Reporte semanal',
              1 => 'Reporte quincenal',
              2 => 'Reporte mensual',
            ),
            'requerido' => true,
          ),
        ),
        'titulo' => 'Comunicación y solicitudes',
        'subtitulo' => 'Cómo nos pides las cosas',
      ),
      3 => 
      array (
        'key' => 'monitoreo',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'monitoreo_uptime',
            'tipo' => 'select',
            'label' => '¿Quieres monitoreo 24/7 de uptime?',
            'opciones' => 
            array (
              0 => 'Sí (incluido en plan)',
              1 => 'No, basta con response time normal',
            ),
            'requerido' => true,
          ),
          1 => 
          array (
            'key' => 'contacto_emergencia',
            'tipo' => 'texto',
            'label' => 'Teléfono/WhatsApp de emergencia (fuera de horario)',
            'requerido' => false,
          ),
        ),
        'titulo' => 'Monitoreo y emergencias',
        'subtitulo' => 'Qué hacemos si algo falla',
      ),
      4 => 
      array (
        'key' => 'cierre',
        'campos' => 
        array (
          0 => 
          array (
            'key' => 'observaciones',
            'tipo' => 'texto_largo',
            'label' => 'Observaciones',
            'requerido' => false,
          ),
          1 => 
          array (
            'key' => 'material_listo',
            'tipo' => 'confirmacion',
            'label' => 'Confirmo que la información está completa y la mantención puede comenzar',
            'requerido' => true,
          ),
        ),
        'titulo' => 'Material listo',
        'subtitulo' => 'Para iniciar mantención',
      ),
    ),
    'activo' => true,
  ),
);

        foreach ($plantillas as $datos) {
            AgenciaOnboardingPlantilla::updateOrCreate(
                ['slug' => $datos['slug']],
                $datos
            );
        }
    }
}
