# Documentación de API: Catálogo (Categorías, Subcategorías, Ítems) y Requerimientos

Guía técnica integral para el consumo de los endpoints de **Catálogo de Materiales** y **Gestión de Requerimientos** en **Monitor SAT Industriales**.

---

## 1. Arquitectura y Reglas Generales

* **Autenticación Dual**:
  * **API REST (Móvil / Integraciones externas)**: Autenticación vía cabecera `Authorization: Bearer <TOKEN_SANCTUM>`.
  * **Web Interna (Blade / Alpine.js / Livewire)**: Autenticación por sesión Laravel (`auth`) y verificación de token `X-CSRF-TOKEN`.
* **Almacenamiento S3 (MinIO) y Optimización de Imágenes**: Las fotografías de los ítems se procesan con `Intervention Image v3`, se escalan proporcionalmente con `scaleDown(1200, 1200)` para no sobrecargar el almacenamiento ni la memoria de dispositivos móviles, se convierten a formato optimizado **WebP (calidad 85)** y se almacenan en el bucket S3 servido vía Cloudflare (`https://s3.sat-sistemas.uk`). Los recursos API retornan tanto el campo `photo` como `photo_url` con la URL absoluta pública resuelta.
* **Atomicidad**: La creación de requerimientos y sus listas de materiales se ejecuta en transacciones SQL (`DB::transaction`) garantizando consistencia total.

---

## 2. Endpoints de Categorías

Permite clasificar los materiales en categorías principales (ej: Electricidad, Pinturas, Ferretería).

### 2.1 Listar y Buscar Categorías

* **Rutas**:
  * API: `GET /api/categories`
  * Web: `GET /categories` o `GET /categories/search`
* **Cabeceras**:
  * `Accept: application/json`
  * `Authorization: Bearer <TOKEN>` (en API)

#### Parámetros Query (URL):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- |
| `search` | `string` | — | Término de búsqueda filtrado por `name` o `description`. |
| `with_subcategories` | `boolean` | `false` | Si es `true` (`1`), precarga el listado de subcategorías asociadas. |
| `sort_by` | `string` | `name` | Columna de ordenamiento: `id`, `name` o `created_at`. |
| `sort_order` | `string` | `asc` | Dirección: `asc` o `desc`. |
| `per_page` | `integer` | `15` | Cantidad de registros por página. |

#### Respuesta Exitosa (`200 OK`):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Materiales Eléctricos",
      "description": "Cables, interruptores, canaletas y luminarias",
      "subcategories_count": 4,
      "subcategories": [
        {
          "id": 10,
          "category_id": 1,
          "name": "Conductores y Cables",
          "created_at": "2026-09-20T10:00:00.000000Z",
          "updated_at": "2026-09-20T10:00:00.000000Z"
        }
      ],
      "created_at": "2026-09-20T09:30:00.000000Z",
      "updated_at": "2026-09-20T09:30:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 2.2 Crear Categoría

* **Rutas**:
  * API: `POST /api/categories`
  * Web: `POST /categories`
* **Cabeceras**: `Content-Type: application/json`, `Accept: application/json`

#### Cuerpo de la Petición (JSON):
```json
{
  "name": "Sistemas de Seguridad",
  "description": "Equipos de protección personal y señalética"
}
```

| Campo | Tipo | Requerido | Reglas |
| :--- | :--- | :--- | :--- |
| `name` | `string` | **Sí** | Máx. 100 caracteres. Debe ser único (`unique:categories,name`). |
| `description` | `string` | No | Texto descriptivo opcional. |

#### Respuesta Exitosa (`201 Created`):
```json
{
  "data": {
    "id": 5,
    "name": "Sistemas de Seguridad",
    "description": "Equipos de protección personal y señalética",
    "subcategories_count": 0,
    "created_at": "2026-09-23T15:50:00.000000Z",
    "updated_at": "2026-09-23T15:50:00.000000Z"
  }
}
```

---

## 3. Endpoints de Subcategorías

Las subcategorías dependen de una categoría padre y son obligatorias al crear un ítem.

### 3.1 Listar y Buscar Subcategorías

* **Rutas**:
  * API: `GET /api/subcategories`
  * Web: `GET /subcategories` o `GET /subcategories/search`

#### Parámetros Query (URL):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- |
| `category_id` | `integer` | — | Filtra exclusivamente subcategorías pertenecientes a dicha categoría. |
| `search` | `string` | — | Búsqueda por coincidencia en el nombre de la subcategoría. |
| `sort_by` | `string` | `name` | Campo de ordenación: `id`, `name`, `category_id`, `created_at`. |
| `sort_order` | `string` | `asc` | Dirección: `asc` o `desc`. |
| `per_page` | `integer` | `15` | Cantidad de resultados por página. |

#### Respuesta Exitosa (`200 OK`):
```json
{
  "data": [
    {
      "id": 12,
      "category_id": 1,
      "name": "Tomacorrientes e Interruptores",
      "category": {
        "id": 1,
        "name": "Materiales Eléctricos"
      },
      "created_at": "2026-09-20T10:05:00.000000Z",
      "updated_at": "2026-09-20T10:05:00.000000Z"
    }
  ]
}
```

---

### 3.2 Crear Subcategoría

* **Rutas**:
  * API: `POST /api/subcategories`
  * Web: `POST /subcategories`

#### Cuerpo de la Petición (JSON):
```json
{
  "category_id": 1,
  "name": "Canaletas y Accesorios"
}
```

| Campo | Tipo | Requerido | Reglas |
| :--- | :--- | :--- | :--- |
| `category_id` | `integer` | **Sí** | Debe existir en `categories.id`. |
| `name` | `string` | **Sí** | Máx. 100 caracteres. |

#### Respuesta Exitosa (`201 Created`):
```json
{
  "data": {
    "id": 14,
    "category_id": 1,
    "name": "Canaletas y Accesorios",
    "category": {
      "id": 1,
      "name": "Materiales Eléctricos"
    },
    "created_at": "2026-09-23T15:52:00.000000Z",
    "updated_at": "2026-09-23T15:52:00.000000Z"
  }
}
```

---

## 4. Endpoints de Ítems / Materiales

