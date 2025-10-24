@extends('tenant.layouts.app')

@push('styles')
<style>
    .content-header-left h1 {
        display: flex;
        align-items: center;
    }
    .construction-icon {
        margin-left: 10px;
        color: #ff9800;
        font-size: 20px;
    }
    .es                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Detalles del Asiento</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-sm">ge {
        font-size: 0.9em;
        padding: 5px 12px;
        border-radius: 15px;
        font-weight: bold;
    }
    .estado-borrador {
        background-color: #ffc107;
        color: #000;
    }
    .estado-confirmado {
        background-color: #28a745;
        color: #fff;
    }
    .estado-aprobado {
        background-color: #17a2b8;
        color: #fff;
    }
    .estado-anulado {
        background-color: #dc3545;
        color: #fff;
    }

    /* Header actions - mejorado */
    .card-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
    }

    .card-tools {
        margin-left: auto !important;
        display: flex !important;
        gap: 8px !important;
        align-items: center !important;
    }

    .header-actions {
        display: flex !important;
        gap: 8px !important;
        align-items: center !important;
        flex-wrap: wrap !important;
    }

    .header-actions .btn {
        white-space: nowrap !important;
        margin: 0 !important;
    }

    /* Estilos para botones disabled */
    .btn.disabled, .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: auto; /* Permitir eventos para mostrar tooltip */
    }

    .btn.disabled:hover, .btn:disabled:hover {
        opacity: 0.5;
        transform: none;
    }

    /* Table styling - más compacto */
    .table td, .table th {
        padding: 0.35rem 0.5rem;
        font-size: 0.9em;
        line-height: 1.2;
    }

    .table-sm td, .table-sm th {
        padding: 0.25rem 0.5rem;
        font-size: 0.85em;
    }

    /* Hacer la vista más compacta */
    .card {
        margin-bottom: 0.75rem;
    }

    .card-body {
        padding: 0.75rem;
    }

    .row {
        margin-bottom: 0.35rem;
    }

    .table-borderless td, .table-borderless th {
        padding: 0.25rem 0.5rem;
        border: none;
    }

    .card-header {
        padding: 0.5rem 0.75rem;
    }

    .card-title {
        margin-bottom: 0;
        font-size: 1rem;
    }

    /* Adjuntos section */
    .adjuntos-container {
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .adjunto-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        background-color: #fff;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .adjunto-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .adjunto-info {
        display: flex;
        align-items: center;
        flex-grow: 1;
    }

    .adjunto-icon {
        font-size: 2.5em;
        margin-right: 15px;
        width: 50px;
        text-align: center;
    }

    .adjunto-details h6 {
        margin: 0;
        font-weight: 600;
    }

    .adjunto-meta {
        font-size: 0.875em;
        margin-top: 5px;
    }

    .adjunto-actions {
        display: flex;
        gap: 8px;
    }

    .btn-adjunto {
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .btn-adjunto:hover {
        transform: translateY(-1px);
    }

    .empty-adjuntos {
        text-align: center;
        padding: 40px;
    }

    .empty-adjuntos i {
        font-size: 3em;
        margin-bottom: 15px;
        opacity: 0.5;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Detalle del Asiento Contable #{{ $asiento->numero_comprobante }}
                    <span class="estado-badge estado-{{ strtolower($asiento->estado) }}">
                        {{ ucfirst($asiento->estado) }}
                    </span>
                    @if($asiento->trashed())
                        <span class="estado-badge estado-anulado" title="Eliminado">
                            Eliminado
                        </span>
                    @endif
                    <i class="fas fa-tools construction-icon" title="Módulo en construcción"></i>
                </h3>
                <div class="card-tools header-actions">
                    <a href="{{ route('tenant.asientos_contables.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>

                    <a href="{{ route('tenant.asientos_contables.imprimir', $asiento->id) }}" target="_blank" class="btn btn-sm btn-info" title="Imprimir">
                        <i class="fas fa-print"></i> Imprimir
                    </a>

                    <!-- Botones siempre visibles, disabled si no es BORRADOR -->
                    <!-- Debug: Estado actual del asiento: {{ $asiento->estado }} -->
                    @php $noEditar = strtolower($asiento->estado) !== 'borrador' || $asiento->trashed(); @endphp
                    <a href="{{ route('tenant.asientos_contables.edit', $asiento->id) }}"
                       class="btn btn-sm btn-primary {{ $noEditar ? 'disabled' : '' }}"
                       {{ $noEditar ? 'aria-disabled=true tabindex=-1' : '' }}
                       title="{{ strtolower($asiento->estado) !== 'borrador' ? 'Solo se puede editar en estado BORRADOR' : 'Editar asiento contable' }}">
                        <i class="fas fa-edit"></i> Editar
                    </a>

                    <button type="button"
                            id="btn-aprobar"
                            class="btn btn-sm btn-success"
                            {{ (strtolower($asiento->estado) !== 'borrador' || $asiento->trashed()) ? 'disabled' : '' }}
                            title="{{ strtolower($asiento->estado) !== 'borrador' ? 'Solo se puede aprobar en estado BORRADOR' : 'Aprobar asiento contable' }}">
                        <i class="fas fa-check"></i> Aprobar
                    </button>

                    <button type="button"
                            id="btn-eliminar"
                            class="btn btn-sm btn-danger"
                            {{ (strtolower($asiento->estado) !== 'borrador' || $asiento->trashed()) ? 'disabled' : '' }}
                            title="{{ strtolower($asiento->estado) !== 'borrador' ? 'Solo se puede eliminar en estado BORRADOR' : 'Eliminar asiento contable' }}">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>

                    <!-- Sin acciones adicionales tras aprobar: no se muestra botón Anular -->
                </div>
            </div>
            <div class="card-body">
                <!-- Información del asiento -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Información General</h6>
                            </div>
                            <div class="card-body py-2">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th width="40%">Tipo de Comprobante:</th>
                                        <td>{{ $asiento->tipoComprobante ? $asiento->tipoComprobante->nombre : 'No definido' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Número:</th>
                                        <td>{{ $asiento->numero_comprobante }}</td>
                                    </tr>
                                    <tr>
                                        <th>Fecha:</th>
                                        <td>{{ $asiento->fecha_asiento ? $asiento->fecha_asiento->format('d/m/Y') : 'No definida' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Concepto:</th>
                                        <td>{{ $asiento->concepto ?? 'Sin concepto' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Estado:</th>
                                        <td>
                                            <span class="estado-badge estado-{{ strtolower($asiento->estado) }}">
                                                {{ ucfirst($asiento->estado) }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Resumen del Asiento</h6>
                            </div>
                            <div class="card-body py-2 text-center">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Total Débito</h6>
                                        <h4 class="text-danger">
                                            ${{ number_format($asiento->total_debito, 0, ',', '.') }}
                                        </h4>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Total Crédito</h6>
                                        <h4 class="text-success">
                                            ${{ number_format($asiento->total_credito, 0, ',', '.') }}
                                        </h4>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6>Diferencia</h6>
                                        @php $diferencia = $asiento->total_debito - $asiento->total_credito; @endphp
                                        <h4 class="{{ $diferencia == 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format(abs($diferencia), 0, ',', '.') }}
                                            @if($diferencia == 0)
                                                <i class="fas fa-check-circle"></i>
                                            @else
                                                <i class="fas fa-exclamation-triangle"></i>
                                            @endif
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles del asiento -->
                <div class="row mb-2">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Detalles del Asiento</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Cuenta Contable</th>
                                                <th>Tercero</th>
                                                <th>Concepto</th>
                                                <th class="text-right">Débito</th>
                                                <th class="text-right">Crédito</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($asiento->detalles as $detalle)
                                            <tr>
                                                <td>
                                                    @if($detalle->cuentaContable)
                                                        <strong>{{ $detalle->cuentaContable->codigo }}</strong><br>
                                                        <small>{{ $detalle->cuentaContable->descripcion }}</small>
                                                    @else
                                                        <span class="text-muted">Cuenta no definida</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($detalle->tercero)
                                                        <strong>{{ $detalle->tercero->number }}</strong><br>
                                                        <small>{{ $detalle->tercero->name }}</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $detalle->concepto ?? 'Sin concepto' }}</td>
                                                <td class="text-right">
                                                    @if($detalle->debito > 0)
                                                        <strong class="text-danger">
                                                            ${{ number_format($detalle->debito, 0, ',', '.') }}
                                                        </strong>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    @if($detalle->credito > 0)
                                                        <strong class="text-success">
                                                            ${{ number_format($detalle->credito, 0, ',', '.') }}
                                                        </strong>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-dark">
                                            <tr>
                                                <th colspan="3" class="text-right">TOTALES:</th>
                                                <th class="text-right">
                                                    ${{ number_format($asiento->total_debito, 0, ',', '.') }}
                                                </th>
                                                <th class="text-right">
                                                    ${{ number_format($asiento->total_credito, 0, ',', '.') }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archivos adjuntos -->
                <div class="row mb-2">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-paperclip mr-2"></i>
                                    Documentos Adjuntos
                                    <span class="badge badge-secondary ml-2">{{ $asiento->adjuntos->count() }}</span>
                                </h6>
                            </div>
                            <div class="card-body py-2 adjuntos-container">
                                @if($asiento->adjuntos->count() > 0)
                                    <div class="adjuntos-grid">
                                        @foreach($asiento->adjuntos as $adjunto)
                                        <div class="adjunto-item border" data-adjunto-id="{{ $adjunto->id }}">
                                            <div class="adjunto-info">
                                                <div class="adjunto-icon">
                                                    @if($adjunto->esPDF())
                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                    @elseif($adjunto->esImagen())
                                                        <i class="fas fa-file-image text-success"></i>
                                                    @elseif($adjunto->esExcel())
                                                        <i class="fas fa-file-excel text-success"></i>
                                                    @elseif($adjunto->esWord())
                                                        <i class="fas fa-file-word text-primary"></i>
                                                    @else
                                                        <i class="fas fa-file text-secondary"></i>
                                                    @endif
                                                </div>
                                                <div class="adjunto-details">
                                                    <h6>{{ $adjunto->nombre_archivo }}</h6>
                                                    <div class="adjunto-meta text-muted">
                                                        <i class="fas fa-weight-hanging mr-1"></i> {{ $adjunto->getTamañoHumano() }}
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-calendar mr-1"></i> {{ $adjunto->fecha_carga ? $adjunto->fecha_carga->format('d/m/Y H:i') : 'Fecha no disponible' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="adjunto-actions">
                                                @if($adjunto->esImagen())
                                                    <button type="button" class="btn btn-outline-info btn-adjunto"
                                                            onclick="previewImage('{{ route('tenant.asientos_contables.adjuntos.descargar', $adjunto->id) }}', '{{ $adjunto->nombre_archivo }}')"
                                                            title="Vista previa">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('tenant.asientos_contables.adjuntos.descargar', $adjunto->id) }}"
                                                   class="btn btn-outline-primary btn-adjunto"
                                                   title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                @if($asiento->puedeEditarse())
                                                    <button type="button" class="btn btn-outline-danger btn-adjunto"
                                                            onclick="eliminarAdjunto({{ $adjunto->id }})"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-adjuntos text-muted">
                                        <i class="fas fa-folder-open"></i>
                                        <h6>No hay documentos adjuntos</h6>
                                        <p>Los documentos que se adjunten a este asiento aparecerán aquí.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Utilidades: usar SweetAlert2 si está disponible, si no, fallback a confirm/alert
        const hasSwal = typeof window.Swal !== 'undefined';
        const ui = {
            async confirm(options) {
                if (hasSwal) {
                    return await Swal.fire(Object.assign({
                        showCancelButton: true
                    }, options));
                }
                const title = options && options.title ? options.title : '¿Confirmar?';
                const text = options && options.text ? `\n${options.text}` : '';
                const ok = window.confirm(`${title}${text}`);
                return { isConfirmed: ok };
            },
            async promptTextarea(options) {
                if (hasSwal) {
                    return await Swal.fire(Object.assign({
                        input: 'textarea',
                        showCancelButton: true
                    }, options));
                }
                const title = options && options.inputLabel ? options.inputLabel : 'Ingrese texto';
                const value = window.prompt(title, '');
                return { value };
            },
            notifySuccess(title, text) {
                if (hasSwal) {
                    return Swal.fire({ title, text, icon: 'success', timer: 2000, showConfirmButton: false });
                }
                alert(`${title}: ${text}`);
            },
            notifyError(title, text) {
                if (hasSwal) {
                    return Swal.fire(title, text, 'error');
                }
                alert(`${title}: ${text}`);
            }
        };

    console.log('DOM loaded, inicializando botones...');
        const btnAprobar = document.getElementById('btn-aprobar');
    // No hay botón Anular en esta vista
        const btnEliminar = document.getElementById('btn-eliminar');

        // Botones de test
        const btnAprobarTest = document.getElementById('btn-aprobar-test');
        const btnEliminarTest = document.getElementById('btn-eliminar-test');

        console.log('Botones encontrados:', { btnAprobar, btnEliminar, btnAprobarTest, btnEliminarTest });

        // Prevenir navegación en enlace de editar si está disabled
        const btnEditar = document.querySelector('a[href*="edit"]:not(#btn-aprobar-test)');
        if (btnEditar && btnEditar.classList.contains('disabled')) {
            btnEditar.addEventListener('click', function(e) {
                e.preventDefault();
                return false;
            });
        }

        if (btnAprobar) {
            btnAprobar.addEventListener('click', async function() {
                // Verificar si el botón está disabled
                if (this.disabled) {
                    return;
                }

                const result = await ui.confirm({
                    title: '¿Aprobar asiento?',
                    text: 'Una vez aprobado, el asiento no podrá modificarse',
                    icon: 'question',
                    confirmButtonText: 'Sí, aprobar',
                    cancelButtonText: 'Cancelar'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await axios.post('{{ url("/") }}/contabilidad/asientos-contables/{{ $asiento->id }}/confirmar');

                        if (response.data.success) {
                            ui.notifySuccess('Éxito', response.data.message);
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        } else {
                            ui.notifyError('Error', response.data.message || 'No se pudo aprobar');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        ui.notifyError('Error', 'Error al aprobar el asiento');
                    }
                }
            });
        }

        // Sin manejadores de Anular

        if (btnEliminar) {
            btnEliminar.addEventListener('click', async function() {
                // Verificar si el botón está disabled
                if (this.disabled) {
                    return;
                }

                const result = await ui.confirm({
                    title: '¿Eliminar asiento?',
                    text: 'El asiento será marcado como eliminado y seguirá visible en el listado.',
                    icon: 'warning',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await axios.delete('{{ url("/") }}/contabilidad/asientos-contables/{{ $asiento->id }}');

                        if (response.data.success) {
                            ui.notifySuccess('Eliminado', 'El asiento ha sido eliminado exitosamente');
                            setTimeout(() => {
                                window.location.href = '{{ route('tenant.asientos_contables.index') }}';
                            }, 500);
                        } else {
                            ui.notifyError('Error', response.data.message || 'No se pudo eliminar');
                        }
                    } catch (error) {
                        ui.notifyError('Error', 'Error al eliminar el asiento');
                    }
                }
            });
        }

        // Event listeners para botones de test (siempre visibles)
        if (btnAprobarTest) {
            btnAprobarTest.addEventListener('click', function() {
                if (btnAprobar) {
                    btnAprobar.click();
                } else {
                    // Ejecutar directamente si el botón principal no existe
                    console.log('Ejecutando aprobar desde botón test');
                    // Aquí puedes duplicar la lógica de aprobar si es necesario
                }
            });
        }

        if (btnEliminarTest) {
            btnEliminarTest.addEventListener('click', function() {
                if (btnEliminar) {
                    btnEliminar.click();
                } else {
                    // Ejecutar directamente si el botón principal no existe
                    console.log('Ejecutando eliminar desde botón test');
                    // Aquí puedes duplicar la lógica de eliminar si es necesario
                }
            });
        }
    });

    // Función para previsualizar imágenes
    function previewImage(url, filename) {
        if (typeof window.Swal !== 'undefined') {
            Swal.fire({
                title: filename,
                imageUrl: url,
                imageAlt: filename,
                showConfirmButton: false,
                showCloseButton: true,
                width: '80%',
                customClass: { image: 'img-fluid' }
            });
        } else {
            window.open(url, '_blank');
        }
    }

    // Función para eliminar adjuntos
    async function eliminarAdjunto(adjuntoId) {
        const result = await (typeof window.Swal !== 'undefined' ? Swal.fire({
            title: '¿Eliminar archivo?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }) : Promise.resolve({ isConfirmed: window.confirm('¿Eliminar archivo?') }));

        if (result.isConfirmed) {
            try {
                const response = await axios.delete(`/contabilidad/asientos-contables/adjuntos/${adjuntoId}`);

                if (response.data.success) {
                    // Remover el elemento del DOM
                    const adjuntoElement = document.querySelector(`[data-adjunto-id="${adjuntoId}"]`);
                    if (adjuntoElement) {
                        adjuntoElement.remove();
                    }

                    // Actualizar contador
                    const badge = document.querySelector('.badge-secondary');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        badge.textContent = currentCount - 1;
                    }

                    // Mostrar mensaje vacío si no hay más adjuntos
                    const adjuntosGrid = document.querySelector('.adjuntos-grid');
                    if (adjuntosGrid && adjuntosGrid.children.length === 0) {
                        location.reload(); // Recargar para mostrar el mensaje de "no hay adjuntos"
                    }

                    if (typeof window.Swal !== 'undefined') {
                        Swal.fire({ title: 'Eliminado', text: 'El archivo ha sido eliminado exitosamente', icon: 'success', timer: 2000, showConfirmButton: false });
                    } else {
                        alert('El archivo ha sido eliminado exitosamente');
                    }
                } else {
                    if (typeof window.Swal !== 'undefined') {
                        Swal.fire('Error', response.data.message, 'error');
                    } else {
                        alert('Error: ' + (response.data.message || 'No se pudo eliminar'));
                    }
                }
            } catch (error) {
                if (typeof window.Swal !== 'undefined') {
                    Swal.fire('Error', 'Error al eliminar el archivo', 'error');
                } else {
                    alert('Error al eliminar el archivo');
                }
            }
        }
    }
</script>
@endpush
