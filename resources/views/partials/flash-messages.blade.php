@php
    $flashes = [];
    if (session('success')) {
        $flashes[] = ['type' => 'success', 'message' => session('success')];
    }
    if (session('error')) {
        $flashes[] = ['type' => 'danger', 'message' => session('error')];
    }
    if (session('warning')) {
        $flashes[] = ['type' => 'warning', 'message' => session('warning')];
    }
    if (session('info')) {
        $flashes[] = ['type' => 'info', 'message' => session('info')];
    }
@endphp

@if (count($flashes) > 0)
    <div id="saeFlashMessages" data-messages='@json($flashes)' style="display: none;"></div>
@endif
