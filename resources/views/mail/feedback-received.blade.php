@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#104976;">Nuevo mensaje en el buzón</h1>
    <p style="margin:0 0 20px;">Llegó un mensaje de tipo <strong>{{ mb_strtolower($type->label()) }}</strong> desde el portal. Por privacidad, el contenido solo se ve en el panel, con tu cuenta.</p>

    @include('mail.family._button', ['url' => route('admin.feedback.index'), 'label' => 'Abrir el buzón', 'color' => '#1D5FA8', 'text' => '#FFFFFF'])

    <p style="margin:0;font-size:13px;color:#56708A;">Recibes este aviso porque tu cuenta está autorizada para leer el buzón de sugerencias.</p>
@endsection
