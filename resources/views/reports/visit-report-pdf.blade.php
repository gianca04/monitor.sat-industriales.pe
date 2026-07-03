<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Visita - {{ $visit->name }}</title>
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
                    @if ($mainClient && $mainClient->logo && file_exists(public_path('storage/' . $mainClient->logo)))
                    <img src="{{ public_path('storage/' . $mainClient->logo) }}" alt="Logo Cliente"
                        class="header-logo">
                    @else
                    <div class="cliente-nombre">
                        {{ $mainClient->business_name ?? 'Cliente' }}
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
                    <h3>Reporte de Visita</h3>
                </th>
                <th class="info-table-header-col">
                    <div class="info-cell-gris">
                        RV-{{ $visit->id ?? 'N/A' }}
                    </div>
                </th>
            </tr>
        </thead>
    </table>

    {{-- TABLA DE INFORMACIÓN BÁSICA DE LA VISITA --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Cliente</th>
                <td style="width: 350px;">{{ $mainClient->business_name ?? 'N/A' }}</td>
                <th style="width: 150px;">RUC</th>
                <td colspan="2">{{ $mainClient->document_number ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

     {{-- TABLA DE SUBCLIENTE --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Tienda</th>
                <td style="width: 350px;">{{ $mainSubClient->name ?? 'N/A' }}</td>
                <th style="width: 150px;">TDR</th>
                <td>{{ $project->quote->TDR ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE INFORMACIÓN DEL CLIENTE (si existe) --}}
    @if($mainClient)
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Cliente</th>
                <td style="width: 350px;">{{ $mainClient->business_name ?? 'N/A' }}</td>
                <th style="width: 150px;">RUC/DNI</th>
                <td colspan="2">{{ $mainClient->document_number ?? 'N/A' }}</td>
            </tr>
            @if($mainSubClient)
            <tr>
                <th style="width: 150px;">Sede/Tienda</th>
                <td style="width: 350px;">{{ $mainSubClient->name ?? 'N/A' }}</td>
                <th style="width: 150px;">Dirección</th>
                <td colspan="2">{{ $mainSubClient->address ?? 'N/A' }}</td>
            </tr>
            @endif
        </tbody>
    </table>
    @endif

    {{-- TABLA DE INFORMACIÓN DE LA VISITA --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Nombre de la Visita</th>
                <td colspan="3">{{ $visit->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th style="width: 150px;">Fecha del Reporte</th>
                <td style="width: 350px;">{{ $visit->report_date ?? 'N/A' }}</td>
                <th style="width: 150px;">Horario</th>
                <td>{{ $visit->start_time ?? 'N/A' }} - {{ $visit->end_time ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- RESUMEN DE LA VISITA --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Total de Evidencias</th>
                <td style="width: 200px;">{{ $visitPhotos->count() }}</td>
                <th style="width: 150px;">Solicitudes Relacionadas</th>
                <td>{{ $requests->count() }}</td>
            </tr>
        </tbody>
    </table>

    {{-- SOLICITUDES RELACIONADAS --}}
    @if($requests->count() > 0)
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Solicitudes Relacionadas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <ol class="reports-index">
                        @foreach($requests as $request)
                        <li>
                            <strong>{{ $request->reference }}</strong><br>
                            <span class="report-details">
                                Descripción: {{ Str::limit($request->description, 100) ?? 'N/A' }} |
                                Estado: {{ $request->status ?? 'N/A' }}
                                @if($request->subClient)
                                | Cliente: {{ $request->subClient->client->business_name ?? 'N/A' }}
                                | Sede: {{ $request->subClient->name ?? 'N/A' }}
                                @endif
                            </span>
                        </li>
                        @endforeach
                    </ol>
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- INFORMACIÓN DETALLADA DE LA VISITA --}}
    
    {{-- DESCRIPCIÓN DE LA VISITA --}}
    @if($visit->description)
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Descripción de la Visita</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $visit->description !!}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- SUGERENCIAS --}}
    @if($visit->suggestions)
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Sugerencias</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $visit->suggestions !!}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- HERRAMIENTAS --}}
    @if($visit->tools)
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Herramientas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $visit->tools !!}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- MATERIALES --}}
    @if($visit->materials)
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Materiales</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $visit->materials !!}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- PERSONAL --}}
    @if($visit->personnel)
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Personal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $visit->personnel !!}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- EVIDENCIAS FOTOGRAFICAS DE LA VISITA --}}
    @if($visitPhotos->count() > 0)
    @foreach ($visitPhotos as $photoIndex => $photo)
    <table class="evidence-table">
        <thead>
            <tr>
                <th class="evidence-th" colspan="2">Evidencia Fotográfica #{{ $loop->iteration }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="evidence-td" colspan="2">
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
                <td class="evidence-desc" colspan="2">
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
    @if($visit->manager_signature || $visit->employee_signature)
    <div class="divider"></div>
    <table class="signature-table">
        <tr>
            <th>Firma del Gerente / Subgerente</th>
            <th>Firma del Empleado</th>
        </tr>
        <tr>
            <td class="signature-cell">
                @if ($visit->manager_signature)
                <img src="{{ $visit->manager_signature }}" alt="Firma del Gerente" class="signature-image" />
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
                @if ($visit->employee_signature)
                <img src="{{ $visit->employee_signature }}" alt="Firma del Empleado"
                    class="signature-image" />
                @else
                <br>
                <br>
                <br>
                <br>
                <span class="no-data">_____________________________________</span>
                @endif
                <div class="signature-label">
                    {{ $employee->first_name ?? 'Empleado' }} {{ $employee->last_name ?? '' }}
                </div>
            </td>
        </tr>
    </table>
    @endif

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
            border-left: 5px solid #007bff;
        }

        .report-header h2 {
            margin: 0 0 5px 0;
            color: #007bff;
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