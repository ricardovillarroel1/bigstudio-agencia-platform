# Módulo Onboarding — Agencia BigStudio

Portal de auto-servicio donde los clientes nuevos entregan a BigStudio todo el material necesario para arrancar su proyecto (diseño Shopify, campañas Meta Ads, SEO, mantención, integraciones, etc.) a través de un wizard guiado.

---

## URLs en producción

| Vista | URL | Auth |
|---|---|---|
| Portal público del cliente | `https://onboarding.bigstudio.cl/o/{token}` | Token único de 40 chars |
| Wizard del cliente | `https://onboarding.bigstudio.cl/o/{token}/w[/{indice}]` | Mismo token |
| Cierre / Material listo | `https://onboarding.bigstudio.cl/o/{token}/completado` | Mismo token |
| Admin — lista | `https://integration-conector.bigstudio.cl/agencia/onboardings` | `auth + role:admin` |
| Admin — detalle | `.../agencia/onboardings/{id}` | Mismo |
| Admin — plantillas | `.../agencia/onboardings/plantillas` | Mismo |
| Admin — imprimir / PDF | `.../agencia/onboardings/{id}/imprimir` | Mismo |

---

## Arquitectura

```
┌──────────────────────────────────┐    ┌──────────────────────────────────┐
│  onboarding.bigstudio.cl         │    │ integration-conector.bigstudio.cl│
│  (vhost Apache, SSL Let's Encrypt)│    │  (vhost existente)               │
└──────────────┬───────────────────┘    └──────────────┬───────────────────┘
               │                                       │
               ▼                                       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  /var/www/shopify-integrator/  (Laravel 9 + Blade + Tailwind via CDN)    │
│                                                                          │
│  ┌─ Rutas públicas (cliente, sin login, token-auth):                     │
│  │     OnboardingPublicoController                                       │
│  │       · mostrar      → vista bienvenida                               │
│  │       · wizard       → wizard sección N con campos dinámicos          │
│  │       · guardar      → POST navegación + persistencia                 │
│  │       · autoguardar  → AJAX por campo, devuelve % avance              │
│  │       · subirArchivo → uploads drag & drop                            │
│  │       · eliminarArchivo / descargarArchivo                            │
│  │       · completado   → vista de cierre                                │
│  │                                                                       │
│  └─ Rutas admin (bajo /agencia/, auth + role:admin):                     │
│        AgenciaOnboardingController          → CRUD proyectos             │
│        AgenciaOnboardingPlantillaController → CRUD plantillas + duplicar │
└──────────────────────────────────────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  MariaDB shopify_integrator                                              │
│     agencia_onboarding_plantillas   (catálogo de tipos de servicio)      │
│     agencia_onboarding_proyectos    (un onboarding por cliente)          │
│     agencia_onboarding_respuestas   (datos llenados, key→valor)          │
│     agencia_onboarding_archivos     (uploads: nombre, ruta, mime, size)  │
│     agencia_onboarding_eventos      (audit log: creado, abierto, etc.)   │
└──────────────────────────────────────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  /var/www/onboarding-storage/{proyecto_id}/{seccion_key}/{archivo}       │
│  (Apache no lo sirve directo; descarga via Laravel valida token)         │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Modelos

| Modelo | Tabla | Relaciones |
|---|---|---|
| `AgenciaOnboardingPlantilla` | `agencia_onboarding_plantillas` | hasMany Proyectos |
| `AgenciaOnboardingProyecto` | `agencia_onboarding_proyectos` | belongsTo Cliente, Plantilla; hasMany Respuestas/Archivos/Eventos |
| `AgenciaOnboardingRespuesta` | `agencia_onboarding_respuestas` | belongsTo Proyecto |
| `AgenciaOnboardingArchivo` | `agencia_onboarding_archivos` | belongsTo Proyecto |
| `AgenciaOnboardingEvento` | `agencia_onboarding_eventos` | belongsTo Proyecto |

### Estructura de `secciones` (JSON en plantilla)

```json
[
  {
    "key": "identidad_visual",
    "titulo": "Identidad visual de marca",
    "subtitulo": "Logo, colores, tipografía",
    "campos": [
      {
        "key": "logo_principal",
        "tipo": "archivo_multiple",
        "label": "Logo principal (vectorial + PNG)",
        "requerido": true
      },
      {
        "key": "tono_comunicacion",
        "tipo": "select",
        "label": "Tono",
        "opciones": ["Cercano", "Premium", "Lúdico"],
        "requerido": true
      }
    ]
  }
]
```

**Tipos de campo soportados**:
- `texto` / `texto_corto` → input
- `texto_largo` → textarea (5 filas)
- `select` → dropdown con `opciones[]`
- `confirmacion` → checkbox con fondo destacado
- `archivo_unico` / `archivo_multiple` → drag & drop, hasta 50MB

---

## Mailables

| Clase | Trigger | Destinatario |
|---|---|---|
| `OnboardingInvitacionMail` | Admin clica "Enviar invitación" | Email del cliente |
| `OnboardingRecordatorioMail` | Comando cron diario detecta inactividad | Email del cliente |
| `OnboardingCompletadoMail` | Cliente marca "Material listo" | `hola@bigstudio.cl` |

Plantillas Blade en `resources/views/emails/onboarding/`. Branding BigStudio (gradiente naranja, botón CTA).

---

## Comando artisan

```bash
# Modo real (corre a las 10:00 AM diarias vía scheduler)
php artisan agencia:onboarding-recordatorios

