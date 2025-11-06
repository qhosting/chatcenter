# 🚀 Guía de Instalación ChatCenter en EasyPanel

## 📋 Requisitos Previos

### EasyPanel Configurado
- ✅ EasyPanel instalado y funcionando
- ✅ Contenedor MariaDB configurado (ya tienes `cloudmx_whatscloud-db:3306/whatscloud-db`)
- ✅ Dominio configurado en EasyPanel

### Variables de Entorno Requeridas
- ✅ **DB_HOST**: `cloudmx_whatscloud-db` (tu contenedor MariaDB existente)
- ✅ **DB_DATABASE**: `whatscloud-db` (tu base de datos)
- ✅ **DB_USER**: `mariadb`
- ✅ **DB_PASSWORD**: `c6873d0542d664ca4ff1`
- ✅ **DB_PORT**: `3306`

## 🛠️ Instalación Paso a Paso

### Paso 1: Subir Archivos a EasyPanel

1. **Crear Proyecto en EasyPanel:**
   - Ir a "Proyectos" → "Nuevo Proyecto"
   - Seleccionar "Aplicación Docker"
   - Nombre: `chatcenter`

2. **Subir Dockerfile y archivos:**
   ```bash
   # Subir estos archivos al directorio del proyecto:
   - Dockerfile
   - docker-compose.yml
   - .env.template
   - nginx.conf
   ```

### Paso 2: Configurar Variables de Entorno

1. **En EasyPanel**, ir a la sección "Variables de Entorno"
2. **Configurar las siguientes variables:**

#### 🗄️ Database Configuration
```
DB_HOST=cloudmx_whatscloud-db
DB_DATABASE=whatscloud-db
DB_USER=mariadb
DB_PASSWORD=c6873d0542d664ca4ff1
DB_PORT=3306
```

#### 🌐 Application Configuration
```
APP_DOMAIN=whatscloud.site
APP_URL=https://whatscloud.site
APP_ENV=production
APP_DEBUG=false
```

#### 👤 Admin Configuration (Opcional - se generan automáticamente)
```
ADMIN_EMAIL=admin@whatscloud.site
ADMIN_TITLE=ChatCenter
ADMIN_SYMBOL=💬
ADMIN_COLOR=#075e54
```

#### 🔐 Meta/WhatsApp API (Opcional)
```
META_API_TOKEN=your_meta_api_token_here
META_PHONE_NUMBER_ID=your_phone_number_id_here
META_BUSINESS_ID=your_business_id_here
META_WEBHOOK_TOKEN=your_webhook_token_here
```

#### 🤖 OpenAI Configuration (Opcional)
```
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-3.5-turbo
```

### Paso 3: Configurar Docker Compose

**En EasyPanel**, crear un nuevo servicio Docker con la siguiente configuración:

```yaml
version: '3.8'
services:
  chatcenter:
    build: .
    container_name: chatcenter-app
    ports:
      - "8080:80"  # Usar puerto 8080 para evitar conflictos
    environment:
      - DB_HOST=cloudmx_whatscloud-db
      - DB_DATABASE=whatscloud-db
      - DB_USER=mariadb
      - DB_PASSWORD=c6873d0542d664ca4ff1
      - DB_PORT=3306
      - APP_DOMAIN=whatscloud.site
      - APP_URL=https://whatscloud.site
      - APP_ENV=production
      - ADMIN_EMAIL=admin@whatscloud.site
      - ADMIN_TITLE=ChatCenter
      - ADMIN_SYMBOL=💬
      - ADMIN_COLOR=#075e54
    volumes:
      - chatcenter_uploads:/var/www/html/cms/uploads
      - chatcenter_logs:/var/www/html/logs
    depends_on:
      - db
    networks:
      - chatcenter-network

volumes:
  chatcenter_uploads:
  chatcenter_logs:

networks:
  chatcenter-network:
    driver: bridge
```

### Paso 4: Configurar Dominio y SSL

1. **Configurar Dominio:**
   - Ir a "Dominios" → Agregar dominio
   - Dominio: `whatscloud.site`
   - Tipo: "Proxy" o "Docker"
   - Puerto: `8080`

2. **SSL Certificate:**
   - Activar SSL en EasyPanel
   - Configurar certificados automáticos

### Paso 5: Desplegar y Verificar

1. **Ejecutar Deploy:**
   ```bash
   # En EasyPanel, hacer click en "Deploy" o "Start"
   ```

2. **Monitorear Logs:**
   - Ir a "Logs" para ver el progreso de instalación
   - Buscar mensajes: "✅ Conexión a base de datos establecida"
   - Buscar: "✅ ChatCenter listo para usar!"

3. **Verificar Instalación:**
   - Acceder a `https://whatscloud.site`
   - Verificar que aparece el dashboard o instalación automática

## 🔧 Instalación Automática

### ¿Qué hace el Instalador?

El script `init-script.sh` ejecuta automáticamente:

1. **🔍 Detección de Variables**
   - Lee todas las variables de entorno configuradas
   - Genera API Key segura si no se proporciona
   - Genera contraseña de administrador si no se proporciona

