# 🚀 Guía Rápida - Sistema SAT Industriales

## 📋 Acciones Comunes

### 👥 Clientes
```
Nuevo Cliente: Gestión de clientes > Clientes > Nuevo Cliente
Buscar: Usar filtros por tipo de documento o razón social
Sedes: Pestaña "Subclientes" en formulario de cliente
```

### 👤 Empleados
```
Nuevo Empleado: Recursos Humanos > Colaboradores > Nuevo Colaborador
Usuario Sistema: Pestaña "Información del Usuario" (opcional)
Exportar: Botón "Exportar" en la tabla
Búsqueda Global: Barra superior del sistema
```

### 📝 Cotizaciones
```
Nueva Cotización: Control de operaciones > Cotizaciones > Nueva Cotización
Estados: Pendiente → En Revisión → Aprobada/Rechazada
Ubicación: Usar mapa interactivo en formulario
Archivos: TDR y archivo de cotización en pestañas
```

### 🏗️ Proyectos
```
Nuevo Proyecto: Control de operaciones > Proyectos > Nuevo Proyecto
Base: Debe tener cotización aprobada
Estados: Automático por fechas (Activo/Programado/Finalizado)
Tareos: Gestionar desde pestaña "Tareos" del proyecto
```

### ⏰ Tareos
```
Nuevo Tareo: Control de operaciones > Tareos > Nuevo Tareo
Restricción: Un tareo por proyecto por día
Horarios: Entrada → Descanso → Fin Descanso → Salida
Asistencias: Pestaña "Asistencias" del tareo
```

### 👥 Asistencias
```
Individual: Desde tareo > Asistencias > Nueva Asistencia
Masiva: Botón "Generar Asistencias" en tareo
Estados: Asistió, Presente, Tarde, Faltó, Justificado
Horarios: Solo para estados de asistencia
```

## ⚠️ Validaciones Importantes

### 🚨 Restricciones
- **Tareos**: Un por proyecto por día
- **Asistencias**: Una por empleado por tareo
- **Cotizaciones**: Correlativo único
- **Horarios**: Secuencia lógica obligatoria

### 🔐 Permisos Básicos
- **Ver**: Solo lectura
- **Crear**: Agregar registros
- **Editar**: Modificar existentes
- **Eliminar**: Borrar registros

## 📊 Estados del Sistema

### Cotizaciones
- ⏳ **Pendiente**: Estado inicial
- 📋 **En Revisión**: Cliente evaluando
- ✅ **Aprobada**: Aceptada, puede crear proyecto
- ❌ **Rechazada**: No aceptada

### Proyectos
- 🟢 **Activo**: En fechas de ejecución
- 🔵 **Programado**: Inicio futuro
- 🔴 **Finalizado**: Fecha fin pasada

### Asistencias
- ✅ **Asistió/Presente**: Jornada completa
- ⚠️ **Llegó Tarde**: Fuera de horario
- ❌ **Faltó**: No asistió
- 📝 **Justificado**: Ausencia autorizada

## 🔧 Funciones Rápidas

### Búsqueda Global
```
Ubicación: Barra superior del sistema
Busca: Empleados, clientes, proyectos, cotizaciones
Filtros: Por nombre, documento, código
```

### Exportación
```
Formatos: Excel, PDF, CSV
Ubicación: Botón "Exportar" en tablas
Uso: Análisis externo y reportes
```

### Mapas
```
Ubicación: Formularios de cotizaciones y proyectos
Función: Clic para seleccionar coordenadas
Auto: Guarda latitud/longitud automáticamente
```

## 🆘 Soluciones Rápidas

### Problemas Comunes
```
No puedo crear tareo:
→ Verificar fecha única por proyecto
→ Confirmar proyecto activo
→ Revisar permisos

Empleado no aparece:
→ Verificar filtros activos
→ Confirmar empleado activo
→ Revisar permisos de visualización

Error de exportación:
→ Reducir cantidad de registros
→ Intentar más tarde
→ Contactar soporte
```

### Contacto Soporte
```
📧 Email: soporte@sat-industriales.pe
📞 Teléfono: (01) 234-5678
🕒 Horario: L-V, 8:00 AM - 6:00 PM
```

## 🎯 Flujo de Trabajo Típico

### 1. Configuración Inicial
```
1. Registrar Cliente
2. Agregar Sedes (si aplica)
3. Registrar Empleados
4. Asignar Roles y Permisos
```

### 2. Proceso Comercial
```
1. Crear Cotización
2. Marcar ubicación en mapa
3. Subir archivos TDR
4. Cambiar estado según avance
5. Aprobar para crear proyecto
```

### 3. Ejecución de Proyecto
```
1. Crear Proyecto desde cotización
2. Definir fechas inicio/fin
3. Crear tareos diarios
4. Registrar asistencias
5. Generar reportes
```

### 4. Control Diario
```
1. Abrir proyecto activo
2. Crear/seleccionar tareo del día
3. Generar asistencias masivas
4. Ajustar horarios individuales
5. Agregar observaciones
```

---

**💡 Tip**: Mantenga esta guía a mano para consultas rápidas durante el uso diario del sistema.

**📅 Actualizado**: Diciembre 2024 | **Versión**: 1.0
