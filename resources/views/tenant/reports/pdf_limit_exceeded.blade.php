@extends('tenant.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        Información sobre Exportación de Reportes
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Reporte Demasiado Grande para PDF</h5>
                        <p>El reporte que intentas generar contiene <strong>{{ $records_count ?? 'muchos' }}</strong> registros.</p>
                        <p>Por motivos de rendimiento y estabilidad del servidor, los reportes PDF están limitados a <strong>{{ $max_allowed ?? 2000 }}</strong> registros.</p>
                    </div>

                    <h5 class="mt-4">Opciones Disponibles:</h5>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card border-success mb-3">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-file-excel"></i> Opción 1: Exportar a Excel (Recomendado)
                                </div>
                                <div class="card-body">
                                    <p>Excel puede manejar grandes cantidades de datos sin problemas de memoria o rendimiento.</p>
                                    <ul>
                                        <li>✓ Sin límite de registros</li>
                                        <li>✓ Más rápido de generar</li>
                                        <li>✓ Fácil de filtrar y analizar</li>
                                        <li>✓ Menor consumo de recursos del servidor</li>
                                    </ul>
                                    <button class="btn btn-success btn-block" onclick="exportToExcel()">
                                        <i class="fas fa-download"></i> Exportar a Excel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-filter"></i> Opción 2: Filtrar Fechas
                                </div>
                                <div class="card-body">
                                    <p>Reduce la cantidad de registros filtrando por un rango de fechas más específico.</p>
                                    <ul>
                                        <li>Selecciona un rango de fechas más corto</li>
                                        <li>Filtra por mes específico</li>
                                        <li>Divide el reporte en períodos más pequeños</li>
                                    </ul>
                                    <button class="btn btn-primary btn-block" onclick="goBack()">
                                        <i class="fas fa-arrow-left"></i> Volver a Filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-info mt-3">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-lightbulb"></i> Consejos para Reportes Grandes
                        </div>
                        <div class="card-body">
                            <ol>
                                <li><strong>Divide y conquista:</strong> En lugar de un reporte anual, genera reportes mensuales</li>
                                <li><strong>Usa filtros adicionales:</strong> Filtra por establecimiento, tipo de documento, cliente, etc.</li>
                                <li><strong>Exporta a Excel:</strong> Para análisis detallados, Excel es más versátil</li>
                                <li><strong>Programa reportes:</strong> Configura reportes automáticos por correo electrónico</li>
                            </ol>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-question-circle"></i> ¿Por qué existe este límite?</h6>
                        <p class="mb-0">
                            Los archivos PDF son más complejos de generar que Excel. Cada página requiere renderizado de HTML,
                            aplicación de estilos CSS, y conversión a formato PDF. Con miles de registros, este proceso puede:
                        </p>
                        <ul class="mb-0">
                            <li>Consumir mucha memoria del servidor (varios GB)</li>
                            <li>Tardar varios minutos en completarse</li>
                            <li>Causar timeouts del navegador</li>
                            <li>Afectar el rendimiento para otros usuarios</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    // Redirigir a la exportación Excel con los mismos filtros
    window.location.href = "{{ $excel_url ?? '#' }}";
}

function goBack() {
    window.history.back();
}
</script>
@endsection
