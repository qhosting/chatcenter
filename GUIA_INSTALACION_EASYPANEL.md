# 🚀 Guía de Instalación de ChatCenter en EasyPanel

Esta guía te ayudará a desplegar ChatCenter en EasyPanel de forma rápida y sencilla.

## 🛠️ Instalación Sencilla (Recomendado)

El proyecto está diseñado para funcionar sin configuración adicional, ya que incluye su propia base de datos.

### Paso 1: Subir los Archivos

1.  **Crea un nuevo proyecto** en EasyPanel de tipo "Aplicación Docker".
2.  **Sube todos los archivos** de este repositorio a la carpeta de tu proyecto.

### Paso 2: Configurar el Dominio

1.  En EasyPanel, ve a la sección de **Dominios**.
2.  Apunta tu dominio (ej. `midominio.com`) al **puerto `8080`** de tu aplicación.
3.  Activa el **SSL** para tener `https://`.

### Paso 3: Desplegar

1.  Haz clic en el botón **"Deploy"** en tu proyecto de EasyPanel.
2.  **¡Listo!** La aplicación se instalará automáticamente.

### Paso 4: Verificar las Credenciales

-   Revisa los **logs** de tu aplicación en EasyPanel. Al finalizar la instalación, verás las credenciales de acceso (email y contraseña).
-   También se guardarán en el archivo `install-credentials.txt` dentro del contenedor.

---

## 🔧 Configuración Avanzada (Opcional)

### Usar una Base de Datos Externa

Si no quieres usar la base de datos que viene incluida, puedes conectarte a una base de datos MariaDB externa (por ejemplo, otra que ya tengas en EasyPanel). Para ello, añade las siguientes variables de entorno a tu proyecto:

```
DB_HOST=el_host_de_tu_bd
DB_DATABASE=el_nombre_de_tu_bd
DB_USER=tu_usuario_de_bd
DB_PASSWORD=tu_contraseña_de_bd
DB_PORT=3306
```

### Personalizar la Aplicación

Puedes ajustar otros detalles de la aplicación a través de las variables de entorno:

-   `APP_DOMAIN`: Tu dominio (ej. `midominio.com`).
-   `APP_URL`: La URL completa (ej. `https://midominio.com`).
-   `ADMIN_EMAIL`: El email que se usará para la cuenta de administrador.
-   `ADMIN_TITLE`: El título que aparecerá en el panel de administración.

---

## 🔍 Solución de Problemas

-   **Error de conexión a la base de datos:** Si usas una base de datos externa, asegúrate de que las credenciales en las variables de entorno son correctas y que la base de datos es accesible desde el contenedor de ChatCenter.
-   **La instalación no termina:** Revisa los logs en EasyPanel para ver si hay algún error durante el proceso.

Para más detalles, consulta el archivo `README.md`.