Permite buscar materiales existentes en catálogo, crearlos con generación automática de código SKU y administrar sus fotografías en S3.

### 4.1 Búsqueda y Listado de Ítems

* **Rutas**:
  * API: `GET /api/items`
  * Web: `GET /items` o `GET /items/search`

#### Parámetros Query (URL):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- |
| `search` | `string` | — | Búsqueda combinada: filtra tanto por **nombre del material** como por **código SKU**. |
| `category_id` | `integer` | — | Filtra ítems pertenecientes a una categoría principal a través de su subcategoría. |
| `subcategory_id` | `integer` | — | Filtra ítems que pertenezcan a la subcategoría indicada. |
| `unit_id` | `integer` | — | Filtra ítems por su unidad de medida (ej: Metros, Unidades, Kilogramos). |
| `sort_by` | `string` | `name` | Opciones: `id`, `sku`, `name`, `created_at`. |
| `sort_order` | `string` | `asc` | Opciones: `asc`, `desc`. |
| `per_page` | `integer` | `15` | Tamaño de página para la paginación. |
| `page` | `integer` | `1` | Número de página para la paginación. |

#### Respuesta Exitosa (`200 OK`):
```json
{
  "data": [
    {
      "id": 45,
      "sku": "ELE-CAB-0045",
      "name": "Cable Vulcanizado 3x14 AWG Indeco",
      "subcategory_id": 10,
      "subcategory": {
        "id": 10,
        "name": "Conductores y Cables",
        "category_id": 1,
        "category": {
          "id": 1,
          "name": "Materiales Eléctricos"
        }
      },
      "unit_id": 2,
      "unit": {
        "id": 2,
        "name": "Metros",
        "symbol": "m"
      },
      "photo": "https://s3.sat-sistemas.uk/integral/items/9d7b4202-6014-4113-a417-802c63ef93b5.webp",
      "photo_url": "https://s3.sat-sistemas.uk/integral/items/9d7b4202-6014-4113-a417-802c63ef93b5.webp",
      "created_by": 2,
      "creator": {
        "id": 2,
        "name": "Giancarlo Almacén",
        "email": "giancarlo@sat-industriales.pe"
      },
      "created_at": "2026-09-22T14:10:00.000000Z",
      "updated_at": "2026-09-22T14:10:00.000000Z"
    }
  ]
}
```

---

### 4.2 Crear un Ítem

* **Rutas**:
  * API: `POST /api/items`
  * Web: `POST /items`
* **Tipo de Contenido**:
  * Si incluye archivo fotográfico: `multipart/form-data`
  * Si solo contiene texto/datos: `application/json`

#### Campos de la Solicitud:

| Campo | Tipo | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `name` | `string` | **Sí** | Nombre descriptivo del material (máx. 255 caracteres). |
| `subcategory_id` | `integer` | **Sí** | ID de la subcategoría a la que pertenece (`subcategories.id`). |
| `unit_id` | `integer` | No | ID de la unidad de medida (`units.id`). |
| `sku` | `string` | No | Código único del ítem. **Si se omite o viene vacío**, el sistema lo autogenera mediante `GenerateItemSkuAction`. |
| `photo` | `file` | No | Archivo de imagen (`jpeg, png, jpg, webp`, máx. 10MB). Se procesa y convierte automáticamente a WebP en S3. |

#### Respuesta Exitosa (`201 Created`):
```json
{
  "data": {
    "id": 46,
    "sku": "MAT-0046",
    "name": "Cinta Aislante 3M Super 33+",
    "subcategory_id": 14,
    "subcategory": {
      "id": 14,
      "name": "Canaletas y Accesorios",
      "category_id": 1
    },
    "unit_id": 1,
    "unit": {
      "id": 1,
      "name": "Unidades",
      "symbol": "und"
    },
    "photo": "https://s3.sat-sistemas.uk/integral/items/3f39a70b-8521-4f10-bc52-8b438276f571.webp",
    "photo_url": "https://s3.sat-sistemas.uk/integral/items/3f39a70b-8521-4f10-bc52-8b438276f571.webp",
    "created_by": 2,
    "creator": {
      "id": 2,
      "name": "Giancarlo Almacén",
      "email": "giancarlo@sat-industriales.pe"
    },
    "created_at": "2026-09-23T15:55:00.000000Z",
    "updated_at": "2026-09-23T15:55:00.000000Z"
  }
}
```

---

### 4.3 Consultar Detalle de un Ítem

* **Rutas**:
  * API: `GET /api/items/{id}`
  * Web: `GET /items/{id}`
* **Respuesta (`200 OK`)**: Retorna el recurso `ItemResource` con relaciones cargadas y URL pública de foto en S3.

---

### 4.4 Actualizar un Ítem

* **Rutas**:
  * API: `PUT /api/items/{id}` (o `POST /api/items/{id}` con campo `_method=PUT` al subir archivos multipart)
* **Comportamiento con Fotografías**: Si se adjunta una nueva foto en `photo`, el servicio `ItemPhotoService` elimina la fotografía anterior en MinIO S3 y almacena la nueva en WebP.

---

### 4.5 Eliminar un Ítem

* **Rutas**:
  * API: `DELETE /api/items/{id}`
* **Protección de Integridad**: Si el ítem ya forma parte de un requerimiento (`requirement_lists`), el endpoint bloquea la eliminación y retorna `422 Unprocessable Content`:
  ```json
  {
    "success": false,
    "message": "No se puede eliminar el ítem porque está asociado a uno o más requerimientos."
  }
  ```
* Si no tiene asociaciones, se borra el registro y se elimina automáticamente su foto en S3 (`200 OK`).

---

### 4.6 Encolamiento Asíncrono de Fotografía para Ítems

Permite subir la fotografía del ítem de forma no bloqueante. El archivo se almacena temporalmente y un Job de cola (`UploadItemPhotoJob`) lo convierte a WebP, lo sube a S3 y limpia el disco local.

* **Rutas**:
  * API REST (Móvil / Bearer Sanctum):
    * `POST /api/items/{item}/photo/queue` (Nombre: `api.items.photo.queue`)
    * `POST /api/items/photo/queue` (Nombre: `api.items.photo.queue.general`)
  * Web Interna (Sesión):
    * `POST /items/{item}/photo/queue` (Nombre: `items.photo.queue`)
    * `POST /items/photo/queue` (Nombre: `items.photo.queue.general`)
