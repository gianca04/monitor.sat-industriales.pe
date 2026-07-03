<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado de Visitas - {{ $request->reference }}</title>
</head>

<body>
    {{-- TABLA DE CABECERA DEL DOCUMENTO --}}
    <table class="header-table">
        <thead>
            <tr>
                <th class="info-table-header-col">
                    <div class="empresa-info">
                        <h1>SAT INDUSTRIALES</h1>
                        <p>Servicios de Asesoría Técnica</p>
                        <p class="direccion">Dirección: Calle Los Industriales 123, Lima</p>
                        <p>Teléfono: (01) 456-7890</p>
                        <p>Email: info@sat-industriales.pe</p>
                    </div>
                </th>
                <th class="info-table-header-col">
                    <div class="empresa-info">
                        <table>
                            <tr>
                                <td class="info-cell-gris">Fecha de Generación:</td>
                                <td class="info-cell">{{ $generatedAt->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="info-cell-gris">Tipo de Reporte:</td>
                                <td class="info-cell">Consolidado de Visitas</td>
                            </tr>
                            <tr>
                                <td class="info-cell-gris">Referencia:</td>
                                <td class="info-cell">{{ $request->reference }}</td>
                            </tr>
                        </table>
                    </div>
                </th>
                <th class="info-table-header-col">
                    <div class="empresa-info">
                        @if(file_exists(public_path('images/logo.png')))
                            <img src="{{ public_path('images/logo.png') }}" alt="Logo SAT" class="header-logo">
                        @else
                            <div class="header-logo-placeholder">SAT</div>
                        @endif
                    </div>
                </th>
            </tr>
        </thead>
    </table>

    <table class="info-table">
        <thead>
            <tr>
                <th class="info-cell">Referencia del Request</th>
                <th class="info-cell">Fecha de Visita</th>
                <th class="info-cell">Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="info-cell">{{ $request->reference }}</td>
                <td class="info-cell">{{ $request->visit_date ? $request->visit_date->format('d/m/Y') : 'N/A' }}</td>
                <td class="info-cell">{{ ucfirst($request->status) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE CLIENTE --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th class="basic-info-table-header">Cliente</th>
                <td class="basic-info-table-cell">{{ $request->subClient->client->business_name ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE SUBCLIENTE --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th class="basic-info-table-header">Subcliente</th>
                <td class="basic-info-table-cell">{{ $request->subClient->name ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE DESCRIPCIÓN DEL REQUEST --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th class="basic-info-table-header">Descripción del Request</th>
            </tr>
            <tr>
                <td class="basic-info-table-cell">{{ $request->description ?? 'Sin descripción' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE COTIZADOR Y SUPERVISOR --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th class="basic-info-table-header">Cotizador</th>
                <td class="basic-info-table-cell">{{ $request->cotizador->full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th class="basic-info-table-header">Supervisor</th>
                <td class="basic-info-table-cell">{{ $request->supervisor->full_name ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- RESUMEN ESTADÍSTICAS DEL REQUEST --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th class="basic-info-table-header">Total de Visitas</th>
                <td class="basic-info-table-cell">{{ $visits->count() }}</td>
            </tr>
            <tr>
                <th class="basic-info-table-header">Total de Fotos</th>
                <td class="basic-info-table-cell">{{ $allPhotos->count() }}</td>
            </tr>
            <tr>
                <th class="basic-info-table-header">Rango de Fechas</th>
                <td class="basic-info-table-cell">
                    {{ $visits->min('report_date') ? \Carbon\Carbon::parse($visits->min('report_date'))->format('d/m/Y') : 'N/A' }} -
                    {{ $visits->max('report_date') ? \Carbon\Carbon::parse($visits->max('report_date'))->format('d/m/Y') : 'N/A' }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ÍNDICE DE VISITAS --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th colspan="4" class="basic-info-text-header">Índice de Visitas</th>
            </tr>
            <tr>
                <th class="basic-info-text-header">N°</th>
                <th class="basic-info-text-header">Nombre de la Visita</th>
                <th class="basic-info-text-header">Fecha</th>
                <th class="basic-info-text-header">Empleado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visits as $index => $visit)
            <tr>
                <td class="basic-info-text-cell">{{ $index + 1 }}</td>
                <td class="basic-info-text-cell">{{ $visit->name }}</td>
                <td class="basic-info-text-cell">{{ $visit->report_date ? \Carbon\Carbon::parse($visit->report_date)->format('d/m/Y') : 'N/A' }}</td>
                <td class="basic-info-text-cell">{{ $visit->employee->full_name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SALTO DE PÁGINA ANTES DE LAS VISITAS --}}
    <div class="page-break"></div>

    {{-- ITERACIÓN POR CADA VISITA --}}
    @foreach($visits as $visitIndex => $visit)

        {{-- TÍTULO DE LA VISITA --}}
        <div class="report-header">
            <h2>Visita {{ $visitIndex + 1 }}: {{ $visit->name }}</h2>
            <p class="report-date">Fecha: {{ $visit->report_date ? \Carbon\Carbon::parse($visit->report_date)->format('d/m/Y') : 'N/A' }}</p>
            @if($visit->start_time && $visit->end_time)
                <p class="report-date">Horario: {{ \Carbon\Carbon::parse($visit->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($visit->end_time)->format('H:i') }}</p>
            @endif
        </div>

        {{-- INFORMACIÓN DEL EMPLEADO --}}
        <table class="basic-info-table">
            <tbody>
                <tr>
                    <th class="basic-info-table-header">Empleado Responsable</th>
                    <td class="basic-info-table-cell">{{ $visit->employee->full_name ?? 'N/A' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- DESCRIPCIÓN DE LA VISITA --}}
        @if($visit->description)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th class="basic-info-text-header">Descripción de la Actividad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="basic-info-text-cell">{{ $visit->description }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- SUGERENCIAS --}}
        @if($visit->suggestions)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th class="basic-info-text-header">Sugerencias</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="basic-info-text-cell">{{ $visit->suggestions }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- HERRAMIENTAS --}}
        @if($visit->tools)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th class="basic-info-text-header">Herramientas Utilizadas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="basic-info-text-cell">{{ $visit->tools }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- MATERIALES --}}
        @if($visit->materials)
        <table class="basic-info-text">
            <thead>
                <tr>
                    <th class="basic-info-text-header">Materiales Utilizados</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="basic-info-text-cell">{{ $visit->materials }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- EVIDENCIAS FOTOGRAFICAS DE LA VISITA --}}
        @if($visit->visitPhotos->count() > 0)
            @foreach ($visit->visitPhotos as $photoIndex => $photo)
                <table class="evidence-table">
                    <thead>
                        <tr>
                            <th class="evidence-th">Evidencia Fotográfica</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="evidence-td">
                                <div class="evidence-img-container">
                                    @php
                                        $imagePath = $photo->photo_path
                                            ? public_path('storage/' . $photo->photo_path)
                                            : null;
                                        $defaultImg = public_path('images/image-no-found.png');
                                    @endphp

                                    @if ($imagePath && file_exists($imagePath))
                                        <img class="photo-image" src="{{ $imagePath }}"
                                            alt="Evidencia {{ $loop->iteration }}">
                                    @else
                                        <img class="photo-image" src="{{ $defaultImg }}" alt="Sin imagen disponible">
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="evidence-desc">
                                {!! $photo->descripcion ?? 'Sin descripción' !!}
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                        <td>No hay evidencias fotográficas para esta visita.</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- FIRMAS DE LA VISITA --}}
        @if($visit->employee_signature || $visit->manager_signature)
        <div class="divider"></div>
        <table class="signature-table">
            <tr>
                @if($visit->employee_signature)
                <td class="signature-cell">
                    <div class="signature-label">Firma del Empleado</div>
                    @if(\Storage::exists($visit->employee_signature))
                        <img src="{{ \Storage::url($visit->employee_signature) }}" alt="Firma Empleado" class="signature-image">
                    @else
                        <div class="signature-placeholder">Firma no disponible</div>
                    @endif
                </td>
                @endif
                @if($visit->manager_signature)
                <td class="signature-cell">
                    <div class="signature-label">Firma del Gerente</div>
                    @if(\Storage::exists($visit->manager_signature))
                        <img src="{{ \Storage::url($visit->manager_signature) }}" alt="Firma Gerente" class="signature-image">
                    @else
                        <div class="signature-placeholder">Firma no disponible</div>
                    @endif
                </td>
                @endif
            </tr>
        </table>
        @endif

        {{-- SALTO DE PÁGINA ENTRE VISITAS (excepto la última) --}}
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
            text-align: center;
            vertical-align: top;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .header-logo {
            max-width: 120px;
            max-height: 80px;
            margin: 0 auto;
        }

        .header-logo-placeholder {
            width: 120px;
            height: 80px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: #666;
            margin: 0 auto;
        }

        .empresa-info {
            text-align: center;
        }

        .empresa-info h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .empresa-info p,
        .direccion {
            margin: 2px 0;
            font-size: 10px;
            color: #666;
        }

        .empresa-info table {
            width: 100%;
            margin-top: 10px;
        }

        .info-cell-gris {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 40%;
        }

        .info-cell {
            background-color: #ffffff;
        }

        /* Solo para la primera tabla .info-table debajo del header */
        .info-table.info-table-header th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
        }

        .info-table.info-table-header th:first-child {
            width: 40%;
        }

        .info-table.info-table-header th:nth-child(2) {
            width: 30%;
        }

        .info-table.info-table-header th:last-child {
            width: 30%;
        }

        .empresa-info td,
        .empresa-info th,
        .info-table td.info-cell,
        .info-table th.info-cell {
            border: none;
            padding: 4px;
        }

        .info-table {
            margin-bottom: 15px;
        }

        .info-table td.info-cell {
            text-align: center;
            font-weight: bold;
        }

        .cliente-nombre {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
        }

        .photo-image {
            display: block;
            margin: 0 auto;
            max-width: 90%;
            max-height: 450px;
            object-fit: contain;
        }

        .photo-placeholder {
            width: 200px;
            height: 150px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }

        /* Estilos para la tabla de evidencias fotográficas */
        .evidence-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .evidence-th,
        .evidence-td {
            text-align: center;
            border: 1px solid #ddd;
        }

        .evidence-th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .evidence-desc {
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            font-style: italic;
        }

        .evidence-img-container {
            text-align: center;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .signature-table {
            margin-top: 20px;
            width: 100%;
        }

        .signature-table th,
        .signature-table td {
            border: none;
            padding: 10px;
            text-align: center;
        }

        .signature-table th {
            font-weight: bold;
        }

        .signature-cell {
            width: 50%;
            vertical-align: top;
        }

        .signature-image {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
        }

        .signature-placeholder {
            width: 200px;
            height: 60px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
        }

        .basic-info-table {
            margin-bottom: 15px;
        }

        .basic-info-table th,
        .basic-info-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .basic-info-table th {
            background-color: #e9ecef;
            font-weight: bold;
            width: 30%;
        }

        .basic-info-table tr:last-child th,
        .basic-info-table tr:last-child td {
            border-bottom: 2px solid #333;
        }

        .basic-info-text {
            margin-bottom: 15px;
        }

        .basic-info-text th,
        .basic-info-text td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .basic-info-text th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
        }

        .basic-info-table-header {
            background-color: #e9ecef;
            font-weight: bold;
            width: 30%;
        }

        .basic-info-table-cell {
            background-color: #ffffff;
        }

        .basic-info-text-header {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: center;
        }

        .basic-info-text-cell {
            background-color: #ffffff;
            vertical-align: top;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .header-table {
            margin-bottom: 20px;
        }

        /* Estilos específicos del reporte consolidado */
        .page-break {
            page-break-before: always;
        }

        .report-header {
            margin-bottom: 20px;
            text-align: center;
        }

        .report-header h2 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .report-date {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }

        .reports-index {
            margin-bottom: 20px;
        }

        .reports-index li {
            margin-bottom: 5px;
        }

        .report-details {
            margin-bottom: 15px;
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
        }

        .divider {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
    </style>
</body>

</html>