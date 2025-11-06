# Cloudflare Turnstile - Configuración y Uso

## 📋 Resumen

Esta implementación integra **Cloudflare Turnstile** en ChatCenter para proteger formularios críticos contra bots y spam.

## 🎯 Formularios Protegidos

- **Login de Administradores** (`/cms/views/pages/login/login.php`)
- **Recuperación de Contraseña** (Modal en login)
- **Instalación del Sistema** (`/cms/views/pages/install/install.php`)
- **API Endpoints** (Login y Registro de usuarios)

## 🔧 Configuración en EasyPanel

### 1. Variables de Entorno Requeridas

Agregar las siguientes variables de entorno en tu panel EasyPanel:

```bash
# Configuración de Cloudflare Turnstile
CLOUDFLARE_TURNSTILE_SITE_KEY=tu_site_key_aqui
CLOUDFLARE_TURNSTILE_SECRET_KEY=tu_secret_key_aqui
TURNSTILE_ENABLED=true
```

### 2. Obtener Credenciales de Cloudflare

1. Ve a [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Selecciona tu dominio
3. Ve a **Security > Turnstile**
4. Crea un nuevo sitio o selecciona uno existente
5. Copia las **Site Key** y **Secret Key**

### 3. Configurar Dominios en Cloudflare

En la configuración de Turnstile, agrega tu dominio:
- `tudominio.com`
- `www.tudominio.com`

## 🛠️ Implementación Técnica

### Archivos Modificados/Creados

#### Backend
- `api/models/turnstile.model.php` - Modelo principal para Turnstile
- `api/controllers/post.controller.php` - Validación en login/register
- `cms/controllers/admins.controller.php` - Validación en CMS
- `cms/controllers/install.controller.php` - Validación en instalación

#### Frontend
- `cms/views/assets/js/integrations/turnstile.js` - JavaScript de integración
- `cms/views/template.php` - Inclusión del script
- `cms/views/pages/login/login.php` - Widget en formulario de login
- `cms/views/pages/install/install.php` - Widget en formulario de instalación

### Variables de Entorno

```bash
# En .env.example y .env
URL_DATABASE=mysql://mariadb:password@cloudmx_whatscloud-db:3306/whatscloud-db
CLOUDFLARE_TURNSTILE_SITE_KEY=0x4AAAAAAAChNiVV6M5WjjPU
CLOUDFLARE_TURNSTILE_SECRET_KEY=0x4AAAAAAAChNiVV6M5WjjPV
TURNSTILE_ENABLED=true
```

## 🎮 Funcionamiento

### Flujo de Validación

1. **Usuario llena formulario** → Se muestra widget Turnstile
2. **Usuario completa CAPTCHA** → Cloudflare genera token
3. **JavaScript captura token** → Se añade al campo `g-recaptcha-response`
4. **Formulario se envía** → Token incluido en POST
5. **Backend valida token** → Llamada a Cloudflare API
6. **Resultado** → Aceptar/rechazar request

### Configuración de Fallback

Si las claves de Turnstile no están configuradas o están deshabilitadas (`TURNSTILE_ENABLED=false`), el sistema funciona normalmente sin validación.

## 🔒 Seguridad

### Tokens de Validación

- **Duración**: Tokens expiran después de 5 minutos
- **IP Binding**: Tokens incluyen IP del usuario
- **Single Use**: Cada token se valida solo una vez

### Validación del Lado Servidor

```php
// Ejemplo de uso en controladores
$turnstile_token = $_POST["g-recaptcha-response"] ?? "";
$turnstile_verification = TurnstileModel::verifyToken($turnstile_token);

if (!$turnstile_verification['success'] && !$turnstile_verification['disabled']) {
    // Rechazar request
    return;
}
```

## 🎨 Personalización

### Temas Disponibles

Los widgets Turnstile soportan dos temas:
- `light` (por defecto)
- `dark`

### Idiomas Soportados

- `es` - Español (por defecto)
- `en` - Inglés
- `auto` - Detección automática

### Personalizar Widget

```php
echo TurnstileModel::renderTurnstile('form-id', [
    'theme' => 'light',
    'language' => 'es'
]);
```

## 🧪 Testing

### Para Desarrollo

1. **Desactivar Turnstile**:
   ```bash
   TURNSTILE_ENABLED=false
   ```

2. **Usar claves de prueba** (opcional):
   ```bash
   CLOUDFLARE_TURNSTILE_SITE_KEY=0x4AAAAAAAChNiVV6M5WjjPU
   CLOUDFLARE_TURNSTILE_SECRET_KEY=0x4AAAAAAAChNiVV6M5WjjPV
   ```

### Test de Validación

Para probar que la validación funciona:
1. Llena un formulario con Turnstile
2. Completa el CAPTCHA
3. Envía el formulario
4. Verifica en logs que se llama a la API de Cloudflare

## 📊 Logs y Debugging

### Logs de Cloudflare

1. Ve a **Security > Turnstile** en Cloudflare Dashboard
2. Revisa las **Analytics** para ver estadísticas
3. Verifica **Successful/Failed verifications**

### Logs de la Aplicación

Los errores de Turnstile se registran en los logs PHP. Revisa:
- `verifyToken()` errors
- HTTP response codes
- Validation failures

## 🔄 Actualizaciones

### Cambiar Claves

1. Actualiza las variables de entorno en EasyPanel
2. Reinicia el contenedor web
3. Limpia caché del navegador

### Configurar Nuevos Dominios

1. Ve a Cloudflare Dashboard
2. Añade el nuevo dominio a Turnstile settings
3. Actualiza Site Key si es necesario

## 🚨 Troubleshooting

### Widget no se muestra

- ✅ Verificar `TURNSTILE_ENABLED=true`
- ✅ Verificar `CLOUDFLARE_TURNSTILE_SITE_KEY` válida
- ✅ Verificar que el dominio está registrado en Cloudflare
- ✅ Verificar consola JavaScript por errores

### Validación siempre falla

- ✅ Verificar `CLOUDFLARE_TURNSTILE_SECRET_KEY` válida
- ✅ Verificar conectividad con Cloudflare (`curl https://challenges.cloudflare.com`)
- ✅ Verificar logs PHP por errores específicos
- ✅ Verificar IP real en logs de Cloudflare

### Formulario no se envía

- ✅ Verificar que el campo `g-recaptcha-response` existe
- ✅ Verificar JavaScript de integración cargado
- ✅ Verificar consola JavaScript por errores
- ✅ Verificar que el token no esté vacío

## 📈 Mejores Prácticas

1. **Monitoreo**: Revisar regularmente las estadísticas de Cloudflare
2. **Claves**: Mantener las claves secretas seguras
3. **Dominios**: Registrar todos los dominios en Cloudflare
4. **Logs**: Monitorear logs de validación
5. **Performance**: Los tokens se validan lado servidor para mejor seguridad

---

## 📞 Soporte

Para soporte técnico:
- **Documentación Cloudflare**: [developers.cloudflare.com/turnstile](https://developers.cloudflare.com/turnstile)
- **Dashboard Cloudflare**: [dash.cloudflare.com](https://dash.cloudflare.com)
- **Logs de ChatCenter**: Revisar logs PHP y console JavaScript