* **Parámetros (`multipart/form-data`)**:
  * `photo`: Archivo de imagen obligatorio (máx. 10MB).
  * `item_id`: Obligatorio si se usa la ruta general sin parámetro de URL.

#### Respuesta de Cola (`202 Accepted`):
```json
{
  "success": true,
  "message": "La fotografía del ítem ha sido puesta en cola para su procesamiento.",
  "data": {
    "item_id": 46,
    "sku": "MAT-0046",
    "status": "queued"
  }
}
```

---

### 4.7 Guía de Integración Móvil (Flutter / App Monitor): Optimización y Caché de Imágenes

Para garantizar que el catálogo y la lista de materiales carguen instantáneamente sin congelar el scroll ni consumir datos móviles excesivos:

#### 1. Optimización en Backend (Procesamiento Automático)
* Al subir cualquier imagen a través de `POST /api/items`, `PUT /api/items/{id}` o la ruta de cola `/items/photo/queue`:
  * Se aplica **`scaleDown(1200, 1200)`** preservando el ratio de aspecto original sin distorsionar ni ampliar imágenes pequeñas.
  * Se convierte a formato **WebP (calidad 85)**.
  * Se almacena con UUID inmutable en S3 (`items/{uuid}.webp`) y se distribuye con encabezados de caché pública (`Cache-Control: public, max-age=31536000, immutable`).
  * Los recursos API devuelven tanto el atributo `photo` como `photo_url` con la URL absoluta pública (`https://s3.sat-sistemas.uk/...`).

#### 2. Caché de Disco vs Memoria en el Cliente Móvil
* **Por qué `cached_network_image` es indispensable**: Aunque la app de Flutter guarde en caché de estado la lista JSON de requerimientos, los modelos Dart solo contienen el string de la URL. Si se usa `Image.network`, Flutter almacena la imagen en una memoria RAM volátil (`imageCache`) que se vacía al cerrar la aplicación o cuando se supera el límite de memoria del dispositivo al hacer scroll en listas largas. Con `cached_network_image`, el binario se persiste en el almacenamiento flash del teléfono, permitiendo visualización offline y carga en 1 ms.

#### 3. Prevención de Saturación de Memoria GPU (`memCacheWidth` / `memCacheHeight`)
* Cuando se renderizan miniaturas en listas (ej. tiles de 56x56 dp en `SwipeableRequirementItemTile`):
  * **Sin límite de decodificación**: Flutter descomprime el WebP a un mapa de bits RGBA completo en memoria GPU (1200x900 px = ~4.3 MB por ítem).
  * **Con límite de decodificación**: Usando `memCacheWidth: (width * devicePixelRatio).round()`, la GPU solo decodifica una textura de ~150 px (~90 KB por ítem), ahorrando más del 95% de memoria RAM y garantizando 60/120 fps constantes.

#### 4. Componente Recomendado para Flutter (`AppCachedImage`)
```dart
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

class AppCachedImage extends StatelessWidget {
  final String? imageUrl;
  final double width;
  final double height;
  final double borderRadius;
  final BoxFit fit;

  const AppCachedImage({
    super.key,
    required this.imageUrl,
    this.width = 56,
    this.height = 56,
    this.borderRadius = 8,
    this.fit = BoxFit.cover,
  });

  @override
  Widget build(BuildContext context) {
    if (imageUrl == null || imageUrl!.trim().isEmpty) {
      return _buildPlaceholder();
    }

    final pixelRatio = MediaQuery.of(context).devicePixelRatio;
    final memWidth = (width * pixelRatio).round();
    final memHeight = (height * pixelRatio).round();

    return ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: CachedNetworkImage(
        imageUrl: imageUrl!,
        width: width,
        height: height,
        fit: fit,
        memCacheWidth: memWidth,
        memCacheHeight: memHeight,
        maxWidthDiskCache: 600,
        maxHeightDiskCache: 600,
        placeholder: (context, url) => Container(
          width: width,
          height: height,
          color: Colors.grey.shade200,
          child: const Center(
            child: SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ),
        ),
        errorWidget: (context, url, error) => _buildPlaceholder(),
      ),
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.grey.shade200,
        borderRadius: BorderRadius.circular(borderRadius),
      ),
      child: Icon(
        Icons.inventory_2_outlined,
        size: width * 0.45,
        color: Colors.grey.shade500,
      ),
    );
  }
}
```

---

## 5. Endpoints de Requerimientos

Permite registrar, consultar y administrar solicitudes de materiales vinculando una tienda/sede (`sub_client_id`) con su actividad obligatoria (`activity_name`) y su lista de materiales (`items`) en una sola operación atómica transaccional (`DB::transaction`).

### Resumen de Endpoints de Requerimientos:

