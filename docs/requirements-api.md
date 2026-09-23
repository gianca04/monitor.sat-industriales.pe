# Documentación de API: Catálogo (Categorías, Subcategorías, Ítems) y Requerimientos

Guía técnica integral para el consumo de los endpoints de **Catálogo de Materiales** y **Gestión de Requerimientos** en **Monitor SAT Industriales**.

---

## 1. Arquitectura y Reglas Generales

* **Autenticación Dual**:
  * **API REST (Móvil / Integraciones externas)**: Autenticación vía cabecera `Authorization: Bearer <TOKEN_SANCTUM>`.
  * **Web Interna (Blade / Alpine.js / Livewire)**: Autenticación por sesión Laravel (`auth`) y verificación de token `X-CSRF-TOKEN`.
* **Almacenamiento S3 (MinIO)**: Las fotografías de los ítems se procesan con `Intervention Image`, se convierten a formato optimizado **WebP** y se almacenan en el bucket S3 vía Cloudflare (`https://s3.sat-sistemas.uk`).
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
  * Con Route Binding: `POST /items/{item}/photo/queue` (Nombre: `items.photo.queue`)
  * Vía Payload General: `POST /items/photo/queue` (Nombre: `items.photo.queue.general`)
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

## 5. Endpoints de Requerimientos

Permite registrar solicitudes de materiales vinculando una tienda/sede (`sub_client_id`) con su lista de materiales en una sola operación atómica.

### 5.1 Crear Requerimiento

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

| Parámetro | Tipo | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `sub_client_id` | `integer` | **Sí** | ID de la tienda o sede (`sub_clients.id`). |
| `activity_name` | `string` | No | Nombre o motivo de la actividad (máx. 255 caracteres). |
| `items` | `array` | No | Lista de materiales a asociar. Si se omite o está vacío, crea solo la cabecera. |
| `items.*.item_id` o `id` | `integer` | Condicional | ID del ítem en catálogo (`items.id`). Requerido si se envían ítems. |
| `items.*.quantity` | `numeric` | Condicional | Cantidad solicitada. Valor mínimo permitido: `0.01`. |

#### Respuesta Exitosa (`201 Created`):
```json
{
  "data": {
    "id": 58,
    "activity_name": "Mantenimiento preventivo subestación norte",
    "sub_client": {
      "id": 3,
      "name": "Planta Principal Callao",
      "client": {
        "id": 1,
        "name": "SAT Industriales S.A.C."
      }
    },
    "created_by": {
      "id": 2,
      "name": "Giancarlo Ingeniero",
      "email": "giancarlo@sat-industriales.pe"
    },
    "requirement_lists": [
      {
        "id": 140,
        "requirement_id": 58,
        "item_id": 45,
        "quantity": 12.5,
        "item": {
          "id": 45,
          "name": "Cable Vulcanizado 3x14 AWG Indeco",
          "sku": "ELE-CAB-0045",
          "unit": {
            "id": 2,
            "name": "Metros",
            "symbol": "m"
          }
        }
      },
      {
        "id": 141,
        "requirement_id": 58,
        "item_id": 46,
        "quantity": 5,
        "item": {
          "id": 46,
          "name": "Cinta Aislante 3M Super 33+",
          "sku": "MAT-0046",
          "unit": {
            "id": 1,
            "name": "Unidades",
            "symbol": "und"
          }
        }
      }
    ],
    "requirement_lists_count": 2,
    "created_at": "2026-09-23T15:58:00.000000Z",
    "updated_at": "2026-09-23T15:58:00.000000Z"
  }
}
```

---

### 5.2 Listado, Detalle y Modificación de Requerimientos

* **Rutas**:
  * API: `GET /api/requirements` (Listar con filtros)
  * API: `GET /api/requirements/{id}` (Consultar detalle)
  * API: `PUT /api/requirements/{id}` (Actualizar cabecera)
  * API: `DELETE /api/requirements/{id}` (Eliminar requerimiento)

#### Parámetros Query para `GET /api/requirements`:

