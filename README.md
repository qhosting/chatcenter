# 🐳 ChatCenter para EasyPanel

## 🚀 Instalación Rápida en EasyPanel

ChatCenter containerizado y optimizado para EasyPanel con instalación automática. Este proyecto incluye una base de datos MariaDB para que funcione de inmediato.

### ⚡ Instalación en 1-Click

1.  **Subir Archivos:** Sube el contenido de este repositorio a tu proyecto en EasyPanel.
2.  **Configurar Dominio:** Asegúrate de que tu dominio (ej. `whatscloud.site`) apunta al puerto `8080`.
3.  **Deploy:** Haz clic en "Deploy" en EasyPanel.

¡Eso es todo! El sistema se instalará automáticamente con la base de datos incluida.

### 🔧 Configuración Opcional (Base de Datos Externa)

Si prefieres usar una base de datos MariaDB externa (por ejemplo, una que ya tengas en EasyPanel), puedes configurar las siguientes variables de entorno en tu proyecto:

```bash
# Conexión a base de datos externa (opcional)
DB_HOST=tu_host_de_bd
DB_DATABASE=tu_base_de_datos
DB_USER=tu_usuario
DB_PASSWORD=tu_contraseña
DB_PORT=3306
```

### 📋 Otras Variables de Entorno (Opcionales)

Puedes personalizar tu instalación con estas variables:

```bash
# Configuración de la aplicación
APP_DOMAIN=whatscloud.site
APP_URL=https://whatscloud.site

# Credenciales del administrador (se generan automáticamente si no se especifican)
ADMIN_EMAIL=admin@whatscloud.site
ADMIN_TITLE=ChatCenter
```

### 📊 Credenciales de Acceso

Después de la instalación, las credenciales de administrador (email y contraseña) se guardarán en el archivo `install-credentials.txt` dentro del contenedor. También se mostrarán en los logs de EasyPanel durante el primer despliegue.

### 🏗️ Arquitectura del Sistema

El proyecto se ejecuta con dos contenedores principales:

1.  **`chatcenter`**: La aplicación principal PHP que corre sobre Apache.
2.  **`db`**: Un contenedor MariaDB 10.6 que sirve como base de datos por defecto.

Por defecto, `chatcenter` se conecta a `db`. Si defines las variables de entorno de `DB_HOST`, la aplicación se conectará a tu base de datos externa en su lugar.

---

**🎉 ¡Listo para usar!** ChatCenter se instala automáticamente y está listo en minutos.
