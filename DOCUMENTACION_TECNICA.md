# Documentación Técnica Avanzada
## Sistema SAT Industriales - Arquitectura y Diagramas

### Diagramas de Arquitectura del Sistema

#### 1. Arquitectura General del Sistema

```mermaid
graph TB
    subgraph "Frontend Layer"
        A[Filament Admin Panel]
        B[API Endpoints]
        C[Web Interface]
    end
    
    subgraph "Application Layer"
        D[Laravel Framework]
        E[Filament Resources]
        F[Relation Managers]
        G[Widgets]
        H[Policies]
    end
    
    subgraph "Business Logic"
        I[Models & Eloquent]
        J[Services]
        K[Controllers]
        L[Middleware]
    end
    
    subgraph "Data Layer"
        M[MySQL Database]
        N[Migrations]
        O[Seeders]
        P[Factories]
    end
    
    subgraph "Infrastructure"
        Q[Redis Cache]
        R[File Storage]
        S[Queue System]
        T[Mail Services]
    end
    
    A --> D
    B --> D
    C --> D
    D --> I
    E --> I
    F --> I
    G --> I
    H --> I
    I --> M
    J --> M
    K --> M
    D --> Q
    D --> R
    D --> S
    D --> T
```

#### 2. Diagrama de Flujo de Proceso de Negocio

```mermaid
flowchart TD
    A[Cliente Potencial] --> B[Crear Cotización]
    B --> C{Estado Cotización}
    C -->|Pending| D[Revisión Comercial]
    C -->|Sent| E[Enviado a Cliente]
    C -->|Approved| F[Crear Proyecto]
    C -->|Rejected| G[Archivar]
    
    D --> E
    E --> H{Respuesta Cliente}
    H -->|Aprobado| F
    H -->|Rechazado| G
    H -->|Modificaciones| B
    
    F --> I[Asignar Empleados]
    I --> J[Crear Tareos]
    J --> K[Registrar Asistencias]
    K --> L[Control Diario]
    L --> M{Proyecto Activo?}
    M -->|Sí| K
    M -->|No| N[Finalizar Proyecto]
    
    N --> O[Generar Reportes]
    O --> P[Facturación]
```

#### 3. Diagrama de Entidad-Relación (ERD)

```mermaid
erDiagram
    USERS {
        id bigint PK
        name string
        email string UK
        email_verified_at timestamp
        password string
        created_at timestamp
        updated_at timestamp
    }
    
    EMPLOYEES {
        id bigint PK
        user_id bigint FK
        first_name string
        last_name string
        dni string UK
        phone string
        address text
        birth_date date
        hire_date date
        is_active boolean
        created_at timestamp
        updated_at timestamp
    }
    
    CLIENTS {
        id bigint PK
        business_name string
        ruc string UK
        contact_person string
        email string
        phone string
        address text
        is_active boolean
        created_at timestamp
        updated_at timestamp
    }
    
    SUB_CLIENTS {
        id bigint PK
        client_id bigint FK
        name string
        address text
        contact_person string
        phone string
        is_active boolean
        created_at timestamp
        updated_at timestamp
    }
    
    QUOTES {
        id bigint PK
        correlative integer UK
        quote_number string UK
        client_id bigint FK
        sub_client_id bigint FK
        description text
        amount decimal
        status enum
        service_type enum
        tdr_document string
        valid_until date
        created_at timestamp
        updated_at timestamp
    }
    
    PROJECTS {
        id bigint PK
        quote_id bigint FK
        name string
        description text
        start_date date
        end_date date
        status enum
        budget decimal
        created_at timestamp
        updated_at timestamp
    }
    
    TIMESHEETS {
        id bigint PK
        project_id bigint FK
        employee_id bigint FK
        check_in_date datetime
        check_out_date datetime
        shift enum
        notes text
        created_at timestamp
        updated_at timestamp
    }
    
    ATTENDANCES {
        id bigint PK
        timesheet_id bigint FK
        employee_id bigint FK
        check_in_time datetime
        check_out_time datetime
        status enum
        notes text
        created_at timestamp
        updated_at timestamp
    }
    
    ROLES {
        id bigint PK
        name string UK
        guard_name string
        created_at timestamp
        updated_at timestamp
    }
    
    PERMISSIONS {
        id bigint PK
        name string UK
        guard_name string
        created_at timestamp
        updated_at timestamp
    }
    
    MODEL_HAS_ROLES {
        role_id bigint FK
        model_type string
        model_id bigint
    }
    
    ROLE_HAS_PERMISSIONS {
        permission_id bigint FK
        role_id bigint FK
    }
    
    USERS ||--|| EMPLOYEES : "has"
    CLIENTS ||--o{ SUB_CLIENTS : "has"
    CLIENTS ||--o{ QUOTES : "requests"
    SUB_CLIENTS ||--o{ QUOTES : "requests"
    QUOTES ||--|| PROJECTS : "generates"
    PROJECTS ||--o{ TIMESHEETS : "contains"
    EMPLOYEES ||--o{ TIMESHEETS : "works_in"
    TIMESHEETS ||--o{ ATTENDANCES : "has"
    EMPLOYEES ||--o{ ATTENDANCES : "records"
    USERS ||--o{ MODEL_HAS_ROLES : "assigned"
    ROLES ||--o{ MODEL_HAS_ROLES : "assigned_to"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "has"
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "granted_to"
```

