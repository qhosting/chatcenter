# Análisis de Base de Datos - ChatCenter

## Resumen General

La base de datos `chatcenter.sql` implementa un sistema completo de gestión de centro de chat para un restaurante, con integración de WhatsApp, automatización de bots, sistema de pedidos, y gestión de contenido CMS. Utiliza MariaDB 10.4.21 con codificación UTF8MB4.

## Arquitectura General

- **Motor**: InnoDB
- **Charset**: utf8mb4
- **Tipo de Sistema**: Centro de Chat con CMS integrado
- **Enfoque**: Automatización de restaurante con WhatsApp Business API

---

## Análisis Detallado de Tablas

### 1. **Tabla: admins**
**Propósito**: Gestión de administradores del sistema

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_admin` | int(11) | Clave primaria |
| `email_admin` | text | Email del administrador |
| `password_admin` | text | Contraseña hasheada (bcrypt) |
| `rol_admin` | text | Rol (superadmin, admin, editor) |
| `permissions_admin` | text | Permisos en formato JSON |
| `token_admin` | text | Token JWT para autenticación |
| `token_exp_admin` | text | Expiración del token |
| `status_admin` | int(11) | Estado (1=activo) |
| `title_admin` | text | Título personalizado |
| `symbol_admin` | text | Icono HTML |
| `font_admin` | text | CSS de fuente personalizada |
| `color_admin` | text | Color hexadecimal |
| `back_admin` | text | URL imagen de fondo |
| `scode_admin` | text | Código de seguridad |
| `chatgpt_admin` | text | Configuración ChatGPT |
| `date_created_admin` | date | Fecha de creación |
| **PK**: `id_admin` | **AUTO_INCREMENT**: Sí |

**Datos ejemplo**:
- 3 usuarios: superadmin, admin, editor
- Configuración completa de branding para superadmin

---

### 2. **Tabla: bots**
**Propósito**: Configuración de bots automatizados para WhatsApp

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_bot` | int(11) | Clave primaria |
| `title_bot` | text | Nombre del bot (conversation, welcome, menu, etc.) |
| `type_bot` | text | Tipo (text, interactive) |
| `header_text_bot` | text | Texto del encabezado |
| `header_image_bot` | text | URL imagen |
| `header_video_bot` | text | URL video |
| `body_text_bot` | text | Contenido principal |
| `footer_text_bot` | text | Pie de página |
| `interactive_bot` | text | Tipo de interactividad (none, button, list) |
| `buttons_bot` | text | Configuración botones (JSON) |
| `list_bot` | text | Lista de opciones (JSON) |
| `title_list_bot` | text | Título de la lista |
| **PK**: `id_bot` | **AUTO_INCREMENT**: Sí |

**Flujo de bots identificado**:
1. `conversation` - Transición a agente humano
2. `welcome` - Saludo inicial con opciones
3. `menu` - Menú principal con categorías
4. `listMenu` - Lista de productos específicos
5. `reservation` - Proceso de reserva
6. `delivery` - Información para domicilio
7. `name`, `phone`, `email`, `address` - Recolección de datos
8. `process` - Procesamiento del pedido
9. `confirmation` - Confirmación del pedido
10. `checkout` - Pago final

---

### 3. **Tabla: categories**
**Propósito**: Categorías de productos del menú

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_category` | int(11) | Clave primaria |
| `title_category` | text | Nombre de categoría |
| **PK**: `id_category` | **AUTO_INCREMENT**: Sí |

**Categorías existentes**:
- Entradas
- Platos Fuertes
- Postres
- Bebidas

---

### 4. **Tabla: contacts**
**Propósito**: Contactos de clientes

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_contact` | int(11) | Clave primaria |
| `phone_contact` | text | Número de teléfono |
| `name_contact` | text | Nombre del contacto |
| `ai_contact` | int(11) | Flag para IA (1/0) |
| **PK**: `id_contact` | **AUTO_INCREMENT**: Sí |

---