| Método | Endpoint | Nombre de Ruta | Propósito |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/requirements` | `requirements.index` | Listar requerimientos paginados con búsqueda y filtros. |
| `POST` | `/api/requirements` | `requirements.store` | Crear un requerimiento con cabecera y materiales iniciales. |
| `GET` | `/api/requirements/{id}` | `requirements.show` | Consultar detalle de requerimiento y sus materiales. |
| `PUT` | `/api/requirements/{id}` | `requirements.update` | Modificar cabecera (tienda, actividad) y/o materiales. |
| `DELETE` | `/api/requirements/{id}` | `requirements.destroy` | Eliminar un requerimiento y sus materiales asociados. |
| `GET` | `/api/requirements/{id}/items` | `requirements.items.index` | Listar materiales del requerimiento con paginación y filtros. |
| `POST` | `/api/requirements/{id}/items` | `requirements.items.store` | Agregar material o incrementar cantidad si ya existe. |
| `GET` | `/api/requirements/{id}/items/{item}` | `requirements.items.show` | Consultar detalle de un material específico en lista. |
| `PUT` | `/api/requirements/{id}/items/{item}` | `requirements.items.update` | Actualizar cantidad de un material en la lista. |
| `DELETE` | `/api/requirements/{id}/items/{item}` | `requirements.items.destroy` | Quitar un material específico de la lista. |
| `DELETE` | `/api/requirements/{id}/items` | `requirements.items.clear` | Vaciar todos los materiales del requerimiento. |

---

### 5.1 Crear Requerimiento (`POST /api/requirements`)

* **Rutas**:
  * API: `POST /api/requirements`
  * Web: `POST /requirements` (Nombre: `requirements.web.store`)
* **Cabeceras**:
  * API: `Authorization: Bearer <TOKEN>`, `Content-Type: application/json`, `Accept: application/json`
  * Web: `X-CSRF-TOKEN: <CSRF_TOKEN>`, `Content-Type: application/json`, `Accept: application/json`

#### Cuerpo de la Petición (JSON Payload):
> **Flexibilidad**: Cada elemento dentro de `items` puede identificarse con `item_id` o `id`.

```json
{
  "sub_client_id": 3,
  "activity_name": "Mantenimiento preventivo subestación norte",
  "items": [
    {
      "item_id": 45,
      "quantity": 12.5
    },
    {
      "id": 46,
      "quantity": 5
    }
  ]
}
```

#### Parámetros del Payload:

| Parámetro | Tipo | Requerido | Reglas y Validación |
| :--- | :--- | :--- | :--- |
| `sub_client_id` | `integer` | **Sí** | ID de la tienda o sede (`sub_clients.id`). Debe existir en la base de datos. |
| `activity_name` | `string` | **Sí** | **Obligatorio**. Máx. 255 caracteres. No admite valores vacíos ni sólo espacios en blanco (`trim`). Debe contener texto descriptivo con al menos una letra en español (`regex:/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/u`). Caracteres permitidos: texto, números y puntuación (`. , - / ( ) : ; #`). |
| `items` | `array` | No | Lista de materiales iniciales. Si se omite o es un array vacío, se crea la cabecera sin ítems. Si se envía, cada elemento debe cumplir sus reglas. |
| `items.*.item_id` o `id` | `integer` | Condicional | ID del ítem en catálogo (`items.id`). Requerido si se envía el ítem. |
| `items.*.quantity` | `numeric` | Condicional | Cantidad solicitada. Requerido si se envía el ítem. Mínimo permitido: `0.01`. |

#### Respuesta Exitosa (`201 Created`):
```json
{
  "data": {
    "id": 58,
    "sub_client_id": 3,
    "sub_client": {
      "id": 3,
      "name": "Planta Principal Callao",
      "client_id": 1
    },
    "activity_name": "Mantenimiento preventivo subestación norte",
    "created_by": 2,
    "creator": {
      "id": 2,
      "name": "Giancarlo Almacén",
      "email": "giancarlo@sat-industriales.pe"
    },
    "items": [
      {
        "id": 140,
        "requirement_id": 58,
        "item_id": 45,
        "quantity": 12.5,
        "item": {
          "id": 45,
          "sku": "ELE-CAB-0045",
          "name": "Cable Vulcanizado 3x14 AWG Indeco",
          "subcategory_id": 10,
          "unit_id": 2,
          "unit": {
            "id": 2,
            "name": "Metros",
            "symbol": "m"
          },
          "photo": "https://s3.sat-sistemas.uk/integral/items/photo.webp",
          "created_by": 2,
          "created_at": "2026-09-20T10:00:00.000000Z",
          "updated_at": "2026-09-20T10:00:00.000000Z"
        },
        "created_at": "2026-09-23T15:58:00.000000Z",
        "updated_at": "2026-09-23T15:58:00.000000Z"
      }
    ],
    "items_count": 1,
    "created_at": "2026-09-23T15:58:00.000000Z",
    "updated_at": "2026-09-23T15:58:00.000000Z"
  }
}
```

---

### 5.2 Listar Requerimientos (`GET /api/requirements`)

Permite obtener la lista paginada de requerimientos registrados, con búsqueda en tiempo real y filtros.

* **Rutas**:
  * API: `GET /api/requirements`
* **Cabeceras**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`

#### Parámetros Query:

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- |
| `search` | `string` | — | Busca coincidencia en el nombre de la actividad (`activity_name`) o en el nombre de la tienda (`subClient.name`). |
| `sub_client_id` | `integer` | — | Filtra requerimientos por tienda o sede específica. |
| `sort_by` | `string` | `created_at` | Columna de ordenamiento: `id`, `activity_name`, `created_at`. |
| `sort_order` | `string` | `desc` | Dirección del orden: `asc` o `desc`. |
| `per_page` | `integer` | `15` | Cantidad de requerimientos por página. |
| `page` | `integer` | `1` | Número de página. |

#### Respuesta Exitosa (`200 OK`):
```json
{
  "data": [
    {
      "id": 58,
      "sub_client_id": 3,
      "sub_client": {
        "id": 3,
        "name": "Planta Principal Callao",
        "client_id": 1
      },
      "activity_name": "Mantenimiento preventivo subestación norte",
      "created_by": 2,
      "creator": {
        "id": 2,
        "name": "Giancarlo Almacén",
        "email": "giancarlo@sat-industriales.pe"
      },
      "items": [ ... ],
      "items_count": 5,
      "created_at": "2026-09-23T15:58:00.000000Z",
      "updated_at": "2026-09-23T15:58:00.000000Z"
    }
  ],
  "links": {
    "first": "https://monitor.sat-industriales.pe/api/requirements?page=1",
    "last": "https://monitor.sat-industriales.pe/api/requirements?page=4",
    "prev": null,
    "next": "https://monitor.sat-industriales.pe/api/requirements?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 4,
    "per_page": 15,
    "to": 15,
    "total": 52
  }
}
```

---

### 5.3 Consultar Detalle de un Requerimiento (`GET /api/requirements/{id}`)

* **Rutas**:
  * API: `GET /api/requirements/{id}`
* **Cabeceras**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`

#### Respuesta Exitosa (`200 OK`):
Retorna el recurso `RequirementResource` con todas sus relaciones precargadas (`subClient`, `creator`, `items.item.unit` e `items_count`).

---

### 5.4 Actualizar Requerimiento (`PUT /api/requirements/{id}`)

Permite modificar los datos de cabecera (`sub_client_id`, `activity_name`) y, opcionalmente, sincronizar o reemplazar la lista de materiales asociados.