#### 4. Diagrama de Secuencia - Proceso de Tareo

```mermaid
sequenceDiagram
    participant S as Supervisor
    participant UI as Filament UI
    participant TR as TimesheetResource
    participant AR as AttendancesRelationManager
    participant DB as Database
    participant N as Notifications
    
    S->>UI: Accede a Proyecto
    UI->>TR: Carga TimesheetsRelationManager
    TR->>DB: Consulta tareos del proyecto
    DB-->>TR: Retorna lista de tareos
    TR-->>UI: Muestra tareos disponibles
    
    S->>UI: Selecciona tareo específico
    UI->>AR: Carga AttendancesRelationManager
    AR->>DB: Consulta asistencias del tareo
    DB-->>AR: Retorna asistencias
    AR-->>UI: Muestra tabla de asistencias
    
    S->>UI: Registra nueva asistencia
    UI->>AR: Valida datos del formulario
    AR->>DB: Inserta nueva asistencia
    DB-->>AR: Confirma inserción
    AR->>N: Envía notificación
    AR-->>UI: Actualiza tabla
    UI-->>S: Confirma registro exitoso
```

#### 5. Diagrama de Estados - Cotización

```mermaid
stateDiagram-v2
    [*] --> Pending : Crear cotización
    
    Pending --> Sent : Enviar a cliente
    Pending --> Draft : Guardar borrador
    
    Draft --> Pending : Completar información
    Draft --> [*] : Cancelar
    
    Sent --> Approved : Cliente aprueba
    Sent --> Rejected : Cliente rechaza
    Sent --> Pending : Solicitar modificaciones
    
    Approved --> ProjectCreated : Crear proyecto
    ProjectCreated --> [*] : Proceso completado
    
    Rejected --> [*] : Archivar
    Rejected --> Pending : Reabrir para modificaciones
```

#### 6. Diagrama de Componentes - Filament Resources

```mermaid
graph TB
    subgraph "Filament Resources Layer"
        A[ClientResource]
        B[EmployeeResource]
        C[QuoteResource]
        D[ProjectResource]
        E[TimesheetResource]
    end
    
    subgraph "Relation Managers"
        F[SubClientsRelationManager]
        G[TimesheetsRelationManager]
        H[AttendancesRelationManager]
    end
    
    subgraph "Widgets"
        I[StatsOverviewWidget]
        J[ProjectsChartWidget]
        K[AttendanceCalendarWidget]
    end
    
    subgraph "Policies"
        L[ClientPolicy]
        M[EmployeePolicy]
        N[QuotePolicy]
        O[ProjectPolicy]
        P[TimesheetPolicy]
    end
    
    subgraph "Models"
        Q[Client Model]
        R[Employee Model]
        S[Quote Model]
        T[Project Model]
        U[Timesheet Model]
        V[Attendance Model]
    end
    
    A --> F
    A --> L
    A --> Q
    
    B --> M
    B --> R
    
    C --> N
    C --> S
    
    D --> G
    D --> O
    D --> T
    
    E --> H
    E --> P
    E --> U
    
    G --> U
    G --> V
    
    H --> V
    
    I --> Q
    I --> R
    I --> T
    
    Q --> V
    R --> V
    T --> U
    U --> V
```

### Especificaciones Técnicas Detalladas

#### 1. Estructura de Archivos del Proyecto

