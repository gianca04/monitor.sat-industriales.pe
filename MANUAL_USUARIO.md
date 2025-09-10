# Manual de Usuario - Sistema de Monitoreo SAT Industriales

**Versión:** 1.0  
**Fecha de Actualización:** Diciembre 2024  
**Empresa:** SAT Industriales S.A.C.

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Módulo de Gestión de Clientes](#módulo-de-gestión-de-clientes)
4. [Módulo de Recursos Humanos](#módulo-de-recursos-humanos)
5. [Módulo de Control de Operaciones](#módulo-de-control-de-operaciones)
6. [Módulo de Tareos y Asistencias](#módulo-de-tareos-y-asistencias)
7. [Gestión de Usuarios y Roles](#gestión-de-usuarios-y-roles)
8. [Reportes y Estadísticas](#reportes-y-estadísticas)
9. [Funciones Especiales](#funciones-especiales)
10. [Solución de Problemas](#solución-de-problemas)
11. [Anexos](#anexos)

---

## 1. Introducción

### 1.1 Propósito del Sistema

El Sistema de Monitoreo SAT Industriales es una plataforma empresarial integral desarrollada con tecnología Laravel y Filament PHP, diseñada específicamente para optimizar la gestión operativa de proyectos industriales. La plataforma centraliza el control de clientes, recursos humanos, cotizaciones, proyectos y el seguimiento detallado de asistencias laborales.

### 1.2 Arquitectura del Sistema

El sistema está construido sobre una arquitectura modular que incluye:

- **Backend:** Laravel Framework con Filament Admin Panel
- **Base de Datos:** MySQL con estructura relacional optimizada
- **Frontend:** Interfaz responsive basada en Livewire y Alpine.js
- **Componentes:** Sistema de widgets, relation managers y recursos modulares

### 1.3 Módulos Principales

| Módulo | Descripción | Funcionalidad Principal |
|--------|-------------|------------------------|
| Gestión de Clientes | Administración de clientes y sedes | CRUD completo, gestión de subclientes |
| Recursos Humanos | Control de empleados y usuarios | Gestión de personal, creación de usuarios |
| Control de Operaciones | Cotizaciones y proyectos | Flujo comercial completo |
| Tareos y Asistencias | Control laboral diario | Registro de asistencias y horarios |
| Reportes | Dashboard y análisis | Estadísticas en tiempo real |

---

## 2. Acceso al Sistema

### 2.1 Proceso de Autenticación

#### 2.1.1 Credenciales de Acceso
1. Acceder a la URL del sistema proporcionada por el administrador del sistema
2. Introducir credenciales válidas:
   - **Usuario:** Dirección de correo electrónico corporativo
   - **Contraseña:** Clave asignada por el administrador
3. Hacer clic en el botón "Iniciar Sesión"

#### 2.1.2 Niveles de Seguridad
- Validación de credenciales mediante Laravel Sanctum
- Sesiones encriptadas con tiempo de expiración configurable
- Registro de actividad de usuarios para auditoría

### 2.2 Dashboard Principal

#### 2.2.1 Componentes del Dashboard

El panel principal presenta una vista consolidada de la información operativa mediante widgets especializados:

**Widgets de Estadísticas Generales:**
```
┌─────────────────────────────────────────────────────────┐
│ Total Clientes    │ Total Empleados   │ Total Proyectos │
│      [123]        │       [45]        │      [78]       │
├─────────────────────────────────────────────────────────┤
│ Clientes Activos  │ Empleados Activos │ Proyectos Act.  │
│      [89]         │       [42]        │      [23]       │
└─────────────────────────────────────────────────────────┘
```

**Widgets de Control Operativo:**
```
┌─────────────────────────────────────────────────────────┐
│ Tareos Hoy       │ Asistencias Hoy   │ Cotizaciones    │
│      [12]        │       [156]       │ Pendientes [8]  │
├─────────────────────────────────────────────────────────┤
│ Presentes        │ Ausentes          │ Justificados    │
│      [142]       │       [8]         │      [6]        │
└─────────────────────────────────────────────────────────┘
```

#### 2.2.2 Navegación Principal

La navegación está organizada por grupos funcionales:

- **Gestión de Clientes**
  - Clientes
  - Sub-clientes (gestión desde relation manager)

- **Recursos Humanos**
  - Colaboradores
  - Usuarios del Sistema

- **Control de Operaciones**
  - Cotizaciones
  - Proyectos
  - Tareos

- **Sistema**
  - Roles y Permisos
  - Configuración

---

## 3. Módulo de Gestión de Clientes

### 3.1 Características del Módulo

El módulo de gestión de clientes implementa un sistema completo de Customer Relationship Management (CRM) con las siguientes capacidades:

#### 3.1.1 Funcionalidades Core
- Registro completo de información empresarial y personal
- Sistema de validación de documentos de identidad
- Gestión jerárquica de sedes mediante relation managers
- Historial completo de interacciones comerciales
- Integración con módulo de cotizaciones y proyectos

#### 3.1.2 Arquitectura de Datos

```
Cliente (Tabla: clients)
├── Información Principal
│   ├── document_type (enum)
│   ├── document_number (unique)
│   ├── person_type (enum)
│   ├── business_name
│   └── contact_information
├── Sub-clientes (Relación 1:N)
│   ├── name
│   ├── location
│   ├── coordinates (lat, lng)
│   └── description
└── Proyectos (Relación N:M via cotizaciones)
    └── Historial de proyectos ejecutados
```

### 3.2 Procedimientos Operativos

#### 3.2.1 Creación de Cliente

**Proceso Step-by-Step:**

1. **Navegación al Módulo**
   ```
   Menu Principal → Gestión de clientes → Clientes → [Nuevo Cliente]
   ```

2. **Formulario Principal (Tabs Component)**

   **Tab 1: Datos del Cliente**
   
   | Campo | Tipo | Validación | Descripción |
   |-------|------|------------|-------------|
   | Tipo de Documento | Select | Required | DNI, RUC, CE, Pasaporte |
   | Número de Documento | TextInput | Required, Unique | Identificación única |
   | Tipo de Persona | Select | Required | Natural, Jurídica |
   | Razón Social | TextInput | Required | Denominación oficial |
   | Dirección | Textarea | Optional | Domicilio fiscal/comercial |
   | Teléfono | TextInput | Optional | Número de contacto principal |
   | Email | TextInput | Email validation | Correo electrónico |
   | Logo | FileUpload | Optional | Imagen corporativa |

   **Tab 2: Subclientes (Relation Manager)**
   
   Esta pestaña implementa un Relation Manager que permite gestionar múltiples sedes:
   
   ```php
   // Componente: SubClientsRelationManager
   protected static string $relationship = 'subClients';
   ```

#### 3.2.2 Gestión de Sub-clientes

El sistema utiliza un **HasMany Relation Manager** para gestionar sedes o sucursales:

**Estructura del Relation Manager:**
```
SubClients Relation Manager
├── Formulario Inline
│   ├── Nombre de sede
│   ├── Descripción
│   ├── Ubicación (texto)
│   └── Coordenadas (componente mapa)
├── Tabla de Gestión
│   ├── CRUD completo
│   ├── Filtros por ubicación
│   └── Acciones bulk
└── Validaciones
    ├── Nombres únicos por cliente
    └── Coordenadas válidas
```

### 3.3 Funciones Avanzadas

#### 3.3.1 Sistema de Búsqueda

**Búsqueda Global:**
- Implementada en `ClientResource::getGloballySearchableAttributes()`
- Campos indexados: `business_name`, `document_number`
- Resultados limitados a 10 para optimización

**Filtros Especializados:**
```php
Tables\Filters\SelectFilter::make('document_type')
    ->options([
        'RUC' => 'RUC',
        'DNI' => 'DNI',
        'FOREIGN_CARD' => 'Carné de Extranjería',
        'PASSPORT' => 'Pasaporte',
    ])
```

#### 3.3.2 Validaciones del Sistema

| Validación | Implementación | Propósito |
|------------|----------------|-----------|
| Document Unique | Laravel Rule | Evitar duplicados |
| Email Format | Email validation | Integridad de datos |
| RUC Format | Custom rule | Validación tributaria |
| Logo File Type | Image validation | Seguridad de archivos |

---

## 4. Módulo de Recursos Humanos

### 4.1 Arquitectura del Módulo de Empleados

El módulo de Recursos Humanos gestiona la información del personal de la empresa mediante una arquitectura dual que separa los datos del empleado de las credenciales del sistema:

#### 4.1.1 Modelo de Datos

```
Employee (Tabla: employees)
├── Información Personal
│   ├── document_type (enum)
│   ├── document_number (unique)
│   ├── first_name, last_name
│   ├── date_birth, date_contract
│   ├── address, sex
│   └── full_name (accessor)
├── Relación Usuario (Opcional)
│   └── user_id (FK a users table)
├── Relaciones Operativas
│   ├── attendances (HasMany)
│   ├── timesheets (HasMany)
│   └── quotes (HasMany)
└── Funciones Computed
    └── getFullNameAttribute()
```

#### 4.1.2 Características del Módulo

**Funcionalidades Core:**
- Gestión completa del ciclo de vida del empleado
- Creación condicional de usuarios del sistema
- Exportación de datos mediante `EmployeeExporter`
- Búsqueda global optimizada
- Políticas de acceso granular mediante `EmployeePolicy`

### 4.2 Procedimientos de Gestión

#### 4.2.1 Registro de Nuevo Colaborador

**Proceso Completo:**

1. **Acceso al Formulario**
   ```
   Menu Principal → Recursos Humanos → Colaboradores → [Nuevo Colaborador]
   ```

2. **Estructura del Formulario (Tabs Component)**

   **Tab 1: Información del Empleado**
   
   | Campo | Componente | Validación | Observaciones |
   |-------|------------|------------|---------------|
   | Nombres | TextInput | Required, String | Solo caracteres alfabéticos |
   | Apellidos | TextInput | Required, String | Solo caracteres alfabéticos |
   | Tipo Documento | Select | Required, Enum | DNI, CE, Pasaporte |
   | Nº Documento | TextInput | Required, Unique | Validación por tipo |
   | Fecha Nacimiento | DatePicker | Required, Past | Edad mínima 18 años |
   | Fecha Contrato | DatePicker | Required | No puede ser futura |
   | Dirección | Textarea | Optional | Domicilio actual |
   | Sexo | Select | Required | M/F |

   **Tab 2: Información del Usuario (Condicional)**
   
   Esta pestaña implementa creación condicional de credenciales:
   
   ```php
   Toggle::make('create_user')
       ->label('¿Crear Usuario del Sistema?')
       ->reactive()
       ->afterStateUpdated(fn ($set) => $set('user', null))
   ```

   | Campo | Componente | Condición | Validación |
   |-------|------------|-----------|------------|
   | Crear Usuario | Toggle | Always | Boolean |
   | Email | TextInput | If create_user | Email, Unique |
   | Contraseña | TextInput | If create_user | Min 8 chars |
   | Rol | Select | If create_user | Exists in roles |
   | Estado | Toggle | If create_user | Boolean |

#### 4.2.2 Lógica de Creación de Usuario

El sistema implementa lógica condicional en `CreateEmployee::handleRecordCreation()`:

```php
protected function handleRecordCreation(array $data): Model
{
    return DB::transaction(function () use ($data) {
        if (isset($data['user']) && ($data['user']['is_active'] ?? false)) {
            $userData = $data['user'];
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'password' => Hash::make($userData['password']),
                'is_active' => true,
            ]);
            $data['user_id'] = $user->id;
        }
        unset($data['user']);
        return static::getModel()::create($data);
    });
}
```

### 4.3 Funciones Especializadas

#### 4.3.1 Sistema de Exportación

**EmployeeExporter Implementation:**
- Formato: Excel (.xlsx)
- Campos exportados: Información completa del empleado
- Activación: HeaderAction en tabla principal
- Configuración: `ExportAction::make()->exporter(EmployeeExporter::class)`

#### 4.3.2 Búsqueda Global Optimizada

**Configuración de Búsqueda:**
```php
protected static int $globalSearchResultsLimit = 10;

public static function getGloballySearchableAttributes(): array
{
    return ['first_name', 'last_name', 'document_number'];
}

public static function getGlobalSearchEloquentQuery(): Builder
{
    return parent::getGlobalSearchEloquentQuery()
        ->with('user')
        ->select(['id', 'first_name', 'last_name', 'document_number']);
}
```

#### 4.3.3 Validaciones del Sistema

| Validación | Nivel | Implementación | Propósito |
|------------|-------|----------------|-----------|
| Document Unique | Database | Laravel Rule | Prevenir duplicados |
| Email Unique | Database | Laravel Rule | Usuarios únicos |
| Age Validation | Business | Custom Rule | Personal mayor de edad |
| Contract Date | Business | Custom Rule | Fecha válida de contrato |

---

## 5. Módulo de Control de Operaciones

### 5.1 Gestión de Cotizaciones

#### 5.1.1 Arquitectura del Módulo

El módulo de cotizaciones implementa un workflow completo de gestión comercial:

```
Quote (Tabla: quotes)
├── Relaciones Principales
│   ├── client_id (BelongsTo Client)
│   ├── sub_client_id (BelongsTo SubClient)
│   └── employee_id (BelongsTo Employee)
├── Información Comercial
│   ├── correlative (unique, auto)
│   ├── project_description
│   ├── contractor, pe_pt
│   ├── delivery_term (date)
│   └── status (enum)
├── Archivos del Proceso
│   ├── TDR (FileUpload)
│   └── quote_file (FileUpload)
├── Ubicación del Proyecto
│   ├── location (text)
│   ├── latitude, longitude
│   └── ubicacion (custom component)
└── Control de Estado
    ├── status (workflow)
    └── comment (observations)
```

#### 5.1.2 Estados del Workflow

**Diagrama de Estados:**
```
    [Pendiente] 
        ↓
    [En Revisión] 
       ↙     ↘
[Aprobada]  [Rechazada]
     ↓
[Proyecto Creado]
```

| Estado | Descripción | Transiciones Permitidas | Acciones Disponibles |
|--------|-------------|------------------------|---------------------|
| Pendiente | Estado inicial | → En Revisión | Editar, Revisar |
| En Revisión | Cliente evaluando | → Aprobada, Rechazada | Aprobar, Rechazar |
| Aprobada | Aceptada por cliente | → Proyecto | Crear Proyecto |
| Rechazada | No aceptada | → Pendiente (re-work) | Archivar, Re-trabajar |

#### 5.1.3 Componente de Ubicación

El sistema implementa un componente custom para gestión geográfica:

```php
\App\Forms\Components\ubicacion::make('location')
    ->columnSpanFull()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set) {
        // Auto-populate coordinates
        if ($state && isset($state['coordinates'])) {
            $set('latitude', $state['coordinates']['lat']);
            $set('longitude', $state['coordinates']['lng']);
        }
    })
```

### 5.2 Gestión de Proyectos

#### 5.2.1 Arquitectura del Módulo

```
Project (Tabla: projects)
├── Relación Base
│   └── quote_id (BelongsTo Quote)
├── Información del Proyecto
│   ├── name
│   ├── start_date, end_date
│   └── is_active (computed)
├── Ubicación
│   ├── location_address
│   ├── latitude, longitude
│   └── coordinates (formatted)
├── Relation Managers
│   └── TimesheetsRelationManager
└── Estados Automáticos
    ├── Active (current date in range)
    ├── Upcoming (start date future)
    └── Completed (end date past)
```

#### 5.2.2 TimesheetsRelationManager

El proyecto implementa un Relation Manager especializado para gestión de tareos:

**Estructura del Relation Manager:**
```php
class TimesheetsRelationManager extends RelationManager
{
    protected static string $relationship = 'timesheets';
    protected static ?string $title = 'Tareos';
    protected static ?string $modelLabel = 'tareo';
    protected static ?string $pluralModelLabel = 'tareos';
}
```

**Funcionalidades del Manager:**
- CRUD completo de tareos desde el proyecto
- Validación de unicidad (un tareo por día)
- Creación de asistencias desde el tareo
- Filtros especializados por turno y empleado
- Acciones bulk para gestión masiva

#### 5.2.3 Estados Automáticos del Proyecto

**Lógica de Cálculo:**
```php
Tables\Filters\SelectFilter::make('status')
    ->query(function (Builder $query, array $data): Builder {
        $now = now()->toDateString();
        
        return match($data['value']) {
            'active' => $query->where('start_date', '<=', $now)
                             ->where(function($q) use ($now) {
                                 $q->whereNull('end_date')
                                   ->orWhere('end_date', '>=', $now);
                             }),
            'upcoming' => $query->where('start_date', '>', $now),
            'completed' => $query->where('end_date', '<', $now),
            default => $query
        };
    })
```

---

## 6. Módulo de Tareos y Asistencias

### 6.1 Arquitectura del Sistema de Control Laboral

El módulo de tareos y asistencias implementa un sistema de control laboral basado en una estructura jerárquica donde cada proyecto puede tener múltiples tareos, y cada tareo gestiona las asistencias de múltiples empleados.

#### 6.1.1 Modelo de Datos

```
Timesheet (Tabla: timesheets)
├── Relaciones Principales
│   ├── project_id (BelongsTo Project)
│   └── employee_id (Supervisor)
├── Control Temporal
│   ├── check_in_date (unique per project)
│   ├── break_date, end_break_date
│   ├── check_out_date
│   └── shift (enum: day, night)
├── Relation Manager
│   └── AttendancesRelationManager
├── Validaciones
│   ├── Unique per project per date
│   ├── Logical time sequence
│   └── Active project only
└── Computed Properties
    ├── total_hours
    ├── break_duration
    └── attendances_summary
```

#### 6.1.2 Validaciones del Sistema

**Boot Method Implementation:**
```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($timesheet) {
        $existingTimesheet = static::where('project_id', $timesheet->project_id)
            ->whereDate('check_in_date', Carbon::parse($timesheet->check_in_date)->toDateString())
            ->first();
            
        if ($existingTimesheet) {
            throw new \Exception('Ya existe un tareo para este proyecto en la fecha seleccionada.');
        }
    });
}
```

### 6.2 Gestión de Tareos

#### 6.2.1 Proceso de Creación

**Validación Previa:**
El sistema implementa validación estricta mediante `TimesheetResource::validateUniqueTimesheetForProjectDate()`:

```php
public static function validateUniqueTimesheetForProjectDate($projectId, $checkInDate, $excludeId = null)
{
    $query = Timesheet::where('project_id', $projectId)
        ->whereDate('check_in_date', Carbon::parse($checkInDate)->toDateString());
        
    if ($excludeId) {
        $query->where('id', '!=', $excludeId);
    }
    
    return $query->exists();
}
```

**Estructura del Formulario:**
```
Section: Datos del registro de asistencia
├── Column 1
│   ├── Project Selection (Required)
│   ├── Employee Selection (Supervisor)
│   └── Date Selection (Required)
├── Column 2
│   ├── Shift Selection (day/night)
│   ├── Check-in Time
│   └── Check-out Time
└── Break Times Section
    ├── Break Start Time
    └── Break End Time
```

#### 6.2.2 Estados y Filtros

**Estados del Tareo:**
| Estado | Condición | Indicador Visual | Descripción |
|--------|-----------|------------------|-------------|
| Único del día | No conflicts | ✅ Color success | Único tareo para proyecto/fecha |
| Conflicto | Duplicate found | ⚠️ Color danger | Posible duplicado detectado |

**Filtros Disponibles:**
```php
Tables\Filters\SelectFilter::make('project_id')
    ->label('Proyecto')
    ->relationship('project', 'name')
    ->searchable()
    ->preload()

Tables\Filters\Filter::make('hoy')
    ->query(fn (Builder $query): Builder => 
        $query->whereDate('check_in_date', Carbon::today())
    )
    ->toggle()

Tables\Filters\Filter::make('conflictos')
    ->query(fn (Builder $query): Builder => 
        $query->whereHas('project.timesheets', function($q) {
            $q->whereColumn('check_in_date', 'timesheets.check_in_date')
              ->where('id', '!=', DB::raw('timesheets.id'));
        })
    )
    ->toggle()
```

### 6.3 AttendancesRelationManager

#### 6.3.1 Arquitectura del Relation Manager

```php
class AttendancesRelationManager extends RelationManager
{
    use Translatable;
    protected static string $relationship = 'attendances';
    protected static ?string $pluralModelLabel = 'Asistencias';
    protected static ?string $modelLabel = 'Asistencia';
    protected static ?string $title = 'Asistencias';
}
```

#### 6.3.2 Modelo de Asistencias

```
Attendance (Tabla: attendances)
├── Relaciones
│   ├── timesheet_id (BelongsTo Timesheet)
│   └── employee_id (BelongsTo Employee)
├── Control de Estado
│   ├── status (enum: attended, late, absent, justified)
│   ├── shift (day, night)
│   └── observation (text)
├── Horarios Individuales
│   ├── check_in_date
│   ├── break_date, end_break_date
│   └── check_out_date
├── Soft Deletes
│   └── deleted_at
└── Computed Fields
    ├── work_duration
    └── extra_hours
```

#### 6.3.3 Estados de Asistencia

**Workflow de Estados:**
```
Employee Assignment
        ↓
    [attended] ←→ [late]
        ↓           ↓
    Work Hours   Work Hours
        ↓           ↓
     Complete    Complete
        
    [absent] → No Work Hours
        ↓
    [justified] → With Observation
```

| Estado | Código | Requiere Horarios | Color Badge | Descripción |
|--------|--------|------------------|-------------|-------------|
| Asistió | `attended` | Sí | success | Jornada completa normal |
| Presente | `present` | Sí | success | Variante de asistencia |
| Llegó Tarde | `late` | Sí | warning | Asistió fuera de horario |
| Faltó | `absent` | No | danger | No se presentó |
| Justificado | `justified` | No | info | Ausencia autorizada |

#### 6.3.4 Funciones Especializadas

**Generación Masiva de Asistencias:**
```php
Tables\Actions\Action::make('generarAsistencias')
    ->label('Generar Asistencias Masivas')
    ->icon('heroicon-o-users')
    ->form([
        Select::make('employees')
            ->multiple()
            ->options(Employee::pluck('full_name', 'id'))
            ->required(),
        Select::make('default_status')
            ->options([
                'attended' => 'Asistió',
                'absent' => 'Faltó'
            ])
            ->default('attended'),
        Select::make('default_shift')
            ->options([
                'day' => 'Día',
                'night' => 'Noche'
            ])
            ->default('day')
    ])
    ->action(function (array $data, Timesheet $record) {
        foreach ($data['employees'] as $employeeId) {
            Attendance::updateOrCreate(
                [
                    'timesheet_id' => $record->id,
                    'employee_id' => $employeeId
                ],
                [
                    'status' => $data['default_status'],
                    'shift' => $data['default_shift']
                ]
            );
        }
    })
```

**SelectColumn para Edición Inline:**
```php
Tables\Columns\SelectColumn::make('status')
    ->options([
        'attended' => 'Asistió',
        'late' => 'Llegó Tarde',
        'absent' => 'Faltó',
        'justified' => 'Justificado'
    ])
    ->afterStateUpdated(function ($record, $state) {
        Notification::make()
            ->title('Estado actualizado')
            ->body("Estado de {$record->employee->full_name} cambiado a {$state}")
            ->success()
            ->send();
    })
```

### 6.4 Importación y Exportación

#### 6.4.1 Sistema de Importación Excel

**AttendancesImport Implementation:**
```php
class AttendancesImport implements ToModel, WithHeadingRow
{
    private $timesheetId;
    
    public function model(array $row)
    {
        return new Attendance([
            'timesheet_id' => $this->timesheetId,
            'employee_id' => $this->findEmployeeByDocument($row['documento']),
            'status' => $this->mapStatus($row['estado']),
            'shift' => $row['turno'] ?? 'day',
            'observation' => $row['observacion'] ?? null
        ]);
    }
}
```

#### 6.4.2 Plantilla de Exportación

**AttendanceTemplateExport Structure:**
```
| Documento | Nombres | Apellidos | Estado | Turno | Entrada | Salida | Observación |
|-----------|---------|-----------|--------|-------|---------|--------|-------------|
| 12345678  | Juan    | Pérez     | asistió| día   | 08:00   | 17:00  |             |
```

---

## 7. Gestión de Usuarios y Roles

### 7.1 Sistema de Autenticación y Autorización

El sistema implementa un modelo de seguridad basado en **Filament Shield** que proporciona control granular de acceso a través de roles y permisos específicos por recurso.

#### 7.1.1 Arquitectura de Seguridad

```
User Authentication System
├── Laravel Sanctum (API Tokens)
├── Filament Shield (RBAC)
└── Custom Policies per Resource

Role-Based Access Control (RBAC)
├── Roles (roles table)
│   ├── name (unique)
│   ├── guard_name
│   └── permissions (M:N relationship)
├── Permissions (permissions table)
│   ├── Resource-based permissions
│   ├── Action-based permissions
│   └── Guard-specific permissions
└── User Role Assignment
    ├── model_has_roles table
    └── Direct assignment interface
```

### 7.2 RoleResource Implementation

#### 7.2.1 Características del Módulo

```php
class RoleResource extends Resource implements HasShieldPermissions
{
    use HasShieldFormComponents;
    use Translatable;
    
    protected static ?string $recordTitleAttribute = 'name';
    protected static int $globalSearchResultsLimit = 10;
}
```

**Funcionalidades Core:**
- Gestión completa de roles del sistema
- Asignación granular de permisos por recurso
- Interfaz de configuración mediante `ShieldSelectAllToggle`
- Búsqueda global optimizada
- Políticas de acceso mediante `HasShieldPermissions`

#### 7.2.2 Sistema de Permisos

**Prefijos de Permisos Estándar:**
```php
public static function getPermissionPrefixes(): array
{
    return [
        'view',           // Visualizar registros individuales
        'view_any',       // Listar todos los registros
        'create',         // Crear nuevos registros
        'update',         // Modificar registros existentes
        'delete',         // Eliminar registros individuales
        'delete_any',     // Eliminación masiva
    ];
}
```

**Matriz de Permisos por Recurso:**

| Recurso | view | view_any | create | update | delete | delete_any |
|---------|------|----------|--------|--------|--------|------------|
| Client | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Employee | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Quote | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Project | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Timesheet | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Role | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

#### 7.2.3 Formulario de Gestión de Roles

**Shield Form Components:**
```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Grid::make()->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->label('Nombre del Rol'),
            
            Forms\Components\Select::make('guard_name')
                ->options(['web' => 'Web'])
                ->default('web')
                ->required()
                ->label('Guard'),
        ]),
        
        static::getShieldFormComponents(),
    ]);
}
```

**ShieldSelectAllToggle Implementation:**
El componente permite selección masiva de permisos con control granular:

```
┌─────────────────────────────────────────┐
│ Seleccionar Todos los Permisos          │
│ ┌─────┐ Super Admin (todos los permisos) │
│ │  ☑  │                                 │
│ └─────┘                                 │
├─────────────────────────────────────────┤
│ Cliente                                 │
│ ☑ Ver  ☑ Ver Todos  ☑ Crear           │
│ ☑ Editar  ☑ Eliminar  ☑ Eliminar Todos │
├─────────────────────────────────────────┤
│ Empleado                                │
│ ☑ Ver  ☑ Ver Todos  ☐ Crear           │
│ ☐ Editar  ☐ Eliminar  ☐ Eliminar Todos │
└─────────────────────────────────────────┘
```

### 7.3 Políticas de Acceso

#### 7.3.1 Policy Structure

Cada recurso implementa una política específica:

```php
// Ejemplo: ClientPolicy
class ClientPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_client');
    }
    
    public function view(User $user, Client $client): bool
    {
        return $user->can('view_client');
    }
    
    public function create(User $user): bool
    {
        return $user->can('create_client');
    }
    
    // ... más métodos de autorización
}
```

#### 7.3.2 Integración con Filament

**Registro Automático:**
Las políticas se registran automáticamente en `AuthServiceProvider`:

```php
protected $policies = [
    Client::class => ClientPolicy::class,
    Employee::class => EmployeePolicy::class,
    Quote::class => QuotePolicy::class,
    Project::class => ProjectPolicy::class,
    Timesheet::class => TimesheetPolicy::class,
    Role::class => RolePolicy::class,
];
```

### 7.4 Configuración de Roles Predefinidos

#### 7.4.1 Roles del Sistema

| Rol | Descripción | Permisos Principales | Casos de Uso |
|-----|-------------|---------------------|--------------|
| Super Admin | Acceso total | Todos los permisos | Administración del sistema |
| Administrador | Gestión operativa | CRUD en módulos principales | Gestión diaria |
| Supervisor | Control de proyectos | Tareos, asistencias, reportes | Supervisión de obra |
| Operador | Consulta básica | Solo lectura en módulos asignados | Consulta de información |
| Cotizador | Gestión comercial | CRUD cotizaciones, clientes | Área comercial |

#### 7.4.2 Configuración Recomendada

**Super Admin:**
```php
'permissions' => [
    '*' // Todos los permisos
]
```

**Supervisor de Proyecto:**
```php
'permissions' => [
    'view_any_project',
    'view_project',
    'view_any_timesheet',
    'create_timesheet',
    'update_timesheet',
    'view_any_attendance',
    'create_attendance',
    'update_attendance',
]
```

**Cotizador:**
```php
'permissions' => [
    'view_any_client',
    'create_client',
    'update_client',
    'view_any_quote',
    'create_quote',
    'update_quote',
    'view_any_employee',
]
```

---

## 8. Reportes y Estadísticas

### 8.1 Sistema de Dashboard y Widgets

El sistema implementa un dashboard modular basado en widgets especializados que proporcionan métricas en tiempo real y análisis de tendencias operativas.

#### 8.1.1 Arquitectura de Widgets

```
Dashboard Widget System
├── OverviewStatsWidget (Estadísticas generales)
├── ClientStatsWidget (Métricas de clientes)
├── EmployeeStatsWidget (Métricas de empleados)
├── ProjectStatsWidget (Métricas de proyectos)
├── GeneralTimesheetStatsWidget (Control laboral)
└── TimesheetStatsWidget (Widget específico por tareo)
```

#### 8.1.2 OverviewStatsWidget Implementation

```php
class OverviewStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Clientes', Client::count())
                ->description('Total de clientes')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
                
            Stat::make('Empleados', Employee::count())
                ->description('Total de empleados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Proyectos', Project::count())
                ->description('Total de proyectos')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),
                
            Stat::make('Cotizaciones', Quote::count())
                ->description('Total de cotizaciones')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
                
            Stat::make('Tareos', Timesheet::count())
                ->description('Total de tareos')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('danger'),
        ];
    }
}
```

#### 8.1.3 Métricas Especializadas por Módulo

**ClientStatsWidget:**
```
┌─────────────────────────────────────────┐
│ Total Clientes: 123                     │
│ Clientes registrados                    │
│ [building-office icon]                  │
├─────────────────────────────────────────┤
│ Clientes Activos: 89                    │
│ Con proyectos                           │
│ [check-circle icon] [color: success]    │
├─────────────────────────────────────────┤
│ Nuevos este Mes: 15                     │
│ Registrados en Dic                      │
│ [plus-circle icon] [color: info]        │
└─────────────────────────────────────────┘
```

**ProjectStatsWidget:**
```php
protected function getStats(): array
{
    $totalProjects = Project::count();
    $activeProjects = Project::whereNotNull('start_date')
                            ->whereNull('end_date')
                            ->count();
    $completedProjects = Project::whereNotNull('end_date')->count();
    
    return [
        Stat::make('Total Proyectos', $totalProjects)
            ->description('Proyectos registrados')
            ->descriptionIcon('heroicon-m-briefcase')
            ->color('primary'),
            
        Stat::make('En Progreso', $activeProjects)
            ->description('Sin fecha fin')
            ->descriptionIcon('heroicon-m-play-circle')
            ->color('success'),
            
        Stat::make('Completados', $completedProjects)
            ->description('Con fecha fin')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('info'),
    ];
}
```

### 8.2 Sistema de Reportes Avanzados

#### 8.2.1 ReportController API

El sistema proporciona endpoints especializados para generación de reportes:

```php
class ReportController extends Controller
{
    /**
     * Reporte de asistencias por proyecto y rango de fechas
     */
    public function attendanceReport(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:present,absent,late,permission,sick_leave'
        ]);
        
        $query = Attendance::with(['employee', 'timesheet.project'])
            ->whereHas('timesheet', function($q) use ($startDate, $endDate, $request) {
                $q->whereBetween('check_in_date', [$startDate, $endDate]);
                
                if ($request->filled('project_id')) {
                    $q->where('project_id', $request->project_id);
                }
            });
            
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'attendances' => $query->get(),
                'summary' => $this->generateAttendanceSummary($query),
                'filters_applied' => $request->only(['project_id', 'start_date', 'end_date', 'status'])
            ]
        ]);
    }
}
```

#### 8.2.2 Tipos de Reportes Disponibles

**Reporte de Asistencias:**
```
Endpoint: /api/reports/attendance
Parámetros:
├── project_id (opcional)
├── start_date (requerido)
├── end_date (requerido)
└── status (opcional)

Respuesta:
├── attendances[] (array de asistencias)
├── summary (resumen estadístico)
└── filters_applied (filtros utilizados)
```

**Dashboard de Administración:**
```php
public function dashboard(): JsonResponse
{
    $currentMonthStart = Carbon::now()->startOfMonth();
    $currentMonthEnd = Carbon::now()->endOfMonth();
    
    $activeProjects = Project::where('start_date', '<=', now())
                            ->where(function($q) {
                                $q->whereNull('end_date')
                                  ->orWhere('end_date', '>=', now());
                            })->count();
                            
    $todayTimesheets = Timesheet::whereDate('check_in_date', Carbon::today())->count();
    
    $monthlyAttendances = Attendance::whereHas('timesheet', function($query) use ($currentMonthStart, $currentMonthEnd) {
        $query->whereBetween('check_in_date', [$currentMonthStart, $currentMonthEnd]);
    });
    
    return response()->json([
        'success' => true,
        'data' => [
            'summary' => [
                'active_projects' => $activeProjects,
                'today_timesheets' => $todayTimesheets,
                'monthly_attendance_summary' => [
                    'total' => $monthlyAttendances->count(),
                    'present' => $monthlyAttendances->where('status', 'present')->count(),
                    'absent' => $monthlyAttendances->where('status', 'absent')->count(),
                    'late' => $monthlyAttendances->where('status', 'late')->count(),
                ]
            ]
        ]
    ]);
}
```

### 8.3 Widgets Especializados

#### 8.3.1 GeneralTimesheetStatsWidget

```php
class GeneralTimesheetStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $todayTimesheets = Timesheet::whereDate('check_in_date', $today)->count();
        
        $todayAttendances = Attendance::whereHas('timesheet', function ($query) use ($today) {
            $query->whereDate('check_in_date', $today);
        });
        
        $totalTodayAttendances = $todayAttendances->count();
        $todayPresent = $todayAttendances->where('status', 'attended')->count();
        $todayAbsent = $todayAttendances->where('status', 'absent')->count();
        
        return [
            Stat::make('Tareos Hoy', $todayTimesheets)
                ->description('Registros del día')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('Asistencias Procesadas', $totalTodayAttendances)
                ->description('Total del día')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Presentes Hoy', $todayPresent)
                ->description("Ausentes: {$todayAbsent}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
```

#### 8.3.2 TimesheetStatsWidget (Context-Aware)

```php
class TimesheetStatsWidget extends BaseWidget
{
    public ?Model $record = null;
    
    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }
        
        $timesheet = $this->record;
        $attendances = $timesheet->attendances();
        
        $totalAttendances = $attendances->count();
        $presentCount = $attendances->where('status', 'attended')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $justifiedCount = $attendances->where('status', 'justified')->count();
        
        $presentPercentage = $totalAttendances > 0 
            ? round(($presentCount / $totalAttendances) * 100, 1) 
            : 0;
            
        return [
            Stat::make('Total Registros', $totalAttendances)
                ->description('Empleados asignados')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Presentes', $presentCount)
                ->description("{$presentPercentage}% del total")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Ausentes', $absentCount)
                ->description("Justificados: {$justifiedCount}")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
```

### 8.4 Exportación de Reportes

#### 8.4.1 EmployeeExporter

```php
class EmployeeExporter implements ToCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Employee::with('user')->get();
    }
    
    public function headings(): array
    {
        return [
            'ID',
            'Nombres',
            'Apellidos',
            'Tipo Documento',
            'Número Documento',
            'Fecha Nacimiento',
            'Fecha Contrato',
            'Dirección',
            'Usuario Email',
            'Estado Usuario'
        ];
    }
    
    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->first_name,
            $employee->last_name,
            $employee->document_type,
            $employee->document_number,
            $employee->date_birth?->format('d/m/Y'),
            $employee->date_contract?->format('d/m/Y'),
            $employee->address,
            $employee->user?->email,
            $employee->user?->is_active ? 'Activo' : 'Inactivo'
        ];
    }
}
```

---

## 9. Funciones Especiales

### 9.1 Sistema de Búsqueda Global

El sistema implementa un motor de búsqueda global optimizado que permite encontrar registros de cualquier módulo desde una interfaz unificada.

#### 9.1.1 Arquitectura de Búsqueda

```php
// Configuración por Recurso
abstract class Resource
{
    protected static int $globalSearchResultsLimit = 10;
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['field1', 'field2', 'field3'];
    }
    
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Detail 1' => $record->attribute1,
            'Detail 2' => $record->attribute2,
        ];
    }
    
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['relation1', 'relation2'])
            ->select(['optimized_fields']);
    }
}
```

#### 9.1.2 Implementación por Módulo

**ClientResource Global Search:**
```php
protected static int $globalSearchResultsLimit = 10;

public static function getGloballySearchableAttributes(): array
{
    return ['business_name', 'document_number'];
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Documento' => $record->document_number,
        'Tipo' => $record->person_type,
        'Email' => $record->contact_email,
    ];
}
```

**EmployeeResource Global Search:**
```php
public static function getGloballySearchableAttributes(): array
{
    return ['first_name', 'last_name', 'document_number'];
}

public static function getGlobalSearchEloquentQuery(): Builder
{
    return parent::getGlobalSearchEloquentQuery()
        ->with('user')
        ->select(['id', 'first_name', 'last_name', 'document_number', 'user_id']);
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Correo' => $record->user?->email,
        'Documento' => $record->document_number,
        'Estado' => $record->user?->is_active ? 'Activo' : 'Inactivo',
    ];
}
```

### 9.2 Componente de Ubicación Geográfica

#### 9.2.1 Custom Form Component

El sistema incluye un componente personalizado para gestión de ubicaciones geográficas:

```php
// App\Forms\Components\ubicacion
class UbicacionComponent extends Component
{
    protected string $view = 'forms.components.ubicacion';
    
    public function getState(): array
    {
        return [
            'address' => $this->evaluate($this->getStateUsing()),
            'coordinates' => [
                'lat' => $this->getRecord()?->latitude,
                'lng' => $this->getRecord()?->longitude
            ]
        ];
    }
    
    public function saveState(): void
    {
        $state = $this->getState();
        
        if (isset($state['coordinates'])) {
            $this->getRecord()->update([
                'latitude' => $state['coordinates']['lat'],
                'longitude' => $state['coordinates']['lng'],
                'location_address' => $state['address']
            ]);
        }
    }
}
```

#### 9.2.2 Integración con Mapas

**Frontend Implementation:**
```javascript
// resources/js/ubicacion-component.js
class UbicacionMap {
    constructor(elementId, initialLat = -12.0464, initialLng = -77.0428) {
        this.map = L.map(elementId).setView([initialLat, initialLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);
        
        this.marker = L.marker([initialLat, initialLng], {
            draggable: true
        }).addTo(this.map);
        
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        this.marker.on('dragend', (e) => {
            const position = e.target.getLatLng();
            this.updateCoordinates(position.lat, position.lng);
        });
        
        this.map.on('click', (e) => {
            this.marker.setLatLng(e.latlng);
            this.updateCoordinates(e.latlng.lat, e.latlng.lng);
        });
    }
    
    updateCoordinates(lat, lng) {
        // Dispatch event to Livewire component
        window.Livewire.emit('coordinatesUpdated', {
            latitude: lat,
            longitude: lng
        });
    }
}
```

### 9.3 Sistema de Exportación e Importación

#### 9.3.1 Exportación de Datos

**Formatos Soportados:**
```
Export System
├── Excel (.xlsx) - Maatwebsite\Excel
├── CSV - Native Laravel
├── PDF - DomPDF Integration
└── Template Downloads - Pre-formatted files
```

**Excel Export Implementation:**
```php
class AttendanceTemplateExport implements FromView, WithTitle
{
    private $timesheet;
    
    public function __construct(Timesheet $timesheet)
    {
        $this->timesheet = $timesheet;
    }
    
    public function view(): View
    {
        $employees = Employee::select(['id', 'first_name', 'last_name', 'document_number'])
                           ->orderBy('first_name')
                           ->get();
                           
        return view('exports.attendance-template', [
            'employees' => $employees,
            'timesheet' => $this->timesheet,
            'project' => $this->timesheet->project
        ]);
    }
    
    public function title(): string
    {
        return 'Plantilla Asistencias';
    }
}
```

#### 9.3.2 Importación Masiva

**AttendancesImport Class:**
```php
class AttendancesImport implements ToModel, WithHeadingRow, WithValidation
{
    private $timesheetId;
    private $errors = [];
    
    public function rules(): array
    {
        return [
            'documento' => 'required|exists:employees,document_number',
            'estado' => 'required|in:attended,absent,late,justified',
            'turno' => 'required|in:day,night',
            'observacion' => 'nullable|string|max:500'
        ];
    }
    
    public function model(array $row)
    {
        $employee = Employee::where('document_number', $row['documento'])->first();
        
        if (!$employee) {
            $this->errors[] = "Empleado no encontrado: {$row['documento']}";
            return null;
        }
        
        return new Attendance([
            'timesheet_id' => $this->timesheetId,
            'employee_id' => $employee->id,
            'status' => $row['estado'],
            'shift' => $row['turno'] ?? 'day',
            'observation' => $row['observacion'] ?? null
        ]);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### 9.4 Interfaz Responsive y Adaptativa

#### 9.4.1 Breakpoints del Sistema

```css
/* Tailwind CSS Responsive Design */
.responsive-table {
    @apply block md:table;
}

.responsive-table thead {
    @apply hidden md:table-header-group;
}

.responsive-table tbody tr {
    @apply block border border-gray-300 md:border-none md:table-row;
}

.responsive-table tbody td {
    @apply block text-right md:table-cell md:text-left;
}

.responsive-table tbody td:before {
    content: attr(data-label) ": ";
    @apply float-left font-bold md:hidden;
}
```

#### 9.4.2 Adaptación por Dispositivo

**Desktop (≥1024px):**
- Vista completa de tablas con todas las columnas
- Sidebars expandidos
- Formularios en múltiples columnas
- Widgets en grid layout

**Tablet (768px - 1023px):**
- Tablas con scroll horizontal
- Sidebars colapsables
- Formularios en dos columnas
- Widgets apilados

**Mobile (≤767px):**
- Tablas en formato card
- Navegación tipo drawer
- Formularios en una columna
- Widgets en lista vertical

#### 9.4.3 Optimizaciones de Rendimiento

**Lazy Loading:**
```php
// En Resources
protected static bool $shouldRegisterNavigation = true;
protected static ?int $navigationSort = 10;

// Eager Loading en Queries
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['relation1', 'relation2'])
        ->withCount(['relation3']);
}
```

**Pagination:**
```php
// En Tables
protected static ?string $defaultPaginationPageOption = '25';
protected static array $paginationPageOptions = ['10', '25', '50', '100'];
```

---

## 10. Solución de Problemas

### ❌ Problemas Frecuentes

#### "No puedo crear un tareo"
**Posibles causas:**
- Ya existe un tareo para ese proyecto en esa fecha
- El proyecto no está activo
- Faltan permisos de creación

**Solución:**
1. Verifique la fecha y proyecto
2. Revise tareos existentes
3. Contacte al administrador si persiste

#### "No aparece un empleado en la lista"
**Posibles causas:**
- El empleado fue dado de baja
- No tiene permisos para ver empleados
- Error en los filtros aplicados

**Solución:**
1. Revise los filtros activos
2. Verifique el estado del empleado
3. Consulte con Recursos Humanos

#### "No puedo exportar datos"
**Posibles causas:**
- Faltan permisos de exportación
- Demasiados registros seleccionados
- Problema temporal del servidor

**Solución:**
1. Reduzca la cantidad de registros
2. Intente más tarde
3. Contacte soporte técnico

### 📞 Contacto de Soporte

Para asistencia técnica:
- **Email**: soporte@sat-industriales.pe
- **Teléfono**: (01) 234-5678
- **Horario**: Lunes a Viernes, 8:00 AM - 6:00 PM

---

## 11. Anexos

### 11.1 Diagramas de Arquitectura

#### 11.1.1 Diagrama de Módulos del Sistema

```
Sistema SAT Industriales
├── Core Application (Laravel Framework)
│   ├── Filament Admin Panel
│   ├── Laravel Sanctum (Authentication)
│   └── Spatie Permissions (Authorization)
├── Modules
│   ├── Client Management
│   │   ├── ClientResource
│   │   ├── SubClient RelationManager
│   │   └── Client Policy
│   ├── Human Resources
│   │   ├── EmployeeResource
│   │   ├── User Management
│   │   └── Employee Policy
│   ├── Operations Control
│   │   ├── QuoteResource
│   │   ├── ProjectResource
│   │   ├── TimesheetResource
│   │   └── Relation Managers
│   └── Reports & Analytics
│       ├── Dashboard Widgets
│       ├── Export Services
│       └── API Controllers
├── Database Layer
│   ├── MySQL Database
│   ├── Migrations
│   ├── Seeders
│   └── Factories
└── Infrastructure
    ├── File Storage
    ├── Session Management
    └── Cache System
```

#### 11.1.2 Diagrama de Relaciones de Base de Datos

```
Database Schema Relationships

Users (1) ←→ (0..1) Employees
    ↓
Employees (1) ←→ (N) Attendances
    ↓              ↓
    ├→ (N) Timesheets
    └→ (N) Quotes

Clients (1) ←→ (N) SubClients
   ↓                  ↓
   └→ (N) Quotes ←───┘
        ↓
        └→ (1) Projects
             ↓
             └→ (N) Timesheets
                  ↓
                  └→ (N) Attendances

Roles (1) ←→ (N) Users
  ↓
  └→ (N) Permissions
```

### 11.2 Glosario Técnico

#### 11.2.1 Términos de Negocio

| Término | Definición | Contexto de Uso |
|---------|------------|----------------|
| **Tareo** | Registro diario de control de asistencia de empleados en un proyecto específico | Control laboral diario |
| **Asistencia** | Registro individual de la presencia de un empleado en un tareo | Gestión de personal |
| **Cotización** | Documento comercial que contiene la propuesta económica para un proyecto | Proceso comercial |
| **Sub-cliente** | Sede, sucursal o división de un cliente principal | Gestión de clientes |
| **TDR** | Términos de Referencia - Documento técnico que especifica requisitos del proyecto | Documentación técnica |
| **PE/PT** | Clasificación del tipo de servicio (Proyecto Específico/Proyecto Tipo) | Categorización comercial |
| **Correlativo** | Código único secuencial asignado a cada cotización | Sistema de numeración |

#### 11.2.2 Términos Técnicos

| Término | Definición | Implementación |
|---------|------------|----------------|
| **Resource** | Clase de Filament que define la interfaz CRUD para un modelo | `app/Filament/Resources/` |
| **Relation Manager** | Componente para gestionar relaciones entre modelos | `RelationManager` class |
| **Widget** | Componente de dashboard que muestra métricas | `StatsOverviewWidget` |
| **Policy** | Clase que define las reglas de autorización | `app/Policies/` |
| **Exporter** | Clase que define la exportación de datos | Laravel Excel |
| **Scope** | Método de consulta reutilizable en modelos Eloquent | Model scopes |

### 11.3 Configuración del Sistema

#### 11.3.1 Requisitos del Sistema

**Requisitos del Servidor:**
```
Software Requirements:
├── PHP >= 8.1
├── Composer >= 2.0
├── Node.js >= 16.0
├── MySQL >= 8.0
└── Nginx/Apache

PHP Extensions:
├── BCMath
├── Ctype
├── Fileinfo
├── JSON
├── Mbstring
├── OpenSSL
├── PDO
├── Tokenizer
└── XML
```

**Configuración de Base de Datos:**
```sql
-- Configuración MySQL recomendada
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_file_per_table = ON
innodb_flush_log_at_trx_commit = 2
query_cache_size = 64M
```

#### 11.3.2 Variables de Entorno

**Archivo .env Principal:**
```env
# Application
APP_NAME="SAT Industriales Monitor"
APP_ENV=production
APP_KEY=base64:generated_key
APP_DEBUG=false
APP_URL=https://monitor.sat-industriales.pe

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sat_monitor
DB_USERNAME=sat_user
DB_PASSWORD=secure_password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=system@sat-industriales.pe
MAIL_PASSWORD=app_password

# File Storage
FILESYSTEM_DISK=local
AWS_BUCKET=sat-industriales-storage
```

### 11.4 Procedimientos de Instalación

#### 11.4.1 Instalación en Servidor de Producción

**Paso 1: Preparación del Servidor**
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install nginx mysql-server php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**Paso 2: Configuración del Proyecto**
```bash
# Clonar repositorio
git clone https://github.com/sat-industriales/monitor.git /var/www/html/sat-monitor

# Instalar dependencias
cd /var/www/html/sat-monitor
composer install --optimize-autoloader --no-dev
npm install && npm run production

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Paso 3: Configuración de Base de Datos**
```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE sat_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sat_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON sat_monitor.* TO 'sat_user'@'localhost';
FLUSH PRIVILEGES;

# Ejecutar migraciones
php artisan migrate --force
php artisan db:seed --force
```

#### 11.4.2 Configuración de Nginx

**Archivo de Configuración:**
```nginx
server {
    listen 80;
    server_name monitor.sat-industriales.pe;
    root /var/www/html/sat-monitor/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 11.5 Guía de Migración y Actualizaciones

#### 11.5.1 Procedimiento de Backup

**Script de Backup Automatizado:**
```bash
#!/bin/bash
# backup.sh

DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/backups"
APP_DIR="/var/www/html/sat-monitor"

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u sat_user -p sat_monitor > $BACKUP_DIR/database_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $APP_DIR/storage/app/

# Backup de configuración
cp $APP_DIR/.env $BACKUP_DIR/env_$DATE.backup

# Limpiar backups antiguos (mantener 30 días)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

#### 11.5.2 Procedimiento de Actualización

**Pasos para Actualización:**
```bash
# 1. Crear backup
./backup.sh

# 2. Poner en mantenimiento
php artisan down

# 3. Actualizar código
git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run production

# 4. Ejecutar migraciones
php artisan migrate --force

# 5. Limpiar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restaurar servicio
php artisan up
```

### 11.6 API Documentation

#### 11.6.1 Endpoints Principales

**Autenticación:**
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@sat-industriales.pe",
    "password": "password"
}

Response: {
    "token": "1|generated_token",
    "user": { ... }
}
```

**Proyectos:**
```http
GET /api/projects
Authorization: Bearer {token}

Response: {
    "success": true,
    "data": [...],
    "meta": { ... }
}
```

**Tareos:**
```http
POST /api/timesheets
Authorization: Bearer {token}
Content-Type: application/json

{
    "project_id": 1,
    "employee_id": 1,
    "check_in_date": "2024-12-01 08:00:00",
    "shift": "day"
}
```

**Asistencias:**
```http
GET /api/attendances/search
Authorization: Bearer {token}

Query Parameters:
- timesheet_id: integer
- employee_id: integer
- status: string
- date_from: date
- date_to: date
```

#### 11.6.2 Códigos de Respuesta

| Código | Significado | Uso |
|--------|-------------|-----|
| 200 | OK | Operación exitosa |
| 201 | Created | Recurso creado exitosamente |
| 400 | Bad Request | Datos de entrada inválidos |
| 401 | Unauthorized | Token inválido o expirado |
| 403 | Forbidden | Sin permisos para la operación |
| 404 | Not Found | Recurso no encontrado |
| 422 | Unprocessable Entity | Errores de validación |
| 500 | Internal Server Error | Error interno del servidor |

---

## Glosario de Términos

### Términos Funcionales

- **Tareo**: Registro diario de entrada y salida de empleados en un proyecto
- **Asistencia**: Registro individual de la presencia de un empleado en un tareo
- **Cotización**: Presupuesto formal presentado a un cliente
- **Sub-cliente**: Sede o sucursal de un cliente principal
- **TDR**: Términos de Referencia del proyecto
- **PE/PT**: Clasificación del tipo de servicio
- **Correlativo**: Código único secuencial de cotización
- **Dashboard**: Panel principal con estadísticas generales
- **Relation Manager**: Componente para gestionar relaciones entre entidades
- **Widget**: Elemento de interfaz que muestra información específica
- **Resource**: Clase que define la gestión de un modelo de datos

### Términos Técnicos

- **CRUD**: Create, Read, Update, Delete (operaciones básicas de datos)
- **API**: Application Programming Interface
- **ORM**: Object-Relational Mapping (mapeo objeto-relacional)
- **MVC**: Model-View-Controller (patrón de arquitectura)
- **RBAC**: Role-Based Access Control (control de acceso basado en roles)
- **Middleware**: Componente intermedio que procesa requests
- **Migration**: Script que modifica la estructura de la base de datos
- **Seeder**: Script que inserta datos iniciales en la base de datos

---

## Información de Versión y Actualizaciones

### Versión Actual: 1.0

**Fecha de Actualización**: Diciembre 2024  
**Sistema**: SAT Industriales Monitoring Platform  
**Framework**: Laravel 10.x con Filament 3.x

### Historial de Cambios

| Versión | Fecha | Cambios Principales |
|---------|-------|-------------------|
| 1.0 | Dic 2024 | Release inicial con todos los módulos |

### Próximas Funcionalidades

- Integración con sistemas contables
- Módulo de facturación
- App móvil para supervisores
- Reportes avanzados con gráficos
- Integración con sistemas de marcado biométrico

### Contacto para Soporte Técnico

**Soporte Técnico Especializado:**
- **Email**: soporte@sat-industriales.pe
- **Teléfono**: (01) 234-5678
- **Horario**: Lunes a Viernes, 8:00 AM - 6:00 PM
- **Portal de Tickets**: https://tickets.sat-industriales.pe

**Documentación Técnica:**
- **Repositorio**: https://github.com/sat-industriales/monitor
- **Documentación API**: https://docs.sat-industriales.pe/api
- **Wiki Técnica**: https://wiki.sat-industriales.pe

---

*Este manual técnico ha sido desarrollado para proporcionar una guía completa del Sistema de Monitoreo SAT Industriales. Para obtener asistencia especializada o reportar problemas, contacte al equipo de soporte técnico utilizando los canales oficiales mencionados.*

**© 2024 SAT Industriales S.A.C. - Todos los derechos reservados**
