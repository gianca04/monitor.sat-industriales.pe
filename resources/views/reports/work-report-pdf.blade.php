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
            color: #111;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #222;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #111;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #444;
            font-size: 14px;
        }

        .report-title {
            font-size: 20px;
            color: #111;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }

        .info-section {
            background-color: #f3f3f3;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #222;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            font-size: medium;
            width: 150px;
            color: #111;
        }

        .info-value {
            color: #222;
        }

        .photos-section {
            margin-top: 30px;
        }

        .section-title {
            font-size: 18px;
            color: #111;
            border-bottom: 2px solid #bbb;
            padding-bottom: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .photo-container {
            margin-bottom: 40px;
            page-break-inside: avoid;
            border: 1px solid #bbb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            background: #fafafa;
            overflow: hidden;
        }

        .photo-header {
            background: #222;
            padding: 16px 18px;
            border-bottom: 1px solid #bbb;
            color: #fff;
        }

        .photo-title {
            font-weight: bold;
            color: #fff;
            font-size: 16px;
            margin-bottom: 2px;
        }

        .photo-date {
            font-size: 12px;
            color: #eee;
        }

        .photo-image {
            display: block;
            margin: 0 auto;
            max-width: 90%;
            height: auto;
            max-height: 350px;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.08);
            object-fit: contain;
        }

        .photo-description {
            padding: 18px;
            background-color: #fff;
            color: #222;
            font-style: italic;
            border-top: 1px solid #bbb;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            color: #222;
            border-top: 1px solid #bbb;
            padding-top: 24px;
        }

        .page-break {
            page-break-before: always;
        }

        .summary-stats {
            display: flex;
            justify-content: space-around;
            background-color: #ededed;
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
            color: #111;
        }

        .stat-label {
            font-size: 12px;
            color: #444;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header" style="display: flex; align-items: center; justify-content: flex-start;">
        <div style="flex:0 0 auto;">
            <img src="{{ public_path('images/Logo2.png') }}" alt="Logo SAT"
                style="height: 48px; width: auto; margin-right: 18px;">
        </div>
        <div style="flex:1 1 auto;">
            <div class="report-title" style="font-size:22px;font-weight:bold;">{{ $project->name }}</div>
            <div class="report-title" style="font-size:16px;font-weight:bold;">Reporte #{{ $workReport->id }}</div>
        </div>
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
            <div class="stat-number">
                {{ $photos->groupBy(function ($item) {
    return $item->taken_at->format('Y-m-d'); })->count() }}
            </div>
            <div class="stat-label">Días de Trabajo</div>
        </div>
    </div>

    <!-- Información General -->
    <div class="info-section">
        <h4 style="margin-top: 0; color: #000000;">Información del Reporte</h4>
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
        <h4 style="margin-top: 0; color: #000000;">Supervisor Responsable</h4>
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
        <h4 style="margin-top: 0; color: #000000;">Proyecto</h4>
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

    <!-- Fotos del Reporte -->
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
                    $imgPath = public_path('storage/' . $photo->photo_path);
                @endphp
                @if(file_exists($imgPath))
                    <div style="width:100%;text-align:center;padding:18px 0;background:#fff;">
                        <img src="{{ $imgPath }}" alt="Evidencia {{ $loop->iteration }}" class="photo-image">
                    </div>
                @else
                    <div style="padding:18px 0;text-align:center;background:#fff;">Imagen no disponible<br>{{ $imgPath }}</div>
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
        <p>SAT INDUSTRIALES - Monitor</p>
    </div>
</body>

</html>
