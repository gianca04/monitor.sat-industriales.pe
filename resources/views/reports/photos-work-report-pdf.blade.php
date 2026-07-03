<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Trabajo - {{ $workReport->name }}</title>
</head>

<body>

    {{-- EVIDENCIAS FOTOGRÁFICAS --}}
    @foreach ($photos as $index => $photo)
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
                {{-- FOTO ANTES --}}
                <td class="evidence-td">
                    <div class="evidence-img-container">
                        @if ($hasBefore)
                        <img class="photo-image" src="{{ public_path('storage/' . $photo->before_work_photo_path) }}" alt="Evidencia inicial {{ $loop->iteration }}">
                        @else
                        <img class="photo-image" src="{{ $defaultImg }}" alt="Sin imagen inicial disponible">
                        @endif
                    </div>
                </td>

                {{-- FOTO DESPUÉS --}}
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

            {{-- DESCRIPCIONES --}}
            <tr>
                <td class="evidence-desc">
                    <div class="desc-text">
                        {!! $photo->before_work_descripcion ?? 'Sin descripción' !!}
                    </div>
                </td>
                <td class="evidence-desc">
                    <div class="desc-text">
                        {!! $photo->descripcion ?? 'Sin descripción' !!}
                    </div>
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
                    <div class="desc-text">
                        {!! $photo->before_work_descripcion ?? 'Sin descripción' !!}
                    </div>
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
                    <div class="desc-text">
                        {!! $photo->descripcion ?? 'Sin descripción' !!}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    @endif
    @endforeach

    {{-- TABLA DE FIRMAS --}}
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
                <br><br><br><br>
                <span class="no-data">_____________________________________</span>
                @endif
                <div class="signature-label">
                    Gerencia / Subgerencia
                </div>
            </td>

            <td class="signature-cell">
                @if ($workReport->supervisor_signature)
                <img src="{{ $workReport->supervisor_signature }}" alt="Firma del Supervisor" class="signature-image" />
                @else
                <br><br><br><br>
                <span class="no-data">_____________________________________</span>
                @endif
                <div class="signature-label">
                    Supervisión / Técnico
                </div>
            </td>
        </tr>
    </table>

    <div>
        <p class="footer">SAT INDUSTRIALES - Monitor</p>
    </div>

    <style>
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

        /* --- IMÁGENES --- */
        .photo-image {
            display: block;
            margin: 0 auto;
            max-width: 90%;
            max-height: 380px;
            object-fit: contain;
        }

        .evidence-table {
            width: 100%;
        }

        .evidence-th,
        .evidence-td {
            width: 50%;
            text-align: center;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .evidence-th {
            background-color: #f2f2f2;
        }

        /* --- DESCRIPCIÓN LIMITADA --- */
        .evidence-desc {
            text-align: center;
            padding: 6px;
            border: 1px solid #ddd;
        }

        .desc-text {
            font-style: italic;
            font-size: 13px;
            line-height: 1.3;
            max-height: 50px;
            /* 🔹 límite de alto */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
        }

        /* --- FIRMAS --- */
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
            color: #000;
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
            max-width: 180px;
            object-fit: contain;
        }

        .signature-label {
            margin-top: 15px;
            font-size: 10px;
            color: #7f8c8d;
        }

        /* --- FOOTER --- */
        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 13px;
            border-top: 1px solid #bbb;
            padding-top: 24px;
        }
    </style>
</body>

</html>