### 5. **Tabla: messages**
**Propósito**: Almacenamiento de mensajes del chat

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_message` | int(11) | Clave primaria |
| `id_conversation_message` | text | ID de conversación |
| `type_message` | text | Tipo (client, business, bot, ia) |
| `id_whatsapp_message` | int(11) | FK a whatsapp |
| `client_message` | longtext | Mensaje del cliente |
| `business_message` | longtext | Respuesta del negocio |
| `template_message` | text | Plantilla utilizada |
| `expiration_message` | datetime | Fecha de expiración |
| `order_message` | int(11) | Orden en la conversación |
| `initial_message` | int(11) | Flag mensaje inicial |
| `phone_message` | text | Teléfono del usuario |
| **PK**: `id_message` | **AUTO_INCREMENT**: Sí |

**Flujo de conversación ejemplo**:
- Conversación: `c7ddcb50129839c421a333343d9ef810`
- Cliente realiza pedido de comida
- Bot guía selección de productos
- Recolección de datos personales
- Confirmación y pago

---

### 6. **Tabla: products**
**Propósito**: Catálogo de productos del menú

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_product` | int(11) | Clave primaria |
| `title_product` | text | Nombre del producto |
| `id_category_product` | int(11) | FK a categories |
| `price_product` | double | Precio en USD |
| `code_product` | text | Código SKU |
| **PK**: `id_product` | **FK**: `id_category_product` → `categories.id_category` |

**Catálogo de productos**:
- **Entradas** ($5): Papas Rústicas, Nachos de la Casa, Mazorca Gratinada
- **Platos Fuertes** ($15): Lomo a la Parrilla, Costillas BBQ, Spaghetti Alfredo, Lasagna
- **Postres** ($5): Flan de Caramelo, Tiramisú, Tres Leches, Ensalada de Frutas
- **Bebidas** ($3): Agua sin Gas, Limonada Natural, Coca Cola

---

### 7. **Tabla: orders**
**Propósito**: Órdenes de pedidos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_order` | int(11) | Clave primaria |
| `conversation_order` | text | ID de conversación |
| `products_order` | text | Resumen de productos |
| `phone_order` | text | Teléfono del cliente |
| `name_order` | text | Nombre del cliente |
| `email_order` | text | Email del cliente |
| `address_order` | text | Dirección de entrega |
| `contact_order` | text | Información de contacto |
| `total_order` | double | Total del pedido |
| `status_order` | text | Estado (Pendiente, En Preparación, etc.) |
| **PK**: `id_order` | **AUTO_INCREMENT**: Sí |

---

### 8. **Tabla: prompts**
**Propósito**: Configuración de prompts para IA

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_prompt` | int(11) | Clave primaria |
| `title_prompt` | text | Título del prompt |
| `content_prompt` | longtext | Contenido del prompt |
| `status_prompt` | int(11) | Estado activo/inactivo |
| **PK**: `id_prompt` | **AUTO_INCREMENT**: Sí |

**Prompt principal**: "Asistente IA Restaurant" - Configuración completa para Sofía, asistente virtual del restaurante con menú, precios y flujos de conversación definidos.

---

### 9. **Tabla: whatsapps**
**Propósito**: Configuración de APIs de WhatsApp

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_whatsapp` | int(11) | Clave primaria |
| `number_whatsapp` | text | Número de WhatsApp |
| `id_number_whatsapp` | text | ID del número |
| `id_app_whatsapp` | text | ID de la aplicación |
| `token_whatsapp` | text | Token de autenticación |
| `status_whatsapp` | int(11) | Estado de conexión |
| `ai_whatsapp` | int(11) | Flag para IA activa |
| **PK**: `id_whatsapp` | **AUTO_INCREMENT**: Sí |

---

### 10. **Tabla: modules**
**Propósito**: Módulos del CMS

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_module` | int(11) | Clave primaria |
| `id_page_module` | int(11) | FK a pages |
| `type_module` | text | Tipo de módulo |
| `title_module` | text | Título del módulo |
| `suffix_module` | text | Sufijo |
| `content_module` | text | Contenido |
| `width_module` | int(11) | Ancho (porcentaje) |
| `editable_module` | int(11) | Flag editable |
| **PK**: `id_module` | **FK**: `id_page_module` → `pages.id_page` |

