<h2>Editar mensaje</h2>

<form action="{{ route('mensajes.update', $mensaje->id) }}" method="POST">
    @csrf
    @method('PUT')

    <input type="text" name="nombre" value="{{ $mensaje->nombre }}"><br>
    <input type="email" name="correo" value="{{ $mensaje->correo }}"><br>
    <textarea name="mensaje">{{ $mensaje->mensaje }}</textarea><br>

    <button type="submit">Actualizar</button>
</form>