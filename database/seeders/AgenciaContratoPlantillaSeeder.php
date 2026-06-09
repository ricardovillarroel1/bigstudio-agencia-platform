<?php

namespace Database\Seeders;

use App\Models\AgenciaContratoPlantilla;
use Illuminate\Database\Seeder;

class AgenciaContratoPlantillaSeeder extends Seeder
{
    public function run(): void
    {
        $plantillas = array (
  0 => 
  array (
    'nombre' => 'Contrato — Diseño Web Shopify',
    'slug' => 'contrato-shopify',
    'tipo_servicio' => 'shopify_prototipo',
    'intro' => 'El presente contrato establece las condiciones bajo las cuales BigStudio (Inversiones RV SpA) prestará al Cliente el servicio de diseño y construcción de su tienda online en Shopify. Te recomendamos leerlo con atención antes de aceptar.',
    'clausulas' => 
    array (
      0 => 
      array (
        'titulo' => '1. Identificación de las partes',
        'contenido' => 'Por una parte, <b>INVERSIONES RV SPA</b>, RUT 78.153.109-K, giro Servicios de publicidad, domicilio en 10 Norte 882, Viña del Mar, representada por <b>Ricardo Andrés Villarroel González</b>, RUT 22.070.453-9 (en adelante el <b>Prestador</b> o <b>BigStudio</b>); y por la otra, <b>{{CLIENTE_NOMBRE}}</b>{{CLIENTE_RUT}} (en adelante el <b>Cliente</b>), acuerdan el presente contrato de prestación de servicios, que se regirá por las siguientes cláusulas.',
      ),
      1 => 
      array (
        'titulo' => '2. Objeto del contrato',
        'contenido' => 'El Prestador desarrollará para el Cliente el <b>diseño y construcción de una tienda online en la plataforma Shopify</b>, conforme al alcance descrito en la cláusula 3 y a la información entregada por el Cliente a través del portal de onboarding.',
      ),
      2 => 
      array (
        'titulo' => '3. Alcance del servicio',
        'contenido' => 'El servicio comprende: (a) configuración inicial de la tienda Shopify; (b) aplicación de logo, paleta y tipografías del Cliente; (c) diseño de las páginas clave (inicio, colección, producto, carrito, checkout, contacto y políticas); (d) carga de hasta <b>20 productos</b> con la información provista por el Cliente; (e) configuración de pasarelas de pago y reglas de envío; (f) optimización básica y revisión responsiva. <b>Productos adicionales sobre los 20 incluidos se cobrarán por separado.</b>',
      ),
      3 => 
      array (
        'titulo' => '4. Trabajos fuera de alcance y órdenes de cambio',
        'contenido' => 'Cualquier requerimiento no contemplado expresamente en la cláusula 3 —incluyendo, a modo ejemplar, campañas publicitarias, producción audiovisual, redacción de contenidos, integraciones a medida, funcionalidades especiales o cambios de dirección creativa una vez aprobado el diseño— se considera <b>trabajo adicional</b> y se cotizará por separado mediante una <b>orden de cambio</b>, que deberá ser aceptada por escrito por el Cliente antes de su ejecución.',
      ),
      4 => 
      array (
        'titulo' => '5. Revisiones y aprobaciones',
        'contenido' => 'El servicio incluye <b>dos (2) rondas de revisión</b> sobre los entregables. Las observaciones deberán enviarse consolidadas y por escrito. Rondas de revisión adicionales se cobrarán como trabajo adicional. Si el Cliente no formula observaciones dentro de <b>5 días hábiles</b> desde la entrega, el entregable se entenderá <b>aprobado tácitamente</b>.',
      ),
      5 => 
      array (
        'titulo' => '6. Plazos y caducidad',
        'contenido' => 'El plazo estimado de entrega es de <b>15 a 20 días hábiles</b> contados desde que el Cliente entregue la totalidad del material requerido. Los plazos se suspenden mientras el Prestador esté a la espera de información, accesos o validaciones del Cliente. Si el Cliente no entrega el material o no responde durante <b>60 días corridos</b>, el proyecto se considerará <b>abandonado</b>, facultando al Prestador a cerrarlo, entendiéndose consumido el anticipo, sin perjuicio del derecho a cobrar el trabajo ejecutado.',
      ),
      6 => 
      array (
        'titulo' => '7. Precio y forma de pago',
        'contenido' => 'El valor del servicio se pagará exclusivamente mediante <b>transferencia electrónica</b>, en la modalidad <b>100% al inicio</b> o <b>50% al inicio y 50% contra entrega</b>, según lo acordado. Los valores son netos; se agregará IVA cuando corresponda. <b>El anticipo no es reembolsable</b>, aún cuando el Cliente desista del proyecto. La mora en cualquier pago devengará un interés de <b>1,5% mensual</b> y facultará al Prestador a suspender los trabajos sin que ello constituya incumplimiento.',
      ),
      7 => 
      array (
        'titulo' => '8. Costos de terceros',
        'contenido' => 'El precio del servicio <b>no incluye</b> los costos de plataformas y servicios de terceros, tales como la suscripción mensual de Shopify, aplicaciones de pago, themes premium, fuentes con licencia, dominios, certificados u otros. Dichos costos son de cargo exclusivo del Cliente y se contratan directamente a su nombre.',
      ),
      8 => 
      array (
        'titulo' => '9. Responsabilidades del Cliente',
        'contenido' => 'El Cliente se obliga a entregar oportunamente el material (logo, textos, imágenes, catálogo), los accesos necesarios (dominio, pasarelas, cuentas) y las validaciones solicitadas. La calidad, veracidad y licitud de la información, imágenes y contenidos entregados son de exclusiva responsabilidad del Cliente, quien declara contar con los derechos para su uso.',
      ),
      9 => 
      array (
        'titulo' => '10. Propiedad intelectual y entrega',
        'contenido' => 'Una vez <b>pagado el 100%</b> del servicio, la configuración de la tienda quedará a nombre del Cliente en su propia cuenta Shopify. Hasta el pago total, los entregables permanecen bajo control del Prestador. Los themes y aplicaciones de terceros mantienen las licencias de sus proveedores. El Prestador conserva la titularidad de sus métodos, plantillas y componentes propios, otorgando al Cliente una licencia de uso para su tienda. El Prestador podrá incluir el proyecto en su portafolio, salvo objeción escrita del Cliente.',
      ),
      10 => 
      array (
        'titulo' => '11. Limitación de responsabilidad',
        'contenido' => 'El Prestador presta un servicio de diseño y configuración; <b>no garantiza resultados comerciales, ventas, tráfico ni posicionamiento (SEO)</b>. El Prestador no será responsable por caídas, errores, suspensiones o cambios de Shopify, pasarelas de pago, empresas de despacho u otros servicios de terceros. En todo caso, la responsabilidad total del Prestador se limita al <b>monto efectivamente pagado por el Cliente</b> por el servicio, excluyéndose el lucro cesante y los daños indirectos. Mantener respaldos de la tienda tras la entrega es responsabilidad del Cliente.',
      ),
      11 => 
      array (
        'titulo' => '12. Garantía',
        'contenido' => 'El Prestador corregirá sin costo los <b>errores funcionales imputables al desarrollo</b> que se reporten dentro de los <b>15 días corridos</b> siguientes a la entrega. Esta garantía no cubre cambios de alcance, nuevos requerimientos, ni fallas de plataformas o servicios de terceros. El soporte posterior a la garantía se contrata mediante un <b>plan de mantención</b> separado.',
      ),
      12 => 
      array (
        'titulo' => '13. Confidencialidad y datos personales',
        'contenido' => 'Ambas partes mantendrán confidencialidad sobre la información comercial, técnica y personal compartida, por un plazo mínimo de <b>5 años</b> desde el término del contrato, conforme a la Ley N° 19.628. El Cliente es el responsable del tratamiento de los datos personales de sus compradores.',
      ),
      13 => 
      array (
        'titulo' => '14. No captación de personal',
        'contenido' => 'Durante la vigencia del contrato y por <b>12 meses</b> posteriores a su término, el Cliente se obliga a no contratar ni vincular, directa o indirectamente, a colaboradores o profesionales del Prestador que hayan participado en el proyecto, sin autorización escrita del Prestador.',
      ),
      14 => 
      array (
        'titulo' => '15. Naturaleza del vínculo',
        'contenido' => 'El presente es un contrato de prestación de servicios. No existe vínculo de subordinación ni dependencia entre las partes, ni relación laboral alguna; cada parte asume sus propias obligaciones tributarias y previsionales.',
      ),
      15 => 
      array (
        'titulo' => '16. Fuerza mayor',
        'contenido' => 'Ninguna de las partes será responsable por el incumplimiento o retraso de sus obligaciones cuando ello derive de caso fortuito o fuerza mayor, conforme al artículo 45 del Código Civil.',
      ),
      16 => 
      array (
        'titulo' => '17. Modificaciones, cesión y comunicaciones',
        'contenido' => 'Toda modificación a este contrato deberá constar por escrito y ser aceptada por ambas partes. Ninguna parte podrá ceder este contrato sin consentimiento escrito de la otra. Las comunicaciones oficiales se efectuarán por correo electrónico a las direcciones registradas en el onboarding.',
      ),
      17 => 
      array (
        'titulo' => '18. Vigencia, término y jurisdicción',
        'contenido' => 'El contrato rige desde su aceptación y hasta la entrega conforme del servicio y el pago total. Cualquiera de las partes podrá ponerle término por incumplimiento grave de la otra, previa notificación escrita y plazo de 10 días hábiles para subsanar. Toda controversia se resolverá de buena fe entre las partes y, en su defecto, ante los <b>tribunales ordinarios de justicia de la comuna de Viña del Mar</b>.',
      ),
      18 => 
      array (
        'titulo' => '19. Aceptación',
        'contenido' => 'La aceptación electrónica de este contrato a través del portal de onboarding, registrando nombre, fecha e IP, constituye manifestación de voluntad válida y vinculante conforme a la <b>Ley N° 19.799</b> sobre documentos electrónicos y firma electrónica.',
      ),
    ),
    'cierre' => 'Al marcar la casilla de aceptación e ingresar tu nombre, declaras haber leído, comprendido y aceptado íntegramente las cláusulas de este contrato, manifestando tu voluntad de obligarte conforme a sus términos.',
    'activo' => true,
  ),
);

        foreach ($plantillas as $d) {
            AgenciaContratoPlantilla::updateOrCreate(["slug" => $d["slug"]], $d);
        }
    }
}
