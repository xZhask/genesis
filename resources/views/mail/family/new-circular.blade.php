@extends('mail.layout')

@section('content')
    <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#3FA64A;text-transform:uppercase;letter-spacing:.04em;">Circular · {{ $circular->published_on->longDate() }}</p>
    <h1 style="margin:0 0 12px;font-size:22px;color:#104976;">{{ $circular->title }}</h1>

    @if ($circular->summary)
        <p style="margin:0 0 20px;">{{ $circular->summary }}</p>
    @endif

    @include('mail.family._button', ['url' => $url, 'label' => 'Leer la circular'])

    @if ($circular->isForFamiliesOnly())
        <p style="margin:0;font-size:14px;color:#56708A;">Esta circular es solo para las familias: se lee al ingresar al portal.</p>
    @endif
@endsection

@section('footer')
    @include('mail.family._footer')
@endsection
