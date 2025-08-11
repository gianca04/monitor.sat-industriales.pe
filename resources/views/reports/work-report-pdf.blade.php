<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Trabajo - {{ $workReport->name }}</title>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th>
                    <img src="{{ public_path('images/Logo2.png') }}" alt="Logo" style="width: 150px; height: auto;">
                </th>
                <th>
                    <div>{{ $project->name }}</div>
                </th>
                <th>
                    @if($project->subClient->client->logo && file_exists(public_path('storage/' . $project->subClient->client->logo)))
                        <img src="{{ public_path('storage/' . $project->subClient->client->logo) }}" alt="Logo Cliente" style="width: 150px; height: auto;">
                    @else
                        <div style="text-align: center; font-weight: bold; font-size: 14px; padding: 20px;">
                            {{ $project->subClient->client->business_name ?? 'Cliente' }}
                        </div>
                    @endif
                </th>
            </tr>
        </thead>
    </table>

    <br>

    {{-- TABLA DE CLIENTE --}}
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>SubCliente</th>
                <th>RUC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $project->subClient->client->business_name ?? 'N/A' }}</td>
                <td>{{ $project->subClient->name ?? 'N/A' }}</td>
                <td>{{ $project->subClient->client->document_number ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    {{-- TABLA DE DESCRIPCIÓN DE PROYECTO --}}
    <table>
        <thead>
            <tr>
                <th>Fecha de inicio del proyecto</th>
                <th>Fecha de fin del proyecto</th>
                <th>TDR</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $workReport->project->start_date->format('d/m/Y') ?? 'N/A' }}</td>
                <td>{{ $project->end_date->format('d/m/Y') ?? 'N/A' }}</td>
                <td>{{ $project->quote->TDR ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABLA DE INFORMACIÓN DEL REPORTE --}}
    <table>
        <thead>
            <tr>
                <th>Nombre de la Actividad</th>
                <th>Supervisor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $workReport->name ?? 'N/A' }}</td>
                <td>{{ $workReport->employee->full_name ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>
    <table>
        <thead>
            <tr>
                <th>Fecha de Reporte</th>
                <th>Hora de inicio</th>
                <th>Hora de finalización</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $workReport->report_date->format('d/m/Y') ?? 'N/A' }}</td>
                <td>{{ $workReport->start_time ?? 'N/A' }}</td>
                <td>{{ $workReport->end_time ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    {{-- TABLA DE DESCRIPCIÓN DE ACTIVIDAD --}}
    <table>
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

    <br>

    {{-- TABLA DE sugerencias --}}
    <table>
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

    <br>


    {{-- TABLA DE HERRAMIENTAS --}}
    <table>
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

    <br>


    {{-- TABLA DE HERRAMIENTAS --}}
    <table>
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

    <br>


    {{-- TABLA DE HERRAMIENTAS --}}
    <table>
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

    <br>

    {{-- EVIDENCIAS FOTOGRAFICAS --}}

    @foreach($photos as $index => $photo)
        <table>
            <thead>
                <tr>
                    <th>Evidencia Inicial</th>
                    <th>Evidencia del Trabajo Realizado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div>
                            @php
                                $imgbefore_work_photo_path = public_path('storage/' . $photo->before_work_photo_path);
                            @endphp
                            @if(file_exists($imgbefore_work_photo_path))
                                <img class="photo-image" src="{{ $imgbefore_work_photo_path }}"
                                    alt="Evidencia {{ $loop->iteration }}">
                            @else
                                <div>Imagen no disponible<br>{{ $imgbefore_work_photo_path }}</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div>
                            @php
                                $imgPath = public_path('storage/' . $photo->photo_path);
                            @endphp
                            @if(file_exists($imgPath))
                                <img class="photo-image" src="{{ $imgPath }}" alt="Evidencia {{ $loop->iteration }}">
                            @else
                                <div>Imagen no disponible<br>{{ $imgPath }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        {!! $photo->before_work_descripcion ?? 'N/A' !!}
                    </td>
                    <td>
                        {!! $photo->descripcion ?? 'N/A' !!}
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <br>

    {{-- TABLA DE FIRMAS --}}
    <table class="half-width">
        <thead>
            <tr>
                <th>Firma del gerente / subgerente</th>
                <th>Firma del Validado por supervisor / técnico</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @if($workReport->manager_signature)
                        <img src="{{ $workReport->manager_signature }}" alt="Firma del Gerente" class="photo-image" />
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($workReport->supervisor_signature)
                        <img src="{{ $workReport->supervisor_signature }}" alt="Firma del Supervisor" class="photo-image" />
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        </tbody>
    </table>


    <div>
        <p class="footer">Reporte generado automáticamente el {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <p class="footer">SAT INDUSTRIALES - Monitor</p>
    </div>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }

        .photo-image {
            display: block;
            margin: 0 auto;
            max-width: 90%;
            max-height: 350px;
            object-fit: contain;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            border-top: 1px solid #bbb;
            padding-top: 24px;
        }
    </style>
</body>

</html>
