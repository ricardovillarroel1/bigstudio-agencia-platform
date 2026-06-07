<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad — Lioren Integration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, system-ui, sans-serif; background: #fff; color: #1a1a1a; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #FF8100, #FFC800); color: white; padding: 2.5rem 2rem; text-align: center; }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .container { max-width: 760px; margin: 0 auto; padding: 3rem 2rem; }
        h2 { color: #FF8100; margin: 2rem 0 0.75rem; font-size: 1.25rem; }
        h3 { margin: 1.5rem 0 0.5rem; font-size: 1.05rem; color: #333; }
        p, li { margin-bottom: 0.75rem; color: #444; }
        ul { padding-left: 1.5rem; margin-bottom: 1rem; }
        .footer { background: #f7f7f7; padding: 1.5rem 2rem; text-align: center; color: #777; font-size: 0.85rem; border-top: 1px solid #eee; }
        .footer a { color: #FF8100; text-decoration: none; }
        a { color: #FF8100; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Política de Privacidad</h1>
        <p>Lioren Integration — BigStudio</p>
    </div>
    <div class="container">
        <p><strong>Última actualización:</strong> 29 de mayo de 2026</p>

        <h2>1. Quiénes somos</h2>
        <p>Lioren Integration es una aplicación operada por <strong>BigStudio SpA</strong>, Chile, que conecta tiendas Shopify con el sistema chileno de facturación electrónica Lioren para la emisión automática de boletas y facturas de venta (DTE).</p>

        <h2>2. Qué datos recopilamos</h2>
        <p>Cuando un comerciante instala nuestra app desde la Shopify App Store, recibimos y procesamos los siguientes datos de su tienda:</p>
        <ul>
            <li><strong>Datos de la tienda:</strong> dominio Shopify, nombre de la tienda, token de acceso OAuth, locations.</li>
            <li><strong>Datos de pedidos:</strong> número de pedido, monto total, productos, montos netos e IVA, fecha de pago.</li>
            <li><strong>Datos del cliente final del pedido:</strong> nombre, RUT, email, teléfono, dirección de facturación. Estos datos son necesarios para emitir el DTE en el SII chileno vía Lioren.</li>
            <li><strong>Datos del comerciante:</strong> nombre, email de contacto, API key de Lioren, plan contratado.</li>
        </ul>

        <h2>3. Cómo usamos los datos</h2>
        <ul>
            <li>Emitir boletas y facturas electrónicas (DTE) vía la API de Lioren al SII.</li>
            <li>Enviar el DTE generado al email del cliente final del pedido.</li>
            <li>Operar el panel de control del comerciante: sincronización de productos, inventario, historial.</li>
            <li>Soporte técnico y comunicación operativa con el comerciante.</li>
        </ul>

        <h2>4. Con quién compartimos los datos</h2>
        <p>Sólo compartimos datos con los siguientes terceros, y únicamente para los fines descritos:</p>
        <ul>
            <li><strong>Lioren (www.lioren.cl):</strong> para la emisión del DTE.</li>
            <li><strong>Servicio de Impuestos Internos (SII) de Chile:</strong> a través de Lioren, conforme exige la ley tributaria chilena.</li>
            <li><strong>Proveedores de hosting y email:</strong> Hostinger (servidor) y SMTP (envío de emails transaccionales).</li>
        </ul>
        <p>No vendemos datos a terceros. No usamos los datos para publicidad.</p>

        <h2>5. Cumplimiento GDPR y derechos del cliente final</h2>
        <p>Atendemos las solicitudes obligatorias de Shopify para cumplimiento de privacidad:</p>
        <ul>
            <li><strong>Customer data request:</strong> entregamos al comerciante los datos del cliente final que tenemos almacenados.</li>
            <li><strong>Customer redact:</strong> eliminamos los datos del cliente final dentro de 30 días de la solicitud, salvo obligación legal tributaria de conservar el DTE emitido (6 años en Chile).</li>
            <li><strong>Shop redact:</strong> al desinstalar la app, eliminamos todos los datos de la tienda dentro de 48 horas.</li>
        </ul>

        <h2>6. Retención</h2>
        <p>Los DTEs emitidos (boletas y facturas) se conservan por 6 años por obligación legal del SII chileno. Otros datos se eliminan al desinstalar la app o tras solicitud de redact.</p>

        <h2>7. Seguridad</h2>
        <p>Encriptamos los tokens de acceso y la API key de Lioren en la base de datos. Toda la comunicación con la app se realiza vía HTTPS con certificados TLS válidos. Verificamos cada webhook entrante mediante firma HMAC SHA-256.</p>

        <h2>8. Contacto</h2>
        <p>Para ejercer cualquier derecho relacionado con tus datos o consultar sobre esta política:</p>
        <ul>
            <li>Email: <a href="mailto:hola@bigstudio.cl">hola@bigstudio.cl</a></li>
            <li>Sitio web: <a href="https://bigstudio.cl" target="_blank">https://bigstudio.cl</a></li>
        </ul>

        <h2>9. Cambios</h2>
        <p>Publicaremos cualquier actualización de esta política en esta misma URL. Recomendamos revisar periódicamente.</p>
    </div>
    <div class="footer">
        © 2026 BigStudio SpA — Lioren Integration · <a href="/terms">Términos de Servicio</a>
    </div>
</body>
</html>
