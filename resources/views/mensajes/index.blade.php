<h2>Lista de mensajes</h2>

<a href="{{ route('mensajes.create') }}" class="btn btn-primary">Nuevo mensaje</a>

@if(session('success'))
    <div>{{ session('success') }}</div>
@endif

<table border="1">
    <tr>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Mensaje</th>
        <th>Acciones</th>
    </tr>

    @foreach($mensajes as $mensaje)
    <tr>
        <td>{{ $mensaje->nombre }}</td>
        <td>{{ $mensaje->correo }}</td>
        <td>{{ $mensaje->mensaje }}</td>
        <td>

            <!-- EDITAR -->
            <a href="{{ route('mensajes.edit', $mensaje->id) }}" class="btn btn-warning">
                Editar
            </a>

            <!-- ELIMINAR -->
            <form action="{{ route('mensajes.destroy', $mensaje->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')

                <button type="submit"
                        onclick="return confirm('¿Seguro que deseas eliminar este mensaje?')">
                    Eliminar
                </button>
            </form>

        </td>
    </tr>
    @endforeach

</table>