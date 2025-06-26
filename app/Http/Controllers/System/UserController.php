<?php
namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\UserRequest;
use App\Http\Resources\System\UserResource;
use App\Models\System\User;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function create()
    {
        $currentUserId = auth()->id();
        return view('system.users.form', compact('currentUserId'));
    }

    public function record()
    {
        // Obtener el usuario autenticado actualmente
        $user = auth()->user();

        // Retornar el usuario como un recurso de API
        return new UserResource($user);
    }

    public function records()
    {
        $users = User::all();
        return UserResource::collection($users);
    }

    public function store(UserRequest $request)
    {
        // Obtiene el ID del usuario desde la solicitud, puede ser null si es un nuevo usuario
        $id = $request->input('id');

        if ($id) {
            // Actualizar un usuario existente
            $user = User::findOrFail($id); // Cambiado a findOrFail para asegurar que el usuario exista
            // Genera un nuevo token cada vez que se actualiza el usuario
            $user->api_token = Str::random(60); // Genera un nuevo token único
        } else {
            // Crear un nuevo usuario
            $user = new User;
            // Genera un token para nuevos usuarios
            $user->api_token = Str::random(60); // Genera un token único para nuevos usuarios
        }

        // Asignar campos comunes
        $user->email = $request->input('email');
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');

        // Asignar o actualizar la contraseña si se proporciona
        if (strlen($request->input('password')) > 0) {
            $user->password = bcrypt($request->input('password'));
        }

        // Guardar el usuario
        $user->save();

        return [
            'success' => true,
            'message' => $id ? 'Usuario actualizado con nuevo token' : 'Usuario creado con token',
            // Opcional: devolver el nuevo token en la respuesta si es necesario
            'api_token' => $user->api_token,
        ];
    }

    public function update(UserRequest $request, User $user)
    {
        // Omitir actualización de contraseña si está vacía
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // Omitir el campo password si está vacío
        $input = $request->except(['password', 'password_confirmation']);
        $user->fill($input);

        $user->save();

        return [
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            // Otros datos que quieras devolver
        ];
    }





    public function getPhone()
    {
        $user = User::first();

        $user_resource = new UserResource($user);

        return $user_resource->phone;
    }

}
