#!/bin/bash
set -e

# --- Helper Functions ---
generate_secure_password() {
    openssl rand -base64 16
}

generate_api_key() {
    openssl rand -hex 32
}

# --- Main Functions ---

# 1. Set up and validate environment variables
setup_variables() {
    echo "1. ⚙️  Validating environment variables..."
    : "${DB_HOST:?Error: DB_HOST is not set}"
    : "${DB_DATABASE:?Error: DB_DATABASE is not set}"
    : "${DB_USER:?Error: DB_USER is not set}"
    : "${DB_PASSWORD:?Error: DB_PASSWORD is not set}"
    : "${DB_PORT:=3306}"

    : "${APP_DOMAIN:=localhost}"
    : "${APP_URL:=http://localhost}"
    : "${APP_ENV:=production}"
    : "${API_KEY:=$(generate_api_key)}"

    : "${ADMIN_EMAIL:=admin@${APP_DOMAIN}}"
    : "${ADMIN_PASSWORD:=$(generate_secure_password)}"
    : "${ADMIN_TITLE:=ChatCenter}"
    : "${ADMIN_SYMBOL:=💬}"
    : "${ADMIN_FONT:=}"
    : "${ADMIN_COLOR:=#075e54}"
    : "${ADMIN_BACKGROUND:=}"

    echo "   ✅ Variables are set."
}

# 2. Wait for the database to be ready
wait_for_db() {
    echo "2. ⏳ Waiting for database connection..."
    MAX_RETRIES=30
    RETRY_COUNT=0
    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        if mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -e "USE ${DB_DATABASE};" &>/dev/null; then
            echo "   ✅ Database connection successful."
            return 0
        fi
        RETRY_COUNT=$((RETRY_COUNT + 1))
        echo "   ... retrying in 5 seconds (${RETRY_COUNT}/${MAX_RETRIES})"
        sleep 5
    done
    echo "   ❌ Error: Could not connect to the database after $MAX_RETRIES attempts."
    exit 1
}

# 3. Create the .env file for the application
configure_environment() {
    echo "3. 📄 Creating .env file..."
    # Create .env file for the PHP application
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
    echo "   ✅ .env file created."
}

# 4. Run the ChatCenter installer if needed
run_installation() {
    echo "4. 🚀 Checking if installation is required..."
    ADMIN_COUNT=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" -ss -e "SELECT COUNT(*) FROM admins;" 2>/dev/null || echo 0)

    if [ "$ADMIN_COUNT" -gt 0 ]; then
        echo "   ✅ ChatCenter is already installed."
        return
    fi

    echo "   📦 New installation detected. Running installer..."

    # Import database schema if tables don't exist
    TABLE_COUNT=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" -ss -e "SHOW TABLES;" 2>/dev/null | wc -l)
    if [ "$TABLE_COUNT" -eq 0 ] && [ -f "/var/www/html/chatcenter.sql" ]; then
        echo "   ... Importing database schema from chatcenter.sql"
        mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" < /var/www/html/chatcenter.sql
    fi

    # Execute PHP installer
    echo "   ... Running PHP install script."
    cd /var/www/html/cms
    php -r "
    \$_POST['email_admin'] = getenv('ADMIN_EMAIL');
    \$_POST['password_admin'] = getenv('ADMIN_PASSWORD');
    \$_POST['title_admin'] = getenv('ADMIN_TITLE');
    \$_POST['symbol_admin'] = getenv('ADMIN_SYMBOL');
    \$_POST['font_admin'] = getenv('ADMIN_FONT');
    \$_POST['color_admin'] = getenv('ADMIN_COLOR');
    \$_POST['back_admin'] = getenv('ADMIN_BACKGROUND');
    include 'controllers/install.controller.php';
    \$install = new InstallController();
    \$install->install();
    "
    echo "   ✅ Installation complete."
}

# 5. Secure the installation
secure_installation() {
    echo "5. 🛡️  Securing installation..."
    # Disable installer page for security
    INSTALLER_PATH="/var/www/html/cms/views/pages/install/install.php"
    if [ -f "$INSTALLER_PATH" ]; then
        rm -f "$INSTALLER_PATH"
        echo "<?php http_response_code(404); echo 'Installer is disabled.'; ?>" > "$INSTALLER_PATH"
        echo "   ... Installer page disabled."
    fi

    # Set final, secure permissions
    echo "   ... Setting final file permissions."
    chown -R www-data:www-data /var/www/html
    chmod -R 775 /var/www/html/cms/uploads /var/www/html/logs
    echo "   ✅ Security measures applied."
}

# 6. Save credentials and show summary
show_summary() {
    echo "---"
    echo "🎉 ChatCenter Installation Summary 🎉"
    echo "   URL: ${APP_URL}"
    echo "   Admin Email: ${ADMIN_EMAIL}"
    echo "   Admin Password: ${ADMIN_PASSWORD}"
    echo "   API Key: ${API_KEY:0:16}..."
    echo "---"

    # Save credentials to a file for easy access
    CREDENTIALS_FILE="/var/www/html/install-credentials.txt"
    echo "Credentials saved to ${CREDENTIALS_FILE} (inside the container)"
    cat > "$CREDENTIALS_FILE" << EOL
# ChatCenter Access Credentials (Generated on $(date))
URL=${APP_URL}
Email=${ADMIN_EMAIL}
Password=${ADMIN_PASSWORD}
API_Key=${API_KEY}
EOL
}

# --- Execution ---
main() {
    echo "🚀 ChatCenter Docker - Starting automatic setup..."
    setup_variables
    wait_for_db
    configure_environment
    run_installation
    secure_installation
    show_summary

    echo "✅ Setup complete. Starting web server..."
    exec "$@"
}

main
