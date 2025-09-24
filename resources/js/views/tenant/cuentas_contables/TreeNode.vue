<template>
    <div class="tree-node">
        <div class="node-content" :style="{ paddingLeft: (level * 20) + 'px' }">
            <div class="d-flex align-items-center justify-content-between py-2 px-3 border rounded mb-1"
                 :class="getNodeClass()">
                <div class="node-info flex-grow-1">
                    <div class="d-flex align-items-center">
                        <button v-if="hasChildren"
                                class="btn btn-sm btn-outline-secondary mr-2"
                                @click="toggleExpanded">
                            <i :class="expanded ? 'fa fa-minus' : 'fa fa-plus'"></i>
                        </button>
                        <div class="node-details">
                            <strong>{{ node.codigo }}</strong> - {{ node.nombre }}
                            <div class="small text-muted">
                                <span class="badge mr-1" :class="getBadgeClass(node.tipo_cuenta)">
                                    {{ node.tipo_cuenta | capitalize }}
                                </span>
                                <span class="badge mr-1" :class="node.naturaleza === 'debito' ? 'badge-primary' : 'badge-info'">
                                    {{ node.naturaleza | capitalize }}
                                </span>
                                <span class="badge mr-1" :class="node.activa ? 'badge-success' : 'badge-secondary'">
                                    {{ node.activa ? 'Activa' : 'Inactiva' }}
                                </span>
                                <span v-if="node.es_cuenta_movimiento" class="badge badge-warning">
                                    Cuenta de Movimiento
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="node-actions">
                    <div class="text-right">
                        <div class="font-weight-bold">{{ formatCurrency(node.saldo_actual) }}</div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info" @click="editNode" title="Editar">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-success" @click="addChild" title="Agregar Subcuenta">
                                <i class="fa fa-plus"></i>
                            </button>
                            <button class="btn btn-outline-danger" @click="deleteNode" title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cuentas hijas -->
        <div v-if="expanded && hasChildren" class="children">
            <tree-node
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :level="level + 1"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
                @add-child="$emit('add-child', $event)"
            ></tree-node>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TreeNode',
    props: {
        node: {
            type: Object,
            required: true
        },
        level: {
            type: Number,
            default: 0
        }
    },
    data() {
        return {
            expanded: this.level < 2 // Expandir automáticamente los primeros 2 niveles
        }
    },
    computed: {
        hasChildren() {
            return this.node.children && this.node.children.length > 0
        }
    },
    methods: {
        toggleExpanded() {
            this.expanded = !this.expanded
        },
        editNode() {
            this.$emit('edit', this.node.id)
        },
        deleteNode() {
            this.$emit('delete', this.node.id)
        },
        addChild() {
            // Redirigir al formulario de creación con el ID de la cuenta padre
            window.location.href = `/contabilidad/cuentas-contables/create?padre_id=${this.node.id}`
        },
        getNodeClass() {
            let classes = []

            if (!this.node.activa) {
                classes.push('bg-light text-muted')
            }

            if (this.node.es_cuenta_movimiento) {
                classes.push('border-warning')
            } else {
                classes.push('border-secondary')
            }

            return classes.join(' ')
        },
        getBadgeClass(tipo) {
            const classes = {
                'activo': 'badge-success',
                'pasivo': 'badge-danger',
                'patrimonio': 'badge-primary',
                'ingreso': 'badge-info',
                'gasto': 'badge-warning',
                'costo': 'badge-dark'
            }
            return classes[tipo] || 'badge-secondary'
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP'
            }).format(amount || 0)
        }
    },
    filters: {
        capitalize(value) {
            if (!value) return ''
            return value.charAt(0).toUpperCase() + value.slice(1)
        }
    }
}
</script>

<style scoped>
.tree-node {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.node-content {
    transition: all 0.2s ease;
}

.node-content:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.node-info {
    min-width: 0; /* Para permitir que el texto se trunque si es necesario */
}

.node-details {
    flex-grow: 1;
}

.children {
    margin-left: 10px;
    border-left: 2px solid #dee2e6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75em;
}

@media (max-width: 768px) {
    .node-content {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .node-actions {
        margin-top: 0.5rem;
        width: 100%;
    }
}
</style>
