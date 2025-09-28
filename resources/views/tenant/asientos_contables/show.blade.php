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
    .estado-badge {
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
    .estado-anulado {
        background-color: #dc3545;
        color: #fff;
    }
    .info-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .balance-summary {
        background-color: #e8f5e8;
        border: 1px solid #c3e6cb;
        border-radius: 5px;
        padding: 15px;
        text-align: center;
    }
    .detalle-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    .adjunto-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 5px;
        background-color: #fff;
    }
    .adjunto-item:hover {
        background-color: #f8f9fa;
    }
    .timeline-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .timeline-item:last-child {
        border-bottom: none;
    }
    .timeline-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        font-size: 14px;
    }
    .timeline-creacion {
        background-color: #007bff;
        color: white;
    }
    .timeline-confirmacion {
        background-color: #28a745;
        color: white;
    }
    .timeline-anulacion {
        background-color: #dc3545;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        Asiento Contable {{ $asiento->numero_comprobante }}
                        <i class="fas fa-tools construction-icon" title="Módulo en construcción"></i>
                    </h3>
                    <div class="card-tools">
                        <div class="btn-group" role="group">
                            <a href="{{ route('tenant.asientos_contables.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            @if($asiento->puedeEditarse())
                                <a href="{{ route('tenant.asientos_contables.edit', $asiento->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            @endif
                            @if($asiento->esBorrador())
                                <button id="btn-confirmar" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Confirmar
                                </button>
                            @endif
                            @if($asiento->puedeAnularse())
                                <button id="btn-anular" class="btn btn-sm btn-danger">
                                    <i class="fas fa-ban"></i> Anular
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Información general -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5>Información General</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Número:</strong></td>
                                    <td>{{ $asiento->numero_comprobante }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo:</strong></td>
                                    <td>{{ $asiento->tipoComprobante->codigo }} - {{ $asiento->tipoComprobante->nombre }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td>{{ $asiento->fecha_asiento->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="estado-badge estado-{{ strtolower($asiento->estado) }}">
                                            {{ $asiento->estado }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Concepto:</strong></td>
                                    <td>{{ $asiento->concepto }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="balance-summary">
                            <h5>Resumen Contable</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Total Débitos</h6>
                                    <h4 class="text-primary">{{ number_format($asiento->total_debito, 0, ',', '.') }}</h4>
                                </div>
                                <div class="col-md-6">
                                    <h6>Total Créditos</h6>
                                    <h4 class="text-success">{{ number_format($asiento->total_credito, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-center align-items-center">
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                <span class="text-success"><strong>Asiento Balanceado</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles del asiento -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Detalles del Asiento</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered detalle-table">
                                <thead>
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
                                            <strong>{{ $detalle->cuentaContable->codigo }}</strong><br>
                                            <small>{{ $detalle->cuentaContable->nombre }}</small>
                                        </td>
                                        <td>
                                            @if($detalle->tercero)
                                                <strong>{{ $detalle->tercero->number }}</strong><br>
                                                <small>{{ $detalle->tercero->name }}</small>
                                            @else
                                                <span class="text-muted">No aplica</span>
                                            @endif
                                        </td>
                                        <td>{{ $detalle->concepto }}</td>
                                        <td class="text-right">
                                            @if($detalle->debito > 0)
                                                <strong class="text-primary">
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

                <!-- Archivos adjuntos -->
                @if($asiento->adjuntos->count() > 0)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Documentos Adjuntos</h5>
                        <div class="row">
                            @foreach($asiento->adjuntos as $adjunto)
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="adjunto-item">
                                    <div>
                                        <i class="fas fa-file{{ $adjunto->esPDF() ? '-pdf text-danger' : ($adjunto->esImagen() ? '-image text-success' : ' text-secondary') }}"></i>
                                        <strong>{{ $adjunto->nombre_archivo }}</strong><br>
                                        <small class="text-muted">
                                            {{ $adjunto->getTamañoHumano() }} -
                                            {{ $adjunto->fecha_carga->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                    <div class="ml-2">
                                        <a href="{{ Storage::url($adjunto->ruta_archivo) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Histórico de estados -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Histórico de Estados</h5>
                        <div class="timeline">
                            <!-- Creación -->
                            <div class="timeline-item">
                                <div class="d-flex align-items-center">
                                    <div class="timeline-icon timeline-creacion">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div>
                                        <strong>Asiento Creado</strong><br>
                                        <small class="text-muted">
                                            {{ $asiento->fecha_creacion->format('d/m/Y H:i:s') }} por {{ $asiento->usuarioCreacion->name }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirmación -->
                            @if($asiento->fecha_confirmacion)
                            <div class="timeline-item">
                                <div class="d-flex align-items-center">
                                    <div class="timeline-icon timeline-confirmacion">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Asiento Confirmado</strong><br>
                                        <small class="text-muted">
                                            {{ $asiento->fecha_confirmacion->format('d/m/Y H:i:s') }} por {{ $asiento->usuarioConfirmacion->name }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Anulación -->
                            @if($asiento->fecha_anulacion)
                            <div class="timeline-item">
                                <div class="d-flex align-items-center">
                                    <div class="timeline-icon timeline-anulacion">
                                        <i class="fas fa-ban"></i>
                                    </div>
                                    <div>
                                        <strong>Asiento Anulado</strong><br>
                                        <small class="text-muted">
                                            {{ $asiento->fecha_anulacion->format('d/m/Y H:i:s') }} por {{ $asiento->usuarioAnulacion->name }}
                                        </small>
                                        @if($asiento->motivo_anulacion)
                                        <br><small class="text-danger">
                                            <strong>Motivo:</strong> {{ $asiento->motivo_anulacion }}
                                        </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
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
        const btnConfirmar = document.getElementById('btn-confirmar');
        const btnAnular = document.getElementById('btn-anular');

        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', async function() {
                const result = await Swal.fire({
                    title: '¿Confirmar asiento?',
                    text: 'Una vez confirmado, el asiento no podrá modificarse',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await axios.post('/contabilidad/asientos-contables/{{ $asiento->id }}/confirmar');

                        if (response.data.success) {
                            Swal.fire({
                                title: 'Éxito',
                                text: response.data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('Error', 'Error al confirmar el asiento', 'error');
                    }
                }
            });
        }

        if (btnAnular) {
            btnAnular.addEventListener('click', async function() {
                const { value: motivo } = await Swal.fire({
                    title: 'Anular asiento',
                    input: 'textarea',
                    inputLabel: 'Motivo de anulación',
                    inputPlaceholder: 'Ingrese el motivo de la anulación...',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Debe ingresar un motivo de anulación';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Anular',
                    cancelButtonText: 'Cancelar'
                });

                if (motivo) {
                    try {
                        const response = await axios.post('/contabilidad/asientos-contables/{{ $asiento->id }}/anular', {
                            motivo: motivo
                        });

                        if (response.data.success) {
                            Swal.fire({
                                title: 'Éxito',
                                text: response.data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('Error', 'Error al anular el asiento', 'error');
                    }
                }
            });
        }
    });
</script>
@endpush
