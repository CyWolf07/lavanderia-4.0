<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'puedeElegirRol' => User::count() === 0,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'cedula' => ['nullable', 'string', 'max:50', 'unique:users,cedula'],
            'contacto' => ['nullable', 'string', 'max:50'],
            'rol' => ['nullable', 'in:admin,programador,usuario,recolector'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $rol = User::count() === 0
            ? ($request->input('rol', 'admin'))
            : 'usuario';

        $rolRegistro = Rol::firstOrCreate(
            ['nombre' => ucfirst($rol)],
            ['descripcion' => 'Rol '.$rol.' del sistema']
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'cedula' => $request->cedula,
            'contacto' => $request->contacto,
            'rol' => $rol,
            'rol_id' => $rolRegistro->id,
            'activo' => true,
            'puede_editar_precios' => false,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