* **Rutas**:
  * API: `PUT /api/requirements/{id}`
  * Web: `PUT /requirements/{id}` (Nombre: `requirements.web.update`)
* **Cabeceras**: `Authorization: Bearer <TOKEN>`, `Content-Type: application/json`, `Accept: application/json`

#### Cuerpo de la Petición (JSON Payload):
```json
{
  "sub_client_id": 3,
  "activity_name": "Mantenimiento correctivo de tableros eléctricos",
  "items": [
    { "item_id": 45, "quantity": 15.0 },
    { "item_id": 46, "quantity": 8 }
  ]
}
```

> **Nota sobre `activity_name`**: Si se incluye en el payload de actualización, es **estrictamente obligatorio** (`sometimes|required`). No puede enviarse vacío (`""`), nulo (`null`), ni con solo espacios en blanco (`"   "`). De lo contrario, responderá con error de validación `422 Unprocessable Content`.

#### Respuesta Exitosa (`200 OK`):
Retorna el `RequirementResource` actualizado con código HTTP `200`.

---

### 5.5 Eliminar Requerimiento (`DELETE /api/requirements/{id}`)

Elimina el requerimiento y todos sus materiales asociados en cascada.

* **Rutas**: `DELETE /api/requirements/{id}`
* **Cabeceras**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`

#### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "message": "Requerimiento eliminado exitosamente."
}
```

---

### 5.6 Gestión Granular de Materiales (`requirements.items`)

Permite manipular la lista de materiales de un requerimiento ya existente de forma individual (agregar ítems posteriores, ajustar cantidades, quitarlos o listarlos con paginación del backend).

* **Rutas**:
  * API: `GET /api/requirements/{requirement}/items` (Listar materiales con paginación del backend)
  * Web: `GET /requirements/{requirement}/items` (Ruta con nombre: `requirements.items.index`)
  * API: `POST /api/requirements/{requirement}/items` (Agregar material / sumar cantidad)
  * API: `GET /api/requirements/{requirement}/items/{item}` (Detalle del ítem en lista)
  * API: `PUT /api/requirements/{requirement}/items/{item}` (Modificar cantidad)
  * API: `DELETE /api/requirements/{requirement}/items/{item}` (Eliminar ítem de la lista)

#### A. Listar Materiales con Paginación del Backend y Filtros (`GET /api/requirements/{requirement}/items`):

#### Parámetros Query (URL):

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- |
| `search` | `string` | — | Búsqueda por nombre del material o código SKU. |
| `category_id` | `integer` | — | Filtra ítems pertenecientes a una categoría principal. |
| `subcategory_id` | `integer` | — | Filtra ítems pertenecientes a una subcategoría específica. |
| `sort_by` | `string` | `created_at` | Opciones: `created_at`, `quantity`, `name`. |
| `sort_order` | `string` | `desc` | Dirección: `asc` o `desc`. |
| `per_page` | `integer` | `15` | Cantidad de materiales por página. |
| `page` | `integer` | `1` | Número de página actual. |

#### Respuesta Exitosa Paginada (`200 OK`):
```json
{
  "data": [
    {
      "id": 140,
      "requirement_id": 58,
      "item_id": 45,
      "quantity": 22.5,
      "item": {
        "id": 45,
        "sku": "ELE-CAB-0045",
        "name": "Cable Vulcanizado 3x14 AWG Indeco",
        "subcategory": {
          "id": 10,
          "name": "Conductores y Cables",
          "category_id": 1,
          "category": {
            "id": 1,
            "name": "Materiales Eléctricos"
          }
        },
        "unit": {
          "id": 2,
          "name": "Metros",
          "symbol": "m"
        }
      },
      "created_at": "2026-09-23T15:58:00.000000Z",
      "updated_at": "2026-09-23T16:05:00.000000Z"
    }
  ],
  "links": {
    "first": "https://monitor.sat-industriales.pe/api/requirements/58/items?page=1",
    "last": "https://monitor.sat-industriales.pe/api/requirements/58/items?page=3",
    "prev": null,
    "next": "https://monitor.sat-industriales.pe/api/requirements/58/items?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 10,
    "to": 10,
    "total": 25
  }
}
```

#### B. Agregar Material a Requerimiento Existente (`POST /api/requirements/{requirement}/items`):

> **Regla de Negocio Inteligente**: Si el `item_id` ya existía en este requerimiento, el endpoint **no duplica la fila**, sino que **incrementa la cantidad** sumándole el nuevo valor y responde con código `200 OK`. Si el ítem no existía, crea el registro y responde con código `201 Created`.

```json
{
  "item_id": 45,
  "quantity": 10
}
```

#### Respuesta (`200 OK` / `201 Created`):
```json
{
  "data": {
    "id": 140,
    "requirement_id": 58,
    "item_id": 45,
    "quantity": 22.5,
    "item": {
      "id": 45,
      "sku": "ELE-CAB-0045",
      "name": "Cable Vulcanizado 3x14 AWG Indeco",
      "unit": {
        "id": 2,
        "name": "Metros",
        "symbol": "m"
      }
    },
    "created_at": "2026-09-23T15:58:00.000000Z",
    "updated_at": "2026-09-23T16:05:00.000000Z"
  }
}
```

#### C. Modificar Cantidad (`PUT /api/requirements/{requirement}/items/{item_id_or_list_id}`):
```json
{
  "quantity": 15
}
```

#### D. Eliminar Material del Requerimiento (`DELETE /api/requirements/{requirement}/items/{item}`):
```json
{
  "success": true,
  "message": "Ítem eliminado del requerimiento exitosamente."
}
```

#### E. Vaciar Todos los Materiales del Requerimiento (`DELETE /api/requirements/{requirement}/items`):

* **Rutas**:
  * API: `DELETE /api/requirements/{requirement}/items`
  * Web: `DELETE /requirements/{requirement}/items` (Ruta con nombre: `requirements.items.clear`)

Elimina de forma atómica e instantánea todos los registros asociados en `requirement_lists` para el requerimiento indicado, sin importar cuántas páginas contenga.