---

### 11. **Tabla: pages**
**Propósito**: Páginas del CMS

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_page` | int(11) | Clave primaria |
| `title_page` | text | Título de la página |
| `url_page` | text | URL slug |
| `icon_page` | text | Icono Bootstrap |
| `type_page` | text | Tipo (custom, modules) |
| `order_page` | int(11) | Orden de navegación |
| **PK**: `id_page` | **AUTO_INCREMENT**: Sí |

**Páginas del sistema**:
1. Chat - Interfaz principal
2. Admins - Gestión de administradores
3. Archivos - Gestor de archivos
4. API-WS - Configuración WhatsApp
5. Mensajes - Visualizar mensajes
6. Bots - Configurar bots
7. Contactos - Lista de contactos
8. Categorías - Gestión de categorías
9. Productos - Catálogo de productos
10. Órdenes - Pedidos
11. Prompts - Configuración IA

---

### 12. **Tabla: columns**
**Propósito**: Configuración de columnas para módulos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_column` | int(11) | Clave primaria |
| `id_module_column` | int(11) | FK a modules |
| `title_column` | text | Título de la columna |
| `alias_column` | text | Alias/label |
| `type_column` | text | Tipo de campo |
| `matrix_column` | text | Matriz/valores |
| `visible_column` | int(11) | Flag visible |
| **PK**: `id_column` | **FK**: `id_module_column` → `modules.id_module` |

**Tipos de campos configurados**:
- select, object, email, password, text
- boolean, textarea, image, video
- code, datetime, int, money, json
- relations (para relaciones entre tablas)

---

### 13. **Tabla: files**
**Propósito**: Gestión de archivos subidos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_file` | int(11) | Clave primaria |
| `id_folder_file` | int(11) | FK a folders |
| `name_file` | text | Nombre del archivo |
| `extension_file` | text | Extensión |
| `type_file` | text | MIME type |
| `size_file` | double | Tamaño en bytes |
| `link_file` | text | URL del archivo |
| `thumbnail_vimeo_file` | text | Thumbnail Vimeo |
| `id_mailchimp_file` | text | ID Mailchimp |
| **PK**: `id_file` | **FK**: `id_folder_file` → `folders.id_folder` |

---

### 14. **Tabla: folders**
**Propósito**: Carpetas de archivos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_folder` | int(11) | Clave primaria |
| `name_folder` | text | Nombre de la carpeta |
| `size_folder` | text | Tamaño límite |
| `total_folder` | double | Espacio utilizado |
| `max_upload_folder` | text | Límite de subida |
| `url_folder` | text | URL base |
| `keys_folder` | text | Claves de configuración |
| **PK**: `id_folder` | **AUTO_INCREMENT**: Sí |

---

## Relaciones Identificadas

### Relaciones Directas (FK explícitas):

1. **products.id_category_product** → **categories.id_category**
2. **modules.id_page_module** → **pages.id_page**
3. **columns.id_module_column** → **modules.id_module**
4. **files.id_folder_file** → **folders.id_folder**

### Relaciones Lógicas (sin FK):

1. **messages.id_whatsapp_message** → **whatsapps.id_whatsapp**
2. **orders.conversation_order** → **messages.id_conversation_message**
3. **messages.id_conversation_message** ↔ **contacts.phone_contact/phone_message**

---

## Flujos de Negocio Principales

### 1. **Flujo de Pedido por WhatsApp**
```
Cliente → Bot Welcome → Selección Menú → Datos Cliente → Confirmación → Pago → Órden
```

**Paso a paso**:
1. Cliente inicia conversación
2. Bot "welcome" presenta opciones (pedido/reserva/menú)
3. Bot "menu" muestra categorías
4. Bot "listMenu" muestra productos específicos
5. Recolección de datos (name, phone, email, address)
6. Bot "confirmation" confirma pedido
7. Bot "checkout" envía link de pago
8. Se crea registro en tabla `orders`

