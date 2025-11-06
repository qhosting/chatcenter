#!/bin/bash

# Script de inicialización automática para ChatCenter
# Lee variables de entorno y ejecuta instalación automática

set -e

echo "🚀 ChatCenter Docker - Iniciando configuración automática..."

# Función para generar contraseña segura si no se proporciona
generate_secure_password() {
    openssl rand -base64 32
}

# Función para generar API Key segura
generate_api_key() {
    openssl rand -hex 32
}

# Variables con valores por defecto
: "${DB_HOST:=cloudmx_whatscloud-db}"
: "${DB_DATABASE:=whatscloud-db}"
: "${DB_USER:=mariadb}"
: "${DB_PASSWORD:=c6873d0542d664ca4ff1}"
: "${DB_PORT:=3306}"

: "${APP_DOMAIN:=localhost}"
: "${APP_URL:=http://localhost}"
: "${APP_ENV:=production}"
: "${API_KEY:=$(generate_api_key)}"

# Configuración del administrador (desde variables de entorno)
: "${ADMIN_EMAIL:=admin@${APP_DOMAIN}}"
: "${ADMIN_PASSWORD:=$(generate_secure_password)}"
: "${ADMIN_TITLE:=ChatCenter}"
: "${ADMIN_SYMBOL:=💬}"
: "${ADMIN_FONT:=}"
: "${ADMIN_COLOR:=#075e54}"
: "${ADMIN_BACKGROUND:=}"

echo "📋 Configuración detectada:"
echo "   Database: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}"
echo "   API Key: ${API_KEY:0:16}..."
echo "   Admin Email: ${ADMIN_EMAIL}"
echo "   Admin Title: ${ADMIN_TITLE}"
echo "   Environment: ${APP_ENV}"

# Crear archivo de configuración temporal
cat > /tmp/env_config.php << EOL
<?php
// Configuración temporal para la instalación
define('TEMP_DB_HOST', '${DB_HOST}');
define('TEMP_DB_NAME', '${DB_DATABASE}');
define('TEMP_DB_USER', '${DB_USER}');
define('TEMP_DB_PASS', '${DB_PASSWORD}');
define('TEMP_DB_PORT', '${DB_PORT}');
define('TEMP_API_KEY', '${API_KEY}');
define('TEMP_ADMIN_EMAIL', '${ADMIN_EMAIL}');
define('TEMP_ADMIN_PASSWORD', '${ADMIN_PASSWORD}');
define('TEMP_ADMIN_TITLE', '${ADMIN_TITLE}');
define('TEMP_ADMIN_SYMBOL', '${ADMIN_SYMBOL}');
define('TEMP_ADMIN_FONT', '${ADMIN_FONT}');
define('TEMP_ADMIN_COLOR', '${ADMIN_COLOR}');
define('TEMP_ADMIN_BACKGROUND', '${ADMIN_BACKGROUND}');
?>
EOL

echo "🔧 Aplicando configuración de base de datos..."

# Actualizar connection.php con variables de entorno
sed -i "s/database.*=.*\"[^\"]*\"/database => \"${DB_DATABASE}\"/g" /var/www/html/api/models/connection.php
sed -i "s/user.*=.*\"[^\"]*\"/user => \"${DB_USER}\"/g" /var/www/html/api/models/connection.php  
sed -i "s/pass.*=.*\"[^\"]*\"/pass => \"${DB_PASSWORD}\"/g" /var/www/html/api/models/connection.php

# Actualizar API Key
sed -i "s/return \"[^\"]*\"/return \"${API_KEY}\"/g" /var/www/html/api/models/connection.php

echo "🔗 Configurando conexión a base de datos..."

# Probar conexión a la base de datos
MAX_RETRIES=30
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -e "USE ${DB_DATABASE};" 2>/dev/null; then
        echo "✅ Conexión a base de datos establecida"
        break
    else
        echo "⏳ Esperando base de datos... (intento $((RETRY_COUNT + 1))/$MAX_RETRIES)"
        sleep 5
        RETRY_COUNT=$((RETRY_COUNT + 1))
    fi
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "❌ No se pudo conectar a la base de datos después de $MAX_RETRIES intentos"
    echo "   Verificar configuración:"
    echo "   - Host: ${DB_HOST}"
    echo "   - Database: ${DB_DATABASE}"
    echo "   - User: ${DB_USER}"
    echo "   - Port: ${DB_PORT}"
    exit 1
fi

echo "🗄️ Verificando esquema de base de datos..."

# Verificar si ya existe el esquema completo
TABLES_CHECK=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" -e "SHOW TABLES;" 2>/dev/null | wc -l)