| Parámetro | Tipo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- |
| `search` | `string` | — | Busca coincidencia en el nombre de la actividad (`activity_name`) o en el nombre de la tienda (`subClient.name`). |
| `sub_client_id` | `integer` | — | Filtra requerimientos de una tienda/sede específica. |
| `sort_by` | `string` | `created_at` | Campos: `id`, `activity_name`, `created_at`. |
| `sort_order` | `string` | `desc` | `asc` o `desc`. |
| `per_page` | `integer` | `15` | Registros por página. |

---

### 5.3 Gestión Granular de Materiales en Requerimientos (`requirements.items`)

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
| `200 OK` | Petición exitosa | Búsquedas, consultas de detalle o eliminaciones. |
| `201 Created` | Recurso creado | Creación de categoría, subcategoría, ítem o requerimiento. |
| `202 Accepted` | Puesto en cola | Subida asíncrona de fotografías de ítems. |
| `401 Unauthorized` | No autenticado | Token ausente, inválido o sesión web expirada. |
| `422 Unprocessable Content` | Fallo de validación | Datos requeridos faltantes, tipo inválido o conflicto de integridad (ej: borrar ítem usado). |
| `404 Not Found` | No encontrado | El ID del recurso especificado no existe en la base de datos. |

---

## 8. Ejemplos Prácticos de Consumo

### 8.1 Flujo Completo en JavaScript (Catálogo y Creación de Requerimiento)

```javascript
// 1. Buscar materiales por texto o código SKU
async function buscarMateriales(termino) {
  const res = await fetch(`/api/items?search=${encodeURIComponent(termino)}&per_page=10`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${tokenSanctum}`
    }
  });
  const json = await res.json();
  return json.data; // [{ id: 45, sku: '...', name: '...', unit: { ... } }]
}

// 2. Crear una nueva subcategoría sobre la marcha
async function crearSubcategoria(categoryId, nombre) {
  const res = await fetch('/api/subcategories', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${tokenSanctum}`
    },
    body: JSON.stringify({ category_id: categoryId, name: nombre })
  });
  return await res.json();
}

// 3. Crear un ítem con foto (Multipart)
async function crearItemConFoto(nombre, subcategoryId, unitId, fotoFile) {
  const formData = new FormData();
  formData.append('name', nombre);
  formData.append('subcategory_id', subcategoryId);
  if (unitId) formData.append('unit_id', unitId);
  if (fotoFile) formData.append('photo', fotoFile);

  const res = await fetch('/api/items', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${tokenSanctum}`
    },
    body: formData
  });
  return await res.json();
}

// 4. Guardar Requerimiento con los materiales seleccionados
async function registrarRequerimiento(subClientId, actividad, materiales) {
  const res = await fetch('/api/requirements', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${tokenSanctum}`
    },
    body: JSON.stringify({
      sub_client_id: subClientId,
      activity_name: actividad,
      items: materiales // [{ id: 45, quantity: 10 }, { id: 46, quantity: 2 }]
    })
  });
  return await res.json();
}
```

---

### 7.2 Ejemplos en cURL

#### Buscar Ítems:
```bash
curl -X GET "https://monitor.sat-industriales.pe/api/items?search=cable&per_page=5" \
  -H "Authorization: Bearer <TOKEN_SANCTUM>" \
  -H "Accept: application/json"
```

#### Crear Ítem con Foto hacia MinIO S3:
```bash
curl -X POST https://monitor.sat-industriales.pe/api/items \
  -H "Authorization: Bearer <TOKEN_SANCTUM>" \
  -H "Accept: application/json" \
  -F "name=Interruptor Termomagnético 3x40A" \
  -F "subcategory_id=12" \
  -F "unit_id=1" \
  -F "photo=@/ruta/a/interruptor.jpg"
```

#### Crear Requerimiento:
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

---

## 9. Verificación y Pruebas Automatizadas

Todos los endpoints detallados cuentan con tests automatizados que validan sus contratos, respuestas HTTP y lógica de negocio:

```bash
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

**Resultado total del módulo**: `26 passed (214 assertions)`.

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

