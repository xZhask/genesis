@props(['status'])

<span {{ $attributes->class(['badge', 'badge-'.$status->value]) }}>{{ $status->label() }}</span>