### 2. **Gestión de Conversaciones**
- Cada conversación tiene un ID único (SHA256)
- Mensajes alternan entre tipo "client" y "business"
- Campo `order_message` mantiene secuencia
- Tipos de mensaje: client, business, bot, ia, manual

### 3. **Automatización con IA**
- Tabla `prompts` define comportamiento de IA
- Flag `ai_contact` en contacts activa IA por contacto
- Integración con ChatGPT (campo `chatgpt_admin` en admins)
- Fallback de bot a agente humano

---

## Análisis de Performance y Optimización

### Índices Identificados
- **Todas las tablas** tienen PRIMARY KEY en campos `id_*`
- **AUTO_INCREMENT** configurado en todas las claves primarias
- **Sin índices secundarios** definidos explícitamente

### Recomendaciones de Índices
```sql
-- Para mejorar performance de consultas frecuentes:
CREATE INDEX idx_messages_conversation ON messages(id_conversation_message);
CREATE INDEX idx_messages_phone ON messages(phone_message);
CREATE INDEX idx_messages_type ON messages(type_message);
CREATE INDEX idx_orders_status ON orders(status_order);
CREATE INDEX idx_products_category ON products(id_category_product);
CREATE INDEX idx_contacts_phone ON contacts(phone_contact);
```

---

## Características Técnicas Destacadas

### **Fortalezas**
✅ **Escalabilidad**: Estructura modular con CMS
✅ **Flexibilidad**: Campos JSON para configuraciones dinámicas
✅ **Trazabilidad**: Logging completo de conversaciones
✅ **Multi-tenancy**: Soporte para múltiples WhatsApps
✅ **IA Integration**: ChatGPT y prompts configurables
✅ **File Management**: Sistema completo de archivos
✅ **Branding**: Personalización completa por admin

### **Áreas de Mejora**
⚠️ **Sin Constraints FK**: Vulnerable a datos huérfanos
⚠️ **Text Fields**: Muchos campos TEXT sin límite definido
⚠️ **Sin Índices**: Performance degradará con volumen
⚠️ **Codificación**: Datos URL-encoded (complejo para consultas)
⚠️ **Sin Soft Delete**: Eliminación física de datos
⚠️ **Sin Timestamps**: Solo fechas de creación/actualización básicas

---

## Especificaciones Técnicas

### **Configuración del Servidor**
- **Versión**: MariaDB 10.4.21
- **PHP**: 8.0.10
- **Encoding**: UTF8MB4
- **Engine**: InnoDB

### **Capacidad Estimada**
- **Mensajes**: ~10,000 registros por mes (estimado)
- **Usuarios**: ~1,000 contactos
- **Órdenes**: ~500 pedidos por mes
- **Archivos**: ~100MB almacenamiento

### **Integraciones**
- **WhatsApp Business API**
- **ChatGPT Integration**
- **PayPal** (pasarela de pagos)
- **Vimeo** (thumbnails)
- **Cloudinary** (almacenamiento imágenes)

---

## Conclusiones

La base de datos implementa un **sistema completo de chat center para restaurante** con las siguientes características principales:

1. **Multicanal**: Soporte para WhatsApp con fallback a agentes humanos
2. **Inteligente**: Bots automatizados + IA para atención 24/7
3. **Comercial**: Sistema completo de pedidos y pagos
4. **Administrativo**: CMS integrado para gestión total
5. **Escalable**: Estructura modular y configurable

El diseño evidencia un **enfoque pragmático** priorizando funcionalidad sobre complejidad, con potencial significativo de optimización en índices y constraints de integridad referencial.

**Puntuación general**: 8/10 - Sistema funcional con margen de mejora en optimización.

---

*Análisis generado el: 2025-11-06*
*Archivo fuente: user_input_files/chatcenter/chatcenter.sql*