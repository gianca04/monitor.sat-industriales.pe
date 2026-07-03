<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Trabajo - {{ $workReport->name }}</title>
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
    {{-- TABLA DE INFORMACIÓN DEL REPORTE --}}
    <table class="info-table">
        <thead>
            <tr>
                <th class="info-table-header-col">
                    <div class="info-cell-gris">
                        Generado: {{ $workReport->created_at->format('d/m/Y H:i') ?? 'N/A' }}
                    </div>
                </th>
                <th class="info-table-header-col">
                    <h3>Informe Técnico</h3>
                </th>
                <th class="info-table-header-col">
                    <div class="info-cell-gris">
                        IT-{{ $workReport->id ?? 'N/A' }}
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
                {{-- Usamos colspan para que el dato ocupe el resto del espacio --}}
                <td colspan="3">{{ $workReport->project->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th style="width: 150px;">Fecha de inicio</th>
                <td style="width: 350px;">{{ $workReport->project->start_date->format('d/m/Y') ?? 'N/A' }}</td>
                <th style="width: 150px;">Fecha de fin</th>
                <td>{{ $project->end_date->format('d/m/Y') ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE INFORMACIÓN DEL REPORTE --}}
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Nombre de la Actividad</th>
                <td>{{ $workReport->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th style="width: 150px;">Supervisor</th>
                <td>{{ $workReport->employee->full_name ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>
    <table class="basic-info-table">
        <tbody>
            <tr>
                <th style="width: 150px;">Fecha de Reporte</th>
                <td>{{ $workReport->report_date ?? 'N/A' }}</td>
                <th style="width: 150px;">H Inicio</th>
                <td>{{ $workReport->start_time ?? 'N/A' }}</td>
                <th style="width: 150px;">H Fin</th>
                <td>{{ $workReport->end_time ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE DESCRIPCIÓN DE ACTIVIDAD --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Descripción de actividad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $workReport->description ?? 'N/A' !!}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE sugerencias --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Sugerencias</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $workReport->suggestions ?? 'N/A' !!}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE HERRAMIENTAS --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Herramientas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $workReport->tools ?? 'N/A' !!}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE MATERIALES --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Materiales</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $workReport->materials ?? 'N/A' !!}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE PERSONAL --}}
    <table class="basic-info-text">
        <thead>
            <tr>
                <th>Personal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{!! $workReport->personnel ?? 'N/A' !!}</td>
            </tr>
        </tbody>
    </table>
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

        .empresa-info h2 {
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
            max-height: 400px;
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
    </style>
</body>

</html>