##### Respuesta Exitosa (`200 OK`):
```json
{
  "success": true,
  "message": "Se eliminaron 25 materiales del requerimiento.",
  "deleted_count": 25
}
```

---

## 6. Endpoints Complementarios Esenciales para el Flujo

Estos dos endpoints son indispensables para que la interfaz o la aplicación cliente pueda operar el formulario de requerimientos sin valores "quemados":

### 6.1 Tiendas / Sedes de Clientes (Selección de `sub_client_id`)

Permite al usuario buscar la tienda o sede solicitante en el input autocompletable.

* **Rutas**:
  * Web: `GET /sub-clients/search` (Ruta: `sub-clients.search`)
  * API: `GET /api/sub-clients/data`

#### Parámetros Query:
* `search`: Texto de búsqueda (filtra por nombre de tienda, dirección o descripción).
* `client_id`: Filtro opcional por empresa cliente padre.
* `limit` / `per_page`: Cantidad de tiendas a retornar (ej: `10`).

#### Respuesta Exitosa (`200 OK`):
```json
{
  "data": [
    {
      "id": 3,
      "name": "Planta Principal Callao",
      "address": "Av. Elmer Faucett 123",
      "client": {
        "id": 1,
        "name": "SAT Industriales S.A.C."
      }
    }
  ]
}
```

---

### 6.2 Unidades de Medida (Selección de `unit_id` en Ítems)

Permite consultar el catálogo de unidades de medida (Metros, Unidades, Litros, etc.) para poblar selectores al registrar nuevos materiales.

* **Rutas**:
  * Web: `GET /units` (Ruta: `units.search`)
  * API: `GET /api/units`

#### Parámetros Query:
* `search`: Filtra por nombre (`Metros`) o símbolo (`m`, `und`, `kg`).
* `sort_by`: `name`, `symbol`, `created_at`.
* `per_page`: Tamaño de página (ej: `100` para cargar todas en el selector).

#### Respuesta Exitosa (`200 OK`):
```json
{
  "data": [
    {
      "id": 1,
      "name": "Unidades",
      "symbol": "und"
    },
    {
      "id": 2,
      "name": "Metros",
      "symbol": "m"
    },
    {
      "id": 3,
      "name": "Kilogramos",
      "symbol": "kg"
    },
    {
      "id": 4,
      "name": "Galones",
      "symbol": "gal"
    }
  ]
}
```

---

## 7. Manejo de Errores y Códigos de Estado

| Código | Significado | Causa común |
| :--- | :--- | :--- |
| `200 OK` | Petición exitosa | Búsquedas, consultas de detalle, actualizaciones o eliminaciones. |
| `201 Created` | Recurso creado | Creación de categoría, subcategoría, ítem o requerimiento. |
| `202 Accepted` | Puesto en cola | Subida asíncrona de fotografías de ítems. |
| `401 Unauthorized` | No autenticado | Token Sanctum ausente, inválido o expirado. |
| `404 Not Found` | No encontrado | El ID del recurso especificado no existe en la base de datos. |
| `422 Unprocessable Content` | Fallo de validación | Datos requeridos faltantes (`activity_name`, `sub_client_id`), tipos inválidos, o conflicto de integridad (ej: borrar ítem usado). |

### 7.1 Estructura Estándar de Error de Validación (`422 Unprocessable Content`)

Cuando los datos enviados en `POST` o `PUT` no cumplen con las reglas del servidor (por ejemplo, si se envía un `activity_name` vacío, con sólo espacios o sin letras en español), el servidor retorna código HTTP `422` con el siguiente formato JSON:

```json
{
  "message": "El nombre de la actividad es obligatorio. (and 1 more error)",
  "errors": {
    "activity_name": [
      "El nombre de la actividad es obligatorio."
    ],
    "sub_client_id": [
      "Debe seleccionar una tienda o sede (subcliente)."
    ]
  }
}
```

Si el nombre de la actividad contiene únicamente números o caracteres no permitidos:
```json
{
  "message": "El nombre de la actividad debe contener texto explicativo en español y no puede componerse solo de números o símbolos extraños.",
  "errors": {
    "activity_name": [
      "El nombre de la actividad debe contener texto explicativo en español y no puede componerse solo de números o símbolos extraños."
    ]
  }
}
```

---

## 8. Guía de Implementación y Ejemplos de Consumo

### 8.1 Guía de Implementación para Flutter / Dart (Cliente Móvil)

A continuación se presenta una arquitectura recomendada para integrar el módulo de Requerimientos en una aplicación **Flutter** utilizando el paquete estándar `http` (compatible con `dio`).

#### A. Modelos de Datos en Dart

```dart
// lib/models/requirement_model.dart

class SubClientModel {
  final int id;
  final String name;
  final int? clientId;

  SubClientModel({required this.id, required this.name, this.clientId});

  factory SubClientModel.fromJson(Map<String, dynamic> json) {
    return SubClientModel(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      clientId: json['client_id'] as int?,
    );
  }
}

class ItemModel {
  final int id;
  final String sku;
  final String name;
  final String? unitSymbol;
  final String? photoUrl;

  ItemModel({
    required this.id,
    required this.sku,
    required this.name,
    this.unitSymbol,
    this.photoUrl,
  });

  factory ItemModel.fromJson(Map<String, dynamic> json) {
    return ItemModel(
      id: json['id'] as int,
      sku: json['sku'] as String? ?? '',
      name: json['name'] as String? ?? '',
      unitSymbol: json['unit']?['symbol'] as String?,
      photoUrl: json['photo'] as String?,
    );
  }
}

class RequirementItemModel {
  final int id;
  final int requirementId;
  final int itemId;
  final double quantity;
  final ItemModel? item;

  RequirementItemModel({
    required this.id,
    required this.requirementId,
    required this.itemId,
    required this.quantity,
    this.item,
  });

  factory RequirementItemModel.fromJson(Map<String, dynamic> json) {
    return RequirementItemModel(
      id: json['id'] as int,
      requirementId: json['requirement_id'] as int,
      itemId: json['item_id'] as int,
      quantity: (json['quantity'] as num).toDouble(),
      item: json['item'] != null ? ItemModel.fromJson(json['item']) : null,
    );
  }
}

class RequirementModel {
  final int id;
  final int subClientId;
  final SubClientModel? subClient;
  final String activityName;
  final int? createdBy;
  final List<RequirementItemModel> items;
  final int itemsCount;
  final DateTime? createdAt;

  RequirementModel({
    required this.id,
    required this.subClientId,
    this.subClient,
    required this.activityName,
    this.createdBy,
    this.items = const [],
    this.itemsCount = 0,
    this.createdAt,
  });

  factory RequirementModel.fromJson(Map<String, dynamic> json) {
    return RequirementModel(
      id: json['id'] as int,
      subClientId: json['sub_client_id'] as int,
      subClient: json['sub_client'] != null
          ? SubClientModel.fromJson(json['sub_client'])
          : null,
      activityName: json['activity_name'] as String? ?? '',
      createdBy: json['created_by'] as int?,
      items: json['items'] != null
          ? (json['items'] as List)
              .map((i) => RequirementItemModel.fromJson(i as Map<String, dynamic>))
              .toList()
          : [],
      itemsCount: json['items_count'] as int? ?? (json['items'] as List?)?.length ?? 0,
      createdAt: json['created_at'] != null ? DateTime.parse(json['created_at']) : null,
    );
  }
}
```

