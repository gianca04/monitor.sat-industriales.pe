<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado - {{ $project->name }}</title>
</head>

<body>
    {{-- TABLA DE CABECERA DEL DOCUMENTO --}}
    <table class="header-table">
        <thead>
            <tr>
                <th>
                    <img src="{{ public_path('images/Logo2.png') }}" alt="Logo" class="header-logo">
                </th>
                <th>
                    <div class="empresa-info">
                        <h1>SAT INDUSTRIALES S.A.C</h1>
                        <p class="direccion">
                            Dirección: Km 9 de la expansión Av. José Aguilar Santisteban. (Pista nueva Curumuy - Fundo
                            las Mercedes)
                        </p>
                        <table class="info-table">
                            <tbody>
                                <tr>
                                    <td class="info-cell">RUC: <a href="20539249640">20539249640</a></td>
                                    <td class="info-cell">Teléfono: <a href="tel:959730981">959 730 981</a></td>
                                    <td class="info-cell">Correo: <a
                                            href="mailto:operaciones@sat-industriales.pe">operaciones@sat-industriales.pe</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </th>
                <th>
                    @if ($project->subClient->client->logo && file_exists(public_path('storage/' . $project->subClient->client->logo)))
                        <img src="{{ public_path('storage/' . $project->subClient->client->logo) }}" alt="Logo Cliente"
                            class="header-logo">
                    @else
                        <div class="cliente-nombre">
                            {{ $project->subClient->client->business_name ?? 'Cliente' }}
                        </div>
                    @endif
                </th>
            </tr>
        </thead>
    </table>
    
    <table class="info-table">
        <thead>
            <tr>
                <th class="info-table-header-col">
                    <div class="info-cell-gris">
                        Generado: {{ $generatedAt->format('d/m/Y H:i') }}
                    </div>
                </th>
                <th class="info-table-header-col">
                    <h3>Reporte Consolidado de Trabajo</h3>
                </th>
                <th class="info-table-header-col">
                    <div class="info-cell-gris">
                        RC-{{ $project->id ?? 'N/A' }}
                    </div>
                </th>
            </tr>
        </thead>
    </table>

    {{-- TABLA DE CLIENTE --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Cliente</th>
                <td style="width: 350px;">{{ $project->subClient->client->business_name ?? 'N/A' }}</td>
                <th style="width: 150px;">RUC</th>
                <td colspan="2">{{ $project->subClient->client->document_number ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE SUBCLIENTE --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Tienda</th>
                <td style="width: 350px;">{{ $project->subClient->name ?? 'N/A' }}</td>
                <th style="width: 150px;">TDR</th>
                <td>{{ $project->quote->TDR ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE DESCRIPCIÓN DE PROYECTO --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Nombre del proyecto</th>
                <td colspan="3">{{ $project->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th style="width: 150px;">Fecha de inicio</th>
                <td style="width: 350px;">{{ $project->start_date ? $project->start_date->format('d/m/Y') : 'N/A' }}</td>
                <th style="width: 150px;">Fecha de fin</th>
                <td>{{ $project->end_date ? $project->end_date->format('d/m/Y') : 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- RESUMEN ESTADÍSTICAS DEL PROYECTO --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Total de Reportes</th>
                <td style="width: 200px;">{{ $workReports->count() }}</td>
                <th style="width: 150px;">Total de Evidencias</th>
                <td>{{ $allPhotos->count() }}</td>
            </tr>
            <tr>
                <th style="width: 150px;">Rango de Fechas</th>
                <td colspan="3">
                    @if($workReports->count() > 0)
                        {{ $workReports->first()->report_date }} - {{ $workReports->last()->report_date }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ÍNDICE DE REPORTES --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Índice de Reportes de Trabajo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <ol class="reports-index">
                        @foreach($workReports as $index => $report)
                            <li>
                                <strong>{{ $report->name }}</strong><br>
                                <span class="report-details">
                                    Fecha: {{ $report->report_date }} | 
                                    Supervisor: {{ $report->employee->full_name ?? 'N/A' }} | 
                                    Evidencias: {{ $report->photos->count() }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- SALTO DE PÁGINA ANTES DE LOS REPORTES --}}
    <div class="page-break"></div>

    {{-- ITERACIÓN POR CADA REPORTE DE TRABAJO --}}
    @foreach($workReports as $workReportIndex => $workReport)
        
        {{-- TÍTULO DEL REPORTE --}}
        <div class="report-header">
            <h2>Reporte {{ $workReportIndex + 1 }}: {{ $workReport->name }}</h2>
            <p class="report-date">Fecha: {{ $workReport->report_date }}</p>
        </div>

        {{-- INFORMACIÓN DEL REPORTE ESPECÍFICO --}}
        <table class="basic-info-table">
            <tbody>
                <tr>
                    <th style="width: 150px;">Supervisor</th>
                    <td>{{ $workReport->employee->full_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th style="width: 150px;">Horario</th>
                    <td>{{ $workReport->start_time ?? 'N/A' }} - {{ $workReport->end_time ?? 'N/A' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- DESCRIPCIÓN DE ACTIVIDAD --}}
        @if($workReport->description)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th>Descripción de actividad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{!! $workReport->description !!}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- SUGERENCIAS --}}
        @if($workReport->suggestions)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th>Sugerencias</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{!! $workReport->suggestions !!}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- HERRAMIENTAS --}}
        @if($workReport->tools)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th>Herramientas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{!! $workReport->tools !!}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- MATERIALES --}}
        @if($workReport->materials)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th>Materiales</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{!! $workReport->materials !!}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- PERSONAL --}}
        @if($workReport->personnel)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th>Personal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{!! $workReport->personnel !!}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- EVIDENCIAS FOTOGRAFICAS DEL REPORTE --}}
        @if($workReport->photos->count() > 0)
            @foreach ($workReport->photos as $photoIndex => $photo)
            @php
            $hasBefore = $photo->before_work_photo_path && file_exists(public_path('storage/' . $photo->before_work_photo_path));
            $hasAfter = $photo->photo_path && file_exists(public_path('storage/' . $photo->photo_path));
            $defaultImg = public_path('images/image-no-found.png');
            @endphp

            @if($hasBefore && $hasAfter)
            {{-- Two columns --}}
            <table class="evidence-table">
                <thead>
                    <tr>
                        <th class="evidence-th">Evidencia Inicial</th>
                        <th class="evidence-th">Evidencia del Trabajo Realizado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="evidence-td">
                            <div class="evidence-img-container">
                                @if ($hasBefore)
                                <img class="photo-image" src="{{ public_path('storage/' . $photo->before_work_photo_path) }}" alt="Evidencia inicial {{ $loop->iteration }}">
                                @else
                                <img class="photo-image" src="{{ $defaultImg }}" alt="Sin imagen inicial disponible">
                                @endif
                            </div>
                        </td>

                        <td class="evidence-td">
                            <div class="evidence-img-container">
                                @if ($hasAfter)
                                <img class="photo-image" src="{{ public_path('storage/' . $photo->photo_path) }}" alt="Evidencia final {{ $loop->iteration }}">
                                @else
                                <img class="photo-image" src="{{ $defaultImg }}" alt="Sin imagen final disponible">
                                @endif
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="evidence-desc">
                            {!! $photo->before_work_descripcion ?? 'Sin descripción' !!}
                        </td>
                        <td class="evidence-desc">
                            {!! $photo->descripcion ?? 'Sin descripción' !!}
                        </td>
                    </tr>
                </tbody>
            </table>
            @elseif($hasBefore)
            {{-- Only before --}}
            <table class="evidence-table">
                <thead>
                    <tr>
                        <th class="evidence-th" style="width: 100%;">Evidencia Inicial</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="evidence-td" style="width: 100%;">
                            <div class="evidence-img-container">
                                <img class="photo-image" src="{{ public_path('storage/' . $photo->before_work_photo_path) }}" alt="Evidencia inicial {{ $loop->iteration }}">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="evidence-desc" style="width: 100%;">
                            {!! $photo->before_work_descripcion ?? 'Sin descripción' !!}
                        </td>
                    </tr>
                </tbody>
            </table>
            @elseif($hasAfter)
            {{-- Only after --}}
            <table class="evidence-table">
                <thead>
                    <tr>
                        <th class="evidence-th" style="width: 100%;">Evidencia del Trabajo Realizado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="evidence-td" style="width: 100%;">
                            <div class="evidence-img-container">
                                <img class="photo-image" src="{{ public_path('storage/' . $photo->photo_path) }}" alt="Evidencia final {{ $loop->iteration }}">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="evidence-desc" style="width: 100%;">
                            {!! $photo->descripcion ?? 'Sin descripción' !!}
                        </td>
                    </tr>
                </tbody>
            </table>
            @endif
            @endforeach
        @else
            <table class="basic-info-text">
                <thead>
                    <tr>
                        <th>Evidencias Fotográficas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>No hay evidencias fotográficas para este reporte.</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- FIRMAS DEL REPORTE --}}
        @if($workReport->manager_signature || $workReport->supervisor_signature)
        <div class="divider"></div>
        <table class="signature-table">
            <tr>
                <th>Firma del Gerente / Subgerente</th>
                <th>Firma del Supervisor / Técnico</th>
            </tr>
            <tr>
                <td class="signature-cell">
                    @if ($workReport->manager_signature)
                        <img src="{{ $workReport->manager_signature }}" alt="Firma del Gerente" class="signature-image" />
                    @else
                        <br>
                        <br>
                        <br>
                        <br>
                        <span class="no-data">_____________________________________</span>
                    @endif
                    <div class="signature-label">
                        Gerencia / Subgerencia
                    </div>
                </td>
                <td class="signature-cell">
                    @if ($workReport->supervisor_signature)
                        <img src="{{ $workReport->supervisor_signature }}" alt="Firma del Supervisor"
                            class="signature-image" />
                    @else
                        <br>
                        <br>
                        <br>
                        <br>
                        <span class="no-data">_____________________________________</span>
                    @endif
                    <div class="signature-label">
                        Supervisión / Técnico
                    </div>
                </td>
            </tr>
        </table>
        @endif

        {{-- SALTO DE PÁGINA ENTRE REPORTES (excepto el último) --}}
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach

    <div>
        <p class="footer">SAT INDUSTRIALES - Monitor</p>
    </div>

    <style>
        /* Estilo para que las columnas de la tabla de cabecera tengan el mismo ancho y texto centrado */
        .info-table-header-col {
            width: 33.33%;
            text-align: center !important;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif !important;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 8px;
        }

        .header-logo {
            margin: 0 auto;
            display: block;
            width: 180px;
            height: auto;
        }

        .empresa-info {
            text-align: center;
        }

        .empresa-info h1 {
            margin-bottom: 6px;
        }

        .empresa-info p,
        .direccion {
            margin: 2px 40px 0px 40px;
            padding: 0 10px;
            font-weight: normal;
            font-size: 15px;
            text-align: center;
        }

        .empresa-info table {
            margin-left: auto;
            margin-right: auto;
        }

        .info-cell-gris {
            padding: 8px 18px;
            background-color: #fff;
            border: 3px solid #e2e2e2;
            border-radius: 20px;
            font-size: 15px;
            font-weight: normal;
        }

        /* Solo para la primera tabla .info-table debajo del header */
        .info-table.info-table-header th {
            width: 33.33%;
        }

        .info-table.info-table-header th:first-child {
            text-align: left;
        }

        .info-table.info-table-header th:nth-child(2) {
            text-align: center;
        }

        .info-table.info-table-header th:last-child {
            text-align: right;
        }

        /* Fin de estilos específicos para la tabla del header */

        .empresa-info td,
        .empresa-info th,
        .info-table td.info-cell,
        .info-table th.info-cell {
            text-align: center;
            font-weight: normal;
            font-size: 15px;
        }

        .info-table {
            width: 100%;
            margin-left: auto;
            margin-top: 4px !important;
            margin-right: auto;
            border-collapse: collapse;
        }

        .info-table td.info-cell {
            width: 33.33%;
            padding: 8px;
        }

        .cliente-nombre {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            padding: 20px;
        }

        .photo-image {
            display: block;
            margin: 0 auto;
            max-width: 90%;
            max-height: 450px;
            object-fit: contain;
        }

        /* Estilos para la tabla de evidencias fotográficas */
        .evidence-table {
            width: 100%;
        }

        .evidence-th,
        .evidence-td {
            width: 50%;
            text-align: center;
            border: 1px solid #ddd;
        }

        .evidence-th {
            background-color: #f2f2f2;
        }

        .evidence-desc {
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            font-style: italic;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            border-top: 1px solid #bbb;
            padding-top: 24px;
        }

        .signature-table {
            margin-top: 40px;
            page-break-inside: avoid;
            border-radius: 8px;
            overflow: hidden;
        }

        .signature-table th,
        .signature-table td {
            width: 50%;
            text-align: center;
        }

        .signature-table th {
            background-color: #e2e2e2;
            color: rgb(0, 0, 0);
            padding: 18px;
            font-size: 12px;
        }

        .signature-cell {
            text-align: center;
            padding: 30px 20px;
            height: 120px;
            vertical-align: top;
            background-color: #ffffff;
            border: 2px dashed #bdc3c7;
        }

        .signature-image {
            max-width: 190px;
            object-fit: contain;
        }

        .basic-info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #bbb;
            overflow: hidden;
        }

        .basic-info-table th,
        .basic-info-table td {
            border: 1px solid #ddd;
            padding: 10px 8px;
        }

        .basic-info-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .basic-info-table tr:last-child th,
        .basic-info-table tr:last-child td {
            border-bottom: none;
        }

        .basic-info-text {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cccccc;
            margin-bottom: 20px;
        }

        .basic-info-text th,
        .basic-info-text td {
            border: 1px solid #cccccc;
            padding: 8px;
        }

        .basic-info-text th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .signature-label {
            margin-top: 15px;
            font-size: 10px;
            color: #7f8c8d;
        }

        .header-table {
            margin-bottom: 0px;
        }

        /* Estilos específicos del reporte consolidado */
        .page-break {
            page-break-before: always;
        }

        .report-header {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 5px solid #666;
        }

        .report-header h2 {
            margin: 0 0 5px 0;
            color: #000000ff;
        }

        .report-date {
            margin: 0;
            font-weight: bold;
            color: #666;
        }

        .reports-index {
            padding-left: 20px;
        }

        .reports-index li {
            margin-bottom: 10px;
        }

        .report-details {
            font-size: 13px;
            color: #666;
        }

        .no-data {
            color: #999;
            font-style: italic;
        }
    </style>
</body>

</html>