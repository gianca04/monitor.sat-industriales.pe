<?php

namespace App\Services;

use App\Models\WorkReport;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WorkReportService
{
  /**
  * Crear un WorkReport con sus Photos asociadas.
  *
  * @param array $workReportData Datos del WorkReport.
  * @param array $photosData Datos de las Photos (ahora contienen los objetos UploadedFile).
  * @return WorkReport
  * @throws \Exception
  */
  public function createWorkReportWithPhotos(array $workReportData, array $photosData): WorkReport
  {
    return DB::transaction(function () use ($workReportData, $photosData) {
      // Crear el WorkReport
      $workReport = WorkReport::create($workReportData);

      // Crear las Photos asociadas
      foreach ($photosData as $photoData) {
        // La asignación de 'photo' y 'before_work_photo' ya la hizo el controlador.
        if (isset($photoData['photo']) && $photoData['photo']->isValid()) {
          $photoData['photo_path'] = $photoData['photo']->store('photos', 'public');
          unset($photoData['photo']); // Limpiamos el objeto UploadedFile
        }

        if (isset($photoData['before_work_photo']) && $photoData['before_work_photo']->isValid()) {
          $photoData['before_work_photo_path'] = $photoData['before_work_photo']->store('photos/before', 'public');
          unset($photoData['before_work_photo']); // Limpiamos el objeto UploadedFile
        }

        $photoData['work_report_id'] = $workReport->id;
        Photo::create($photoData);
      }

      // Recargar relaciones
      $workReport->load(['employee.position', 'project.subClient', 'project.client', 'photos']);

      return $workReport;
    });
  }
}