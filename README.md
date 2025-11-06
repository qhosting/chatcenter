# 🐳 ChatCenter para EasyPanel

## 🚀 Instalación Rápida en EasyPanel

ChatCenter containerizado y optimizado para EasyPanel con instalación automática.

### 📋 Archivos Incluidos

- **`Dockerfile`** - Imagen Docker optimizada para ChatCenter
- **`docker-compose.yml`** - Configuración de servicios
- **`init-script.sh`** - Script de instalación automática
- **`apache.conf`** - Configuración de Apache
- **`nginx.conf`** - Configuración de Nginx (opcional)
- **`.env.template`** - Plantilla de variables de entorno
- **`GUIA_INSTALACION_EASYPANEL.md`** - Guía completa de instalación

### ⚡ Instalación Rápida

#### 1. Subir Archivos
Sube todos los archivos a tu proyecto en EasyPanel.

#### 2. Configurar Variables de Entorno
```bash
# Base de datos (obligatorio)
DB_HOST=cloudmx_whatscloud-db
DB_DATABASE=whatscloud-db
DB_USER=mariadb
DB_PASSWORD=c6873d0542d664ca4ff1
DB_PORT=3306

# Aplicación
APP_DOMAIN=whatscloud.site
APP_URL=https://whatscloud.site
APP_ENV=production

# Admin (opcional - se generan automáticamente)
ADMIN_EMAIL=admin@whatscloud.site
ADMIN_TITLE=ChatCenter
ADMIN_SYMBOL=💬
ADMIN_COLOR=#075e54

# API (opcional)
META_API_TOKEN=your_token_here
OPENAI_API_KEY=your_key_here
```

#### 3. Ejecutar Deploy
En EasyPanel, hacer click en "Deploy".

#### 4. Verificar Instalación
- Acceder a `https://whatscloud.site`
- Revisar logs para credenciales generadas

### 🔧 Características

✅ **Instalación Automática** - Detecta variables y configura todo automáticamente  
✅ **Base de Datos Externa** - Conecta a tu MariaDB de EasyPanel  
✅ **SSL Integrado** - Compatible con SSL de EasyPanel  
✅ **Logs Detallados** - Monitoreo completo de instalación  
✅ **Credenciales Seguras** - Genera contraseñas y API keys automáticamente  
✅ **Backup Ready** - Volúmenes para persistencia de datos  
✅ **Health Checks** - Monitoreo de estado del servicio  

### 📊 Componentes del Sistema

- **Chat Center** - Interfaz tipo WhatsApp
- **E-commerce** - Sistema de pedidos para restaurantes
- **IA Integration** - ChatGPT para automatización
- **WhatsApp API** - Integración completa con WhatsApp Business
- **CMS** - Sistema de gestión de contenido
- **API REST** - Backend robusto en PHP

### 🗄️ Base de Datos

El sistema usa tu contenedor MariaDB existente:
- **Host**: `cloudmx_whatscloud-db`
- **Database**: `whatscloud-db`
- **User**: `mariadb`
- **Puerto**: `3306`

### 📁 Estructura de Volúmenes

```bash
/var/www/html/cms/uploads     # Archivos subidos
/var/www/html/logs           # Logs del sistema
/var/www/html/api/logs       # Logs de la API
```

### 🔍 Troubleshooting

**Error de conexión a BD:**
```bash
# Verificar credenciales en variables de entorno
# Verificar que MariaDB esté corriendo
```

**Instalación no completa:**
```bash
# Revisar logs en EasyPanel
# Buscar mensajes de instalación
```

**Acceso al dashboard:**
```bash
# Credenciales se generan automáticamente
# Ver archivo install-credentials.txt
```

### 📋 Variables de Entorno Importantes

| Variable | Descripción | Requerido |
|----------|-------------|-----------|
| `DB_HOST` | Host de MariaDB | ✅ Sí |
| `DB_DATABASE` | Nombre de la BD | ✅ Sí |
| `DB_USER` | Usuario BD | ✅ Sí |
| `DB_PASSWORD` | Contraseña BD | ✅ Sí |
| `ADMIN_EMAIL` | Email administrador | ❌ No (genera) |
| `API_KEY` | API Key | ❌ No (genera) |
| `META_API_TOKEN` | Token WhatsApp | ❌ No |
| `OPENAI_API_KEY` | Token OpenAI | ❌ No |

### 🎯 URLs de Acceso

- **Dashboard**: `https://whatscloud.site`
- **API**: `https://whatscloud.site/api/`
- **Status**: `https://whatscloud.site/api/status`

### 📞 Soporte

Para problemas:
1. Revisar logs en EasyPanel
2. Verificar variables de entorno
3. Consultar `GUIA_INSTALACION_EASYPANEL.md`

### 🏗️ Arquitectura

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   EasyPanel     │────│  ChatCenter App  │────│   MariaDB       │
│                 │    │  (PHP/Apache)    │    │   Container     │
│  - Domain       │    │                  │    │                 │
│  - SSL          │    │  - Auto Install  │    │  - External     │
│  - Docker       │    │  - API REST      │    │  - Port 3306    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌────────▼────────┐              │
         │              │   Volumes       │              │
         └──────────────│  - uploads      │──────────────┘
                        │  - logs         │
                        └─────────────────┘
```

---

**🎉 ¡Listo para usar!** ChatCenter se instala automáticamente y está listo en minutos.

**Versión**: 1.0  
**Fecha**: 2025-11-06  
**Autor**: MiniMax Agent