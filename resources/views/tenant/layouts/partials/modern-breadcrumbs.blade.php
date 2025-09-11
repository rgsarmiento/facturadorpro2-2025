{{-- Breadcrumbs modernos para el tema corporativo --}}
<div class="modern-breadcrumbs">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-auto">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb modern-breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('tenant.dashboard.index') }}" class="breadcrumb-link">
                                <i class="fas fa-home me-1"></i>
                                Inicio
                            </a>
                        </li>
                        @if(isset($breadcrumbs) && is_array($breadcrumbs))
                            @foreach($breadcrumbs as $breadcrumb)
                                @if($loop->last)
                                    <li class="breadcrumb-item active" aria-current="page">
                                        {{ $breadcrumb['title'] }}
                                    </li>
                                @else
                                    <li class="breadcrumb-item">
                                        @if(isset($breadcrumb['url']))
                                            <a href="{{ $breadcrumb['url'] }}" class="breadcrumb-link">
                                                {{ $breadcrumb['title'] }}
                                            </a>
                                        @else
                                            {{ $breadcrumb['title'] }}
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        @endif
                    </ol>
                </nav>
            </div>
            @if(isset($page_actions) && !empty($page_actions))
            <div class="col-auto ms-auto">
                <div class="page-actions">
                    @foreach($page_actions as $action)
                        <a href="{{ $action['url'] }}"
                           class="btn {{ $action['class'] ?? 'btn-primary' }} btn-sm modern-btn">
                            @if(isset($action['icon']))
                                <i class="{{ $action['icon'] }} me-1"></i>
                            @endif
                            {{ $action['title'] }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Estilos para breadcrumbs modernos */
.modern-breadcrumbs {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--gray-200, #e2e8f0);
    padding: 16px 0;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
}

.modern-breadcrumb {
    background: none;
    padding: 0;
    margin: 0;
}

.modern-breadcrumb .breadcrumb-item {
    font-size: 14px;
    font-weight: 500;
}

.modern-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    content: "";
    color: var(--gray-400, #94a3b8);
    font-weight: 600;
    float: none;
    padding-left: 8px;
    padding-right: 8px;
}

.breadcrumb-link {
    color: var(--gray-600, #475569);
    text-decoration: none;
    transition: color 0.15s ease-in-out;
    display: flex;
    align-items: center;
}

.breadcrumb-link:hover {
    color: var(--primary-color, #2563eb);
    text-decoration: none;
}

.modern-breadcrumb .breadcrumb-item.active {
    color: var(--gray-800, #1e293b);
    font-weight: 600;
}

.page-actions .modern-btn {
    margin-left: 8px;
    border-radius: 8px;
    font-weight: 600;
    padding: 8px 16px;
    font-size: 13px;
    transition: all 0.15s ease-in-out;
}

.page-actions .modern-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

@media (max-width: 768px) {
    .modern-breadcrumbs {
        padding: 12px 0;
        margin-bottom: 16px;
    }

    .page-actions {
        margin-top: 12px;
    }

    .page-actions .modern-btn {
        margin-left: 0;
        margin-right: 8px;
        margin-bottom: 8px;
    }
}
</style>