#### B. Servicio de API en Dart (`RequirementApiService`)

```dart
// lib/services/requirement_api_service.dart

import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/requirement_model.dart';

class RequirementApiException implements Exception {
  final String message;
  final Map<String, dynamic>? errors;
  final int statusCode;

  RequirementApiException({required this.message, this.errors, required this.statusCode});

  @override
  String toString() => 'RequirementApiException ($statusCode): $message';
}

class RequirementApiService {
  final String baseUrl;
  final String token;

  RequirementApiService({
    this.baseUrl = 'https://monitor.sat-industriales.pe/api',
    required this.token,
  });

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  };

  /// 1. Listar Requerimientos con Paginación y Filtros
  Future<List<RequirementModel>> getRequirements({
    int page = 1,
    int perPage = 15,
    String? search,
    int? subClientId,
  }) async {
    final queryParams = {
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (search != null && search.isNotEmpty) 'search': search,
      if (subClientId != null) 'sub_client_id': subClientId.toString(),
    };

    final uri = Uri.parse('$baseUrl/requirements').replace(queryParameters: queryParams);
    final response = await http.get(uri, headers: _headers);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      final List data = json['data'] as List;
      return data.map((e) => RequirementModel.fromJson(e)).toList();
    } else {
      _handleError(response);
      return [];
    }
  }

  /// 2. Consultar Detalle de un Requerimiento
  Future<RequirementModel> getRequirementDetail(int id) async {
    final response = await http.get(Uri.parse('$baseUrl/requirements/$id'), headers: _headers);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return RequirementModel.fromJson(json['data'] ?? json);
    } else {
      _handleError(response);
      throw Exception('Unreachable');
    }
  }

  /// 3. Crear Requerimiento (activity_name es OBLIGATORIO)
  Future<RequirementModel> createRequirement({
    required int subClientId,
    required String activityName,
    List<Map<String, dynamic>> items = const [],
  }) async {
    final trimmedActivity = activityName.trim();
    if (trimmedActivity.isEmpty) {
      throw RequirementApiException(
        message: 'El nombre de la actividad es obligatorio.',
        statusCode: 422,
        errors: {'activity_name': ['El nombre de la actividad es obligatorio.']},
      );
    }

    final payload = {
      'sub_client_id': subClientId,
      'activity_name': trimmedActivity,
      'items': items,
    };

    final response = await http.post(
      Uri.parse('$baseUrl/requirements'),
      headers: _headers,
      body: jsonEncode(payload),
    );

    if (response.statusCode == 201) {
      final json = jsonDecode(response.body);
      return RequirementModel.fromJson(json['data'] ?? json);
    } else {
      _handleError(response);
      throw Exception('Unreachable');
    }
  }

  /// 4. Actualizar Requerimiento
  Future<RequirementModel> updateRequirement(
    int id, {
    int? subClientId,
    String? activityName,
    List<Map<String, dynamic>>? items,
  }) async {
    final payload = <String, dynamic>{};
    if (subClientId != null) payload['sub_client_id'] = subClientId;
    if (activityName != null) {
      final trimmed = activityName.trim();
      if (trimmed.isEmpty) {
        throw RequirementApiException(
          message: 'El nombre de la actividad es obligatorio.',
          statusCode: 422,
          errors: {'activity_name': ['El nombre de la actividad es obligatorio.']},
        );
      }
      payload['activity_name'] = trimmed;
    }
    if (items != null) payload['items'] = items;

    final response = await http.put(
      Uri.parse('$baseUrl/requirements/$id'),
      headers: _headers,
      body: jsonEncode(payload),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return RequirementModel.fromJson(json['data'] ?? json);
    } else {
      _handleError(response);
      throw Exception('Unreachable');
    }
  }

  /// 5. Eliminar Requerimiento
  Future<bool> deleteRequirement(int id) async {
    final response = await http.delete(Uri.parse('$baseUrl/requirements/$id'), headers: _headers);
    if (response.statusCode == 200) {
      return true;
    } else {
      _handleError(response);
      return false;
    }
  }

  /// 6. Agregar o Incrementar Material en Requerimiento Existente
  Future<RequirementItemModel> addItemToRequirement({
    required int requirementId,
    required int itemId,
    required double quantity,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/requirements/$requirementId/items'),
      headers: _headers,
      body: jsonEncode({
        'item_id': itemId,
        'quantity': quantity,
      }),
    );

    if (response.statusCode == 200 || response.statusCode == 201) {
      final json = jsonDecode(response.body);
      return RequirementItemModel.fromJson(json['data'] ?? json);
    } else {
      _handleError(response);
      throw Exception('Unreachable');
    }
  }

  /// 7. Actualizar Cantidad de un Material
  Future<RequirementItemModel> updateItemQuantity({
    required int requirementId,
    required int listItemId,
    required double quantity,
  }) async {
    final response = await http.put(
      Uri.parse('$baseUrl/requirements/$requirementId/items/$listItemId'),
      headers: _headers,
      body: jsonEncode({'quantity': quantity}),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return RequirementItemModel.fromJson(json['data'] ?? json);
    } else {
      _handleError(response);
      throw Exception('Unreachable');
    }
  }

  /// 8. Eliminar un Material de la Lista
  Future<bool> removeItemFromRequirement({
    required int requirementId,
    required int listItemId,
  }) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/requirements/$requirementId/items/$listItemId'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return true;
    } else {
      _handleError(response);
      return false;
    }
  }

  void _handleError(http.Response response) {
    try {
      final body = jsonDecode(response.body);
      final message = body['message'] ?? 'Ocurrió un error en la solicitud.';
      final errors = body['errors'] as Map<String, dynamic>?;
      throw RequirementApiException(message: message, errors: errors, statusCode: response.statusCode);
    } catch (e) {
      if (e is RequirementApiException) rethrow;
      throw RequirementApiException(message: 'Error de servidor (${response.statusCode})', statusCode: response.statusCode);
    }
  }
}
```

