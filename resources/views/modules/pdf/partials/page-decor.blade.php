@if (! empty($encabezadoImage))
    <img src="{{ $encabezadoImage }}" alt="Encabezado" class="decor-header">
@endif

@if (! empty($sidebarImage))
    <img src="{{ $sidebarImage }}" alt="Sidebar" class="decor-sidebar">
@endif

@if (! empty($piePaginaImage))
    <img src="{{ $piePaginaImage }}" alt="Pie de pagina" class="decor-footer">
@endif