# Modo prueba (no envía emails)
php artisan agencia:onboarding-recordatorios --dry-run

# Override umbrales
php artisan agencia:onboarding-recordatorios --dias-min=7 --intervalo-min=5
```

Filtros: proyectos en `no_iniciado` o `en_progreso`, con `email_cliente`, con `fecha_envio`, sin actualización en `--dias-min` días, sin recordatorio enviado en `--intervalo-min` días.

Programado en `app/Console/Kernel.php` → `dailyAt('10:00')`.

---

## Plantillas seed disponibles

| Slug | Nombre | Secciones | Campos | Días estimados |
|---|---|---|---|---|
| `shopify-prototipo` | Diseño Web Shopify - Prototipo | 7 | 37 | 20 |
| `meta-ads-mensual` | Gestión de Campañas Meta Ads | 7 | 28 | 7 |
| `seo-mensual` | SEO Mensual | 6 | 19 | 10 |
| `mantencion-shopify` | Mantención Mensual Shopify | 5 | 14 | 5 |

Para agregar más: usa el CRUD admin (`/agencia/onboardings/plantillas/crear`) o crea seeders en `database/seeders/`.

---

## Tokens y seguridad

- **Token público** (40 chars `Str::random(40)`) en URL del cliente. Único en BD. Se genera automáticamente en el `booted()` del modelo Proyecto.
- **Expira** según `token_expira_en` (default 60 días desde creación). Si está vencido, devuelve vista `expirado.blade.php` con HTTP 410.
- **CSRF exemption** para rutas `/o/*/w/*/autoguardar`, `/o/*/u/*/*`, `/o/*/a/*` — porque ya están autenticadas por el token único en URL.

---

## Configuración crítica

### `.env`
```
MAIL_MAILER=smtp                  # ya configurado para hola@bigstudio.cl vía Gmail
APP_URL=https://integration-conector.bigstudio.cl
```

Opcional para portal:
```
ONBOARDING_URL=https://onboarding.bigstudio.cl  # default si no se define
```
Leído desde `AgenciaOnboardingProyecto::urlPublica()`.

### Apache
- `/etc/apache2/sites-available/onboarding.bigstudio.cl.conf` → vhost HTTP
- `/etc/apache2/sites-available/onboarding.bigstudio.cl-le-ssl.conf` → vhost HTTPS (Let's Encrypt)
- Ambos sirven desde `/var/www/shopify-integrator/public/` (mismo Laravel que `integration-conector`).

### PHP
```
upload_max_filesize = 50M    # subir si se necesitan archivos más grandes
post_max_size = 60M
```
En `/etc/php/8.3/fpm/php.ini`. Recordar `systemctl reload php8.3-fpm` tras cambios.

### Storage
- `/var/www/onboarding-storage/{proyecto_id}/{seccion_key}/` — owner `www-data:www-data`, mode 755.
- Archivos servidos via Laravel (no Apache directo) para validar autorización por token.

---

## Cómo crear un onboarding desde admin

1. Entra a `/agencia/onboardings`.
2. Click en `+ Nuevo onboarding`.
3. Elige cliente, plantilla, escribe título, **email del cliente** (opcional ahora, pero necesario para mandar invitación).
4. (Opcional) Notas internas + días de validez del token.
5. Click `Crear onboarding`.
6. En el detalle del proyecto creado, click `Enviar invitación` → el cliente recibe un email con el link al wizard.

---

## Cómo agregar un nuevo tipo de servicio (plantilla)

Opción A — Por interfaz:
1. Ir a `/agencia/onboardings/plantillas/crear`.
2. Llenar nombre, tipo, días estimados.
3. En el campo "Secciones (JSON)", pegar la estructura `[{key, titulo, campos: [...]}, ...]`.
4. Guardar y activar.

Opción B — Duplicando una existente:
1. Ir a `/agencia/onboardings/plantillas`.
2. Click `Duplicar` en la plantilla más parecida.
3. Editar la copia y activarla.

---

## Mantenimiento típico

| Operación | Comando / acción |
|---|---|
| Ver logs de envíos | `tail -f /var/www/shopify-integrator/storage/logs/laravel.log` |
| Probar recordatorios sin enviar | `php artisan agencia:onboarding-recordatorios --dry-run` |
| Backup de archivos subidos | `rsync -av /var/www/onboarding-storage/ /root/backups/onboarding-files/` |
| Renovar cert SSL | Automático vía `certbot.timer`. Manual: `certbot renew --apache` |
| Verificar PM2 y Apache | `pm2 status && systemctl status apache2` |

---

## Roadmap futuro

- Google Drive sync automático de archivos (Sprint pendiente, requiere Service Account).
- Portal del cliente para ver sus contratos + informes pasados.
- Export PDF nativo (actualmente: `Imprimir → Save as PDF` desde browser).
- Webhook al completar onboarding para integraciones externas (Slack, Notion).
- Multi-idioma del wizard cliente (en/es).

---

_Documento generado el 2026-06-06 al cierre de Sprints 0-9. Stack: Laravel 9.52, PHP 8.4 (CLI) / 8.3 (FPM), MariaDB, Apache, hosted on Hostinger VPS (Ubuntu 22.04)._