---

### 8.2 Flujo en JavaScript (Web / SPA)

```javascript
// 1. Guardar Requerimiento (activity_name obligatorio con .trim())
async function registrarRequerimiento(subClientId, actividad, materiales) {
  const trimmedActivity = (actividad || '').trim();
  if (!trimmedActivity) {
    throw new Error('El nombre de la actividad es obligatorio.');
  }

  const res = await fetch('/api/requirements', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${tokenSanctum}`
    },
    body: JSON.stringify({
      sub_client_id: subClientId,
      activity_name: trimmedActivity,
      items: materiales // [{ item_id: 45, quantity: 10 }]
    })
  });

  const json = await res.json();
  if (!res.ok) {
    if (json.errors) {
      const primerError = Object.values(json.errors)[0];
      throw new Error(Array.isArray(primerError) ? primerError[0] : primerError);
    }
    throw new Error(json.message || 'Error al guardar requerimiento.');
  }

  return json.data;
}
```

---

### 8.3 Ejemplos en cURL

#### Listar Requerimientos con Búsqueda:
```bash
curl -X GET "https://monitor.sat-industriales.pe/api/requirements?search=mantenimiento&per_page=10" \
  -H "Authorization: Bearer <TOKEN_SANCTUM>" \
  -H "Accept: application/json"
```

#### Crear Requerimiento (Con Actividad Obligatoria):
```bash
curl -X POST https://monitor.sat-industriales.pe/api/requirements \
  -H "Authorization: Bearer <TOKEN_SANCTUM>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sub_client_id": 3,
    "activity_name": "Instalación de tablero de distribución",
    "items": [
      { "item_id": 45, "quantity": 30 },
      { "item_id": 46, "quantity": 4 }
    ]
  }'
```

#### Actualizar Cabecera de Requerimiento:
```bash
curl -X PUT https://monitor.sat-industriales.pe/api/requirements/58 \
  -H "Authorization: Bearer <TOKEN_SANCTUM>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "activity_name": "Mantenimiento general actualizado"
  }'
```

---

## 9. Verificación y Pruebas Automatizadas

Todos los endpoints detallados cuentan con tests automatizados que validan sus contratos, validación de campos obligatorios, respuestas HTTP y lógica de negocio transaccional:

```bash
# Tests de API de Inventario y Requerimientos (29 pruebas que cubren validaciones y reglas)
php artisan test --compact tests/Feature/Api/InventoryAndRequirementApiTest.php

# Tests de Paginación en Backend y Filtros de Categoría (5 pruebas)
php artisan test --compact tests/Feature/RequirementListPaginationTest.php

# Tests de Requerimientos y Action Atómica (7 pruebas)
php artisan test --compact tests/Feature/CreateRequirementActionTest.php

# Tests de Almacenamiento S3 y Procesamiento WebP (5 pruebas)
php artisan test --compact tests/Unit/ItemPhotoServiceTest.php

# Tests de Integración de Ítems y S3 (3 pruebas)
php artisan test --compact tests/Feature/ItemControllerS3Test.php

# Tests de Procesamiento Asíncrono en Cola para Fotos de Ítems (6 pruebas)
php artisan test --compact tests/Feature/ItemPhotoQueueTest.php
```

**Resultado total del módulo**: `55+ tests passed con cobertura integral de validaciones, contratos y transacciones`.

---

## 10. Datos de Prueba y Demostración (Seeder)

Se ha creado un Seeder completo e idempotente para poblar la base de datos con un escenario realista de prueba:

```bash
php artisan db:seed --class=RequirementDemoSeeder
```

### Datos Generados:
* **Usuario Demo**: `admin@sat-industriales.pe` / `password`
* **Cliente**: SAT Industriales S.A.C. (`20601234567`)
* **Tiendas / Sedes**:
  * Planta Principal Callao
  * Sede Logística Lurín
  * Oficinas Administrativas San Isidro
* **3 Categorías y 12 Subcategorías**:
  * Materiales Eléctricos (Cables, Interruptores, Tuberías, Luminarias)
  * Ferretería y Estructuras (Pernos, Herramientas, Adhesivos, Perfiles)
  * Equipos de Protección EPP (Cabeza, Visual, Guantes, Calzado)
* **30 Materiales de Catálogo** con códigos SKU realistas (`ELE-CAB-001`, `FER-PER-014`, `EPP-CAB-024`, etc.).
* **3 Requerimientos de Demostración**:
  * **Requerimiento #1**: Con **25 materiales** asociados (ideal para probar paginación de 10 en 10 o 15 en 15, filtros por categoría y búsqueda).
  * **Requerimiento #2**: Con 8 materiales.
  * **Requerimiento #3**: Con 7 materiales.

### URLs Listas para Probar:
* **Paginación (10 por página, página 1)**:
  `GET /api/requirements/11/items?per_page=10&page=1`
* **Página 2**:
  `GET /api/requirements/11/items?per_page=10&page=2`
* **Filtro por Categoría Eléctricos**:
  `GET /api/requirements/11/items?category_id=27`
* **Filtro por Búsqueda (nombre o SKU)**:
  `GET /api/requirements/11/items?search=cable`
* **Catálogo de Búsqueda General con Categoría**:
  `GET /api/items?category_id=28&per_page=12`