2. **🗄️ Configuración de Base de Datos**
   - Actualiza `api/models/connection.php` con las variables
   - Prueba conexión a MariaDB
   - Espera hasta 5 minutos si la BD no está disponible

3. **📦 Instalación del Sistema**
   - Crea tablas necesarias si no existen
   - Ejecuta instalación automática del dashboard
   - Configura usuario administrador
   - Crea página de inicio y módulos básicos

4. **🔐 Seguridad y Configuración**
   - Deshabilita instalador después de completar
   - Configura permisos de archivos
   - Crea archivo `.env` con configuraciones

5. **📋 Generación de Credenciales**
   - Crea archivo `/var/www/html/install-credentials.txt`
   - Guarda las credenciales de acceso
   - Genera API Key segura

## 📊 Credenciales y Acceso

### Después de la Instalación

**Las credenciales se generan automáticamente y se guardan en:**
- 📄 **Archivo**: `install-credentials.txt` dentro del contenedor
- 📧 **Admin Email**: Configurado en `ADMIN_EMAIL`
- 🔑 **Password**: Generado automáticamente (visible en logs)

### Ejemplo de Credenciales Generadas
```
URL: https://whatscloud.site
Email: admin@whatscloud.site
Password: K9mN3pQrS8tVy2Xz5Bn7Cs3D4eF6GhJkL8mN
API Key: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
```

## 🔍 Resolución de Problemas

### Problemas Comunes

#### 1. Error de Conexión a Base de Datos
```
❌ No se pudo conectar a la base de datos
```
**Solución:**
- Verificar que el contenedor MariaDB esté corriendo
- Verificar credenciales en variables de entorno
- Verificar conectividad de red entre contenedores

#### 2. Instalación No Se Completa
```
⏳ Esperando base de datos... (intento 1/30)
```
**Solución:**
- Aumentar tiempo de espera en el script
- Verificar que el puerto 3306 esté accesible
- Revisar logs detallados en EasyPanel

#### 3. Error 404 en API
```
404 Not Found
```
**Solución:**
- Verificar configuración de Apache
- Verificar que `/api/` esté accesible
- Revisar configuración de nginx/apache

#### 4. Permisos de Archivos
```
Permission denied
```
**Solución:**
```bash
chown -R www-data:www-data /var/www/html
chmod -R 777 /var/www/html/cms/uploads
```

### Logs de Depuración

**En EasyPanel, revisar logs de:**
- 🐳 **Docker Container**: Logs del contenedor chatcenter
- 🌐 **Nginx/Apache**: Logs de acceso web
- 📋 **PHP**: Logs de errores PHP

## 🔄 Actualizaciones y Mantenimiento

### Backup de Datos
```bash
# Hacer backup de uploads
tar -czf chatcenter-uploads-backup.tar.gz /var/www/html/cms/uploads

# Backup de logs
tar -czf chatcenter-logs-backup.tar.gz /var/www/html/logs
```

### Actualización
1. Subir nueva versión del código
2. Reconstruir contenedor Docker
3. Las configuraciones se mantienen en variables de entorno

### Monitoreo
- Verificar logs regularmente
- Monitorear uso de disco en uploads
- Verificar conectividad con MariaDB
- Monitorear uso de memoria y CPU

## 🎯 Configuración Avanzada

### SSL Personalizado
```bash
# Montar certificados en docker-compose.yml
volumes:
  - ./ssl/whatscloud.site.crt:/etc/nginx/ssl/whatscloud.site.crt:ro
  - ./ssl/whatscloud.site.key:/etc/nginx/ssl/whatscloud.site.key:ro
```

### Backup Automático
```bash
# Agregar cron job para backup automático
0 2 * * * docker exec chatcenter-app tar -czf /tmp/backup-$(date +\%Y\%m\%d).tar.gz /var/www/html/cms/uploads
```

### Monitoring
```bash
# Agregar healthcheck personalizado
healthcheck:
  test: ["CMD", "curl", "-f", "https://whatscloud.site/api/status"]
  interval: 60s
  timeout: 10s
  retries: 3
```

## ✅ Checklist Final

- [ ] ✅ Contenedor MariaDB corriendo y accesible
- [ ] ✅ Variables de entorno configuradas en EasyPanel
- [ ] ✅ Dockerfile compilado exitosamente
- [ ] ✅ Dominio configurado con SSL
- [ ] ✅ Logs muestran instalación exitosa
- [ ] ✅ Credenciales guardadas y accesibles
- [ ] ✅ Dashboard accesible en https://whatscloud.site
- [ ] ✅ API responding correctamente
- [ ] ✅ Uploads funcionando
- [ ] ✅ Backup configurado

---

**🎉 ¡ChatCenter instalado y funcionando en EasyPanel!**

Para soporte adicional, revisar los logs en EasyPanel y consultar la documentación de ChatCenter.

---

**Última actualización**: 2025-11-06  
**Versión**: ChatCenter v1.0  
**Plataforma**: EasyPanel + Docker