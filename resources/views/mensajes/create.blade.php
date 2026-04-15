<h2>Crear mensaje</h2>

<form action="{{ route('mensajes.store') }}" method="POST">
    @csrf

    <input type="text" name="nombre" placeholder="Nombre"><br>
    <input type="email" name="correo" placeholder="Correo"><br>
    <textarea name="mensaje" placeholder="Mensaje"></textarea><br>

    <button type="submit">Guardar</button>
</form>