```
app/
├── Filament/
│   ├── Resources/
│   │   ├── ClientResource.php
│   │   │   └── RelationManagers/
│   │   │       └── SubClientsRelationManager.php
│   │   ├── EmployeeResource.php
│   │   ├── QuoteResource.php
│   │   ├── ProjectResource.php
│   │   │   └── RelationManagers/
│   │   │       └── TimesheetsRelationManager.php
│   │   └── TimesheetResource.php
│   │       └── RelationManagers/
│   │           └── AttendancesRelationManager.php
│   └── Widgets/
│       ├── StatsOverviewWidget.php
│       ├── ProjectsChartWidget.php
│       └── AttendanceCalendarWidget.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AttendanceApiController.php
│   │   │   ├── ProjectApiController.php
│   │   │   └── TimesheetApiController.php
│   │   └── Web/
│   │       └── DashboardController.php
│   └── Middleware/
│       ├── CheckRole.php
│       └── ValidateApiToken.php
├── Models/
│   ├── User.php
│   ├── Employee.php
│   ├── Client.php
│   ├── SubClient.php
│   ├── Quote.php
│   ├── Project.php
│   ├── Timesheet.php
│   └── Attendance.php
├── Policies/
│   ├── ClientPolicy.php
│   ├── EmployeePolicy.php
│   ├── QuotePolicy.php
│   ├── ProjectPolicy.php
│   └── TimesheetPolicy.php
├── Exports/
│   ├── AttendancesExport.php
│   └── AttendanceTemplateExport.php
└── Imports/
    └── AttendancesImport.php
```

#### 2. Configuración de Permisos y Roles

```php
// Configuración de roles en DatabaseSeeder
$roles = [
    'super_admin' => [
        'name' => 'Super Admin',
        'permissions' => ['*'] // Todos los permisos
    ],
    'comercial' => [
        'name' => 'Comercial',
        'permissions' => [
            'view_clients', 'create_clients', 'edit_clients',
            'view_quotes', 'create_quotes', 'edit_quotes',
            'view_projects', 'create_projects', 'edit_projects'
        ]
    ],
    'supervisor' => [
        'name' => 'Supervisor',
        'permissions' => [
            'view_employees', 'create_employees', 'edit_employees',
            'view_timesheets', 'create_timesheets', 'edit_timesheets',
            'view_attendances', 'create_attendances', 'edit_attendances'
        ]
    ],
    'administrativo' => [
        'name' => 'Administrativo',
        'permissions' => [
            'view_clients', 'view_employees', 'view_quotes',
            'view_projects', 'view_timesheets', 'view_attendances',
            'export_data'
        ]
    ]
];
```

#### 3. API Endpoints Completos

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Autenticación
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Clientes
    Route::apiResource('clients', ClientApiController::class);
    Route::get('clients/{client}/sub-clients', [ClientApiController::class, 'subClients']);
    
    // Empleados
    Route::apiResource('employees', EmployeeApiController::class);
    Route::get('employees/active', [EmployeeApiController::class, 'active']);
    
    // Cotizaciones
    Route::apiResource('quotes', QuoteApiController::class);
    Route::patch('quotes/{quote}/status', [QuoteApiController::class, 'updateStatus']);
    
    // Proyectos
    Route::apiResource('projects', ProjectApiController::class);
    Route::get('projects/{project}/timesheets', [ProjectApiController::class, 'timesheets']);
    
    // Tareos
    Route::apiResource('timesheets', TimesheetApiController::class);
    Route::get('timesheets/{timesheet}/attendances', [TimesheetApiController::class, 'attendances']);
    
    // Asistencias
    Route::apiResource('attendances', AttendanceApiController::class);
    Route::get('attendances/search', [AttendanceApiController::class, 'search']);
    Route::post('attendances/bulk-import', [AttendanceApiController::class, 'bulkImport']);
    
    // Reportes
    Route::get('reports/dashboard-stats', [ReportController::class, 'dashboardStats']);
    Route::get('reports/attendance-summary', [ReportController::class, 'attendanceSummary']);
    Route::get('reports/project-progress', [ReportController::class, 'projectProgress']);
    
    // Exportaciones
    Route::get('export/attendances', [ExportController::class, 'attendances']);
    Route::get('export/timesheets', [ExportController::class, 'timesheets']);
    Route::get('export/projects', [ExportController::class, 'projects']);
});
```

#### 4. Configuración de Base de Datos Optimizada

```sql
-- Índices para optimización de consultas
CREATE INDEX idx_attendances_timesheet_employee ON attendances(timesheet_id, employee_id);
CREATE INDEX idx_attendances_check_in_time ON attendances(check_in_time);
CREATE INDEX idx_timesheets_project_employee ON timesheets(project_id, employee_id);
CREATE INDEX idx_timesheets_check_in_date ON timesheets(check_in_date);
CREATE INDEX idx_quotes_status ON quotes(status);
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_clients_is_active ON clients(is_active);
CREATE INDEX idx_employees_is_active ON employees(is_active);

-- Configuración de particiones por fecha (opcional para grandes volúmenes)
ALTER TABLE attendances 
PARTITION BY RANGE (YEAR(check_in_time)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

#### 5. Configuración de Cache y Performance

```php
// config/cache.php - Configuración optimizada
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

// Implementación de cache en modelos
class Project extends Model
{
    public function getActiveTimesheetsCountAttribute()
    {
        return Cache::remember(
            "project_{$this->id}_active_timesheets",
            3600, // 1 hora
            fn() => $this->timesheets()->whereNull('check_out_date')->count()
        );
    }
    
    public function getTotalHoursWorkedAttribute()
    {
        return Cache::remember(
            "project_{$this->id}_total_hours",
            1800, // 30 minutos
            fn() => $this->attendances()
                ->whereNotNull('check_out_time')
                ->sum('hours_worked')
        );
    }
}
```

#### 6. Monitoreo y Logging

```php
// Configuración de logging personalizado
'channels' => [
    'attendance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/attendance.log'),
        'level' => 'info',
        'days' => 30,
    ],
    
    'api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api.log'),
        'level' => 'info',
        'days' => 14,
    ],
    
    'business' => [
        'driver' => 'daily',
        'path' => storage_path('logs/business.log'),
        'level' => 'info',
        'days' => 90,
    ],
];

// Implementación en controladores
Log::channel('attendance')->info('Nueva asistencia registrada', [
    'employee_id' => $attendance->employee_id,
    'timesheet_id' => $attendance->timesheet_id,
    'check_in_time' => $attendance->check_in_time,
    'user_id' => auth()->id(),
]);
```

### Procedimientos de Mantenimiento

#### 1. Backup Automatizado

```bash
#!/bin/bash
# Script de backup completo

# Variables
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/backups/sat_monitor"
DB_NAME="sat_monitor"
DB_USER="sat_user"
APP_DIR="/var/www/html/sat-monitor"

# Crear directorios
mkdir -p $BACKUP_DIR/{database,files,config}

# Backup de base de datos
mysqldump -u $DB_USER -p $DB_NAME --single-transaction --routines --triggers > $BACKUP_DIR/database/db_$DATE.sql

# Backup de archivos de storage
tar -czf $BACKUP_DIR/files/storage_$DATE.tar.gz $APP_DIR/storage/app/

# Backup de configuración
cp $APP_DIR/.env $BACKUP_DIR/config/env_$DATE.backup
cp -r $APP_DIR/config/ $BACKUP_DIR/config/config_$DATE/

# Limpiar backups antiguos (30 días)
find $BACKUP_DIR -type f -mtime +30 -delete

# Subir a storage remoto (opcional)
# aws s3 sync $BACKUP_DIR s3://sat-backups/$(date +%Y/%m/)/
```

#### 2. Monitoreo de Salud del Sistema

```bash
#!/bin/bash
# Script de monitoreo de salud

# Verificar servicios
systemctl is-active --quiet nginx || echo "ALERT: Nginx down"
systemctl is-active --quiet php8.1-fpm || echo "ALERT: PHP-FPM down"
systemctl is-active --quiet mysql || echo "ALERT: MySQL down"
systemctl is-active --quiet redis || echo "ALERT: Redis down"

# Verificar uso de disco
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "ALERT: Disk usage at ${DISK_USAGE}%"
fi

# Verificar logs de errores
ERROR_COUNT=$(grep -c "ERROR" /var/log/nginx/error.log | tail -1)
if [ $ERROR_COUNT -gt 10 ]; then
    echo "ALERT: High error count in Nginx logs: $ERROR_COUNT"
fi

# Verificar conexiones de base de datos
DB_CONNECTIONS=$(mysql -u monitoring -p -e "SHOW STATUS LIKE 'Threads_connected';" | awk 'NR==2 {print $2}')
if [ $DB_CONNECTIONS -gt 50 ]; then
    echo "ALERT: High database connections: $DB_CONNECTIONS"
fi
```

### Guía de Troubleshooting

#### Problemas Comunes y Soluciones

1. **Error de permisos en storage/**
   ```bash
   sudo chown -R www-data:www-data storage/
   sudo chmod -R 775 storage/
   ```

2. **Cache corrupto**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Problemas de migración**
   ```bash
   php artisan migrate:status
   php artisan migrate:rollback --step=1
   php artisan migrate
   ```

4. **Performance lenta**
   ```bash
   php artisan optimize
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

**Documentación Técnica SAT Industriales**  
*Versión 1.0 - Diciembre 2024*