if [ $TABLES_CHECK -gt 5 ]; then
    echo "✅ Esquema de base de datos detectado"
    
    # Verificar si ya está instalado el sistema
    ADMIN_COUNT=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" -e "SELECT COUNT(*) FROM admins;" 2>/dev/null | tail -1)
    
    if [ "$ADMIN_COUNT" -gt 0 ]; then
        echo "✅ Sistema ChatCenter ya instalado"
        echo "🎉 Acceso disponible en: ${APP_URL}"
        echo "👤 Admin: ${ADMIN_EMAIL}"
        echo "🔑 Password: ${ADMIN_PASSWORD}"
        
        # Guardar credenciales en archivo
        echo "ChatCenter - Credenciales de acceso" > /var/www/html/install-credentials.txt
        echo "URL: ${APP_URL}" >> /var/www/html/install-credentials.txt
        echo "Email: ${ADMIN_EMAIL}" >> /var/www/html/install-credentials.txt
        echo "Password: ${ADMIN_PASSWORD}" >> /var/www/html/install-credentials.txt
        echo "API Key: ${API_KEY}" >> /var/www/html/install-credentials.txt
        echo "Fecha: $(date)" >> /var/www/html/install-credentials.txt
        
    else
        echo "⚠️ Esquema existe pero sistema no está instalado"
        echo "📦 Ejecutando instalación automática..."
        
        # Ejecutar instalación
        cd /var/www/html/cms
        php -r "
        \$_POST['email_admin'] = '${ADMIN_EMAIL}';
        \$_POST['password_admin'] = '${ADMIN_PASSWORD}';
        \$_POST['title_admin'] = '${ADMIN_TITLE}';
        \$_POST['symbol_admin'] = '${ADMIN_SYMBOL}';
        \$_POST['font_admin'] = '${ADMIN_FONT}';
        \$_POST['color_admin'] = '${ADMIN_COLOR}';
        \$_POST['back_admin'] = '${ADMIN_BACKGROUND}';
        include 'controllers/install.controller.php';
        \$install = new InstallController();
        \$install->install();
        "
        
        echo "✅ Instalación automática completada"
    fi
else
    echo "📦 Ejecutando instalación completa del sistema..."
    
    # Importar esquema SQL si está disponible
    if [ -f "/var/www/html/chatcenter.sql" ]; then
        echo "📄 Importando esquema SQL..."
        mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" < /var/www/html/chatcenter.sql
    fi
    
    # Ejecutar instalación
    cd /var/www/html/cms
    php -r "
    \$_POST['email_admin'] = '${ADMIN_EMAIL}';
    \$_POST['password_admin'] = '${ADMIN_PASSWORD}';
    \$_POST['title_admin'] = '${ADMIN_TITLE}';
    \$_POST['symbol_admin'] = '${ADMIN_SYMBOL}';
    \$_POST['font_admin'] = '${ADMIN_FONT}';
    \$_POST['color_admin'] = '${ADMIN_COLOR}';
    \$_POST['back_admin'] = '${ADMIN_BACKGROUND}';
    include 'controllers/install.controller.php';
    \$install = new InstallController();
    \$install->install();
    "
    
    echo "✅ Instalación automática completada"
fi

echo "🔐 Configurando variables de entorno PHP..."

# Crear archivo .env para la aplicación
cat > /var/www/html/.env << EOL
# ChatCenter Environment Configuration
DB_HOST=${DB_HOST}
DB_DATABASE=${DB_DATABASE}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_PORT=${DB_PORT}

API_KEY=${API_KEY}
APP_DOMAIN=${APP_DOMAIN}
APP_URL=${APP_URL}
APP_ENV=${APP_ENV}

META_API_TOKEN=${META_API_TOKEN:-}
META_PHONE_NUMBER_ID=${META_PHONE_NUMBER_ID:-}
META_BUSINESS_ID=${META_BUSINESS_ID:-}
META_WEBHOOK_TOKEN=${META_WEBHOOK_TOKEN:-}

OPENAI_API_KEY=${OPENAI_API_KEY:-}
OPENAI_MODEL=${OPENAI_MODEL:-gpt-3.5-turbo}

SESSION_LIFETIME=${SESSION_LIFETIME:-86400}
MAX_UPLOAD_SIZE=${MAX_UPLOAD_SIZE:-50}
ALLOWED_EXTENSIONS=${ALLOWED_EXTENSIONS:-jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx}

ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_TITLE=${ADMIN_TITLE}
ADMIN_SYMBOL=${ADMIN_SYMBOL}
ADMIN_COLOR=${ADMIN_COLOR}
EOL

echo "🛡️ Configurando seguridad..."

# Deshabilitar installer después de la instalación
rm -f /var/www/html/cms/views/pages/install/install.php
touch /var/www/html/cms/views/pages/install/install.php
echo "<?php echo 'Sistema ya instalado. Contacte al administrador.'; ?>" > /var/www/html/cms/views/pages/install/install.php

# Configurar permisos finales
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/cms/uploads
chmod -R 777 /var/www/html/logs

echo "📋 Resumen de instalación:"
echo "   URL: ${APP_URL}"
echo "   Admin: ${ADMIN_EMAIL}"
echo "   Password: ${ADMIN_PASSWORD}"
echo "   API Key: ${API_KEY:0:16}..."
echo "   Database: ${DB_DATABASE} @ ${DB_HOST}:${DB_PORT}"

echo "✅ ChatCenter listo para usar!"
echo "🚀 Iniciando servidor web..."

# Ejecutar comando original
exec "$@"