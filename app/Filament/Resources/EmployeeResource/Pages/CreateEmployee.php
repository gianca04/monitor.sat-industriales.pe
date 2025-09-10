<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si el toggle 'is_active' del usuario no está marcado,
        // nos aseguramos de que 'user_id' sea null y no intentamos crear un usuario.
        if (!($data['user']['is_active'] ?? false)) {
            $data['user_id'] = null;
            unset($data['user']); // Eliminamos los datos del subformulario 'user' si no se va a crear
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $user = null;
            // Solo creamos el usuario si 'is_active' era true en el formulario.
            // Si $data['user'] no existe, significa que mutateFormDataBeforeCreate ya lo manejó
            // y no se debe crear un usuario.
            if (isset($data['user']) && ($data['user']['is_active'] ?? false)) {
                $userData = $data['user'];
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'role' => $userData['role'],
                    'password_confirmation' => $userData['role'],
                    'password' => Hash::make($userData['password']), // Usar Hash::make()
                    'is_active' => true, // Siempre true si se crea aquí

                ]);
                $data['user_id'] = $user->id;
            } else {
                $data['user_id'] = null; // Asegurarse de que no haya un user_id si no se creó usuario
            }

            // Eliminar el sub-array 'user' para evitar problemas al crear el Employee
            unset($data['user']);

            // Crear el empleado
            return static::getModel()::create($data);
        });
    }

    protected function getRedirectUrl(): string
    {
        // Opcional: Redirigir a la página de edición del empleado recién creado
        // o a la lista de empleados.
        return $this->getResource()::getUrl('index');
    }
}
