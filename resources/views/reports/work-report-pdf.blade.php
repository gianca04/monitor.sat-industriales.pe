<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Trabajo - {{ $workReport->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
        }

        .report-title {
            font-size: 20px;
            color: #1e40af;
            margin: 20px 0;
            text-align: center;
        }

        .info-section {
            background-color: #f8fafc;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #374151;
        }

        .info-value {
            color: #6b7280;
        }

        .photos-section {
            margin-top: 30px;
        }

        .section-title {
            font-size: 18px;
            color: #1e40af;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        .photo-container {
            margin-bottom: 30px;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .photo-header {
            background-color: #f3f4f6;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .photo-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }

        .photo-date {
            font-size: 12px;
            color: #6b7280;
        }

        .photo-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            display: block;
        }

        .photo-description {
            padding: 15px;
            background-color: #fff;
            color: #4b5563;
            font-style: italic;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        .page-break {
            page-break-before: always;
        }

        .summary-stats {
            display: flex;
            justify-content: space-around;
            background-color: #eff6ff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">SAT INDUSTRIALES</div>
        <div class="subtitle">Sistema de Monitoreo de Proyectos</div>
        <div class="report-title">REPORTE DE TRABAJO</div>
    </div>

    <!-- Información General -->
    <div class="info-section">
        <h3 style="margin-top: 0; color: #1e40af;">Información del Reporte</h3>
        <div class="info-row">
            <span class="info-label">Reporte #:</span>
            <span class="info-value">{{ $workReport->id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span class="info-value">{{ $workReport->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Descripción:</span>
            <span class="info-value">{{ $workReport->description ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha de creación:</span>
            <span class="info-value">{{ $workReport->created_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <!-- Información del Supervisor -->
    <div class="info-section">
        <h3 style="margin-top: 0; color: #1e40af;">Supervisor Responsable</h3>
        <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span class="info-value">{{ $employee->first_name }} {{ $employee->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Documento:</span>
            <span class="info-value">{{ $employee->document_type }} {{ $employee->document_number }}</span>
        </div>
        @if($employee->user)
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $employee->user->email }}</span>
        </div>
        @endif
    </div>

    <!-- Información del Proyecto -->
    <div class="info-section">
        <h3 style="margin-top: 0; color: #1e40af;">Proyecto</h3>
        <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span class="info-value">{{ $project->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Código:</span>
            <span class="info-value">{{ $project->quote_id ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">{{ $project->status ?? 'Activo' }}</span>
        </div>
        @if($project->start_date)
        <div class="info-row">
            <span class="info-label">Fecha inicio:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') }}</span>
        </div>
        @endif
    </div>

    <!-- Estadísticas del Reporte -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-number">{{ $photos->count() }}</div>
            <div class="stat-label">Total Evidencias</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ $photos->where('taken_at', '>=', today())->count() }}</div>
            <div class="stat-label">Evidencias Hoy</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ $photos->groupBy(function($item) { return $item->taken_at->format('Y-m-d'); })->count() }}</div>
            <div class="stat-label">Días de Trabajo</div>
        </div>
    </div>

    <!-- Evidencias Fotográficas -->
    <div class="photos-section">
        <h2 class="section-title">Evidencias Fotográficas</h2>

        @foreach($photos as $index => $photo)
        @if($index > 0 && $index % 2 == 0)
        <div class="page-break"></div>
        @endif

        <div class="photo-container">
            <div class="photo-header">
                <div class="photo-title">Evidencia #{{ $loop->iteration }}</div>
                <div class="photo-date">
                    Capturada el: {{ $photo->taken_at->format('d/m/Y H:i') }}
                </div>
            </div>

            @php
            $imgPath = public_path($photo->photo_path);
            @endphp
            @if(file_exists($imgPath))
            <img src="{{ public_path('storage/' . basename($photo->photo_path)) }}"
                alt="Evidencia {{ $loop->iteration }}"
                class="photo-image">
            @else
            <div style="padding: 40px; text-align: center; color: #6b7280; background-color: #f9fafb;">
                <p>Imagen no disponible</p>
                <small>{{ $photo->photo_path }}</small>
            </div>
            @endif

            <div class="photo-description">
                <strong>Descripción:</strong> {{ $photo->descripcion }}
            </div>
        </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Reporte generado automáticamente el {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p>SAT INDUSTRIALES - Sistema de Monitoreo de Proyectos</p>
    </div>
</body>

</html>