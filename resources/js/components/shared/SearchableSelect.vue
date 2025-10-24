<template>
    <div class="searchable-select-wrapper" ref="wrapper">
        <input
            v-if="isOpen"
            type="text"
            v-model="searchTerm"
            :placeholder="placeholder"
            class="form-control searchable-input"
            @input="filterItems"
            @keydown="handleKeyboard"
            @blur="closeDropdownDelayed"
            ref="searchInput"
            autocomplete="off">
        <select
            v-else
            :id="`${selectType}-${uniqueId}`"
            v-model="selectedValue"
            @change="handleChange"
            @focus="openDropdown"
            :class="['form-control', selectClass]"
            :disabled="disabled"
            :required="required">
            <option value="">{{ placeholder }}</option>
            <option v-for="item in items"
                    :key="item.id"
                    :value="item.id">
                {{ getDisplayText(item) }}
            </option>
        </select>
    </div>
</template>

<script>
// Z-index global que se incrementa con cada dropdown abierto
let globalZIndex = 999999;

export default {
    name: 'SearchableSelect',
    props: {
        value: {
            type: [String, Number],
            default: ''
        },
        items: {
            type: Array,
            default: () => []
        },
        placeholder: {
            type: String,
            default: 'Seleccionar...'
        },
        displayTemplate: {
            type: Function,
            default: null
        },
        searchFields: {
            type: Array,
            default: () => ['nombre', 'codigo', 'number', 'name']
        },
        selectType: {
            type: String,
            default: 'select'
        },
        selectClass: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        },
        required: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            selectedValue: this.value,
            uniqueId: Math.random().toString(36).substr(2, 9),
            isOpen: false,
            searchTerm: '',
            filteredItems: [],
            hoveredIndex: -1,
            closeTimeout: null,
            dropdownStyle: {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '0',
                zIndex: 999999,
                pointerEvents: 'auto'
            }
        };
    },
    watch: {
        value(newVal) {
            this.selectedValue = newVal;
        },
        items() {
            this.filteredItems = this.items;
        },
        isOpen(newVal) {
            if (newVal) {
                this.$nextTick(() => {
                    this.updateDropdownPosition();
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        }
    },
    mounted() {
        this.filteredItems = this.items;
        // Agregar listener para recalcular posición en scroll
        window.addEventListener('scroll', this.handleScroll, true);

        // Crear el dropdown element que se usará
        this.dropdownElement = null;
    },
    beforeDestroy() {
        window.removeEventListener('scroll', this.handleScroll, true);
        // Limpiar dropdown si existe
        if (this.dropdownElement && this.dropdownElement.parentNode) {
            this.dropdownElement.parentNode.removeChild(this.dropdownElement);
        }
    },
    methods: {
        openDropdown() {
            this.isOpen = true;
            this.searchTerm = '';
            this.filteredItems = this.items;
            this.hoveredIndex = 0;

            // Incrementar z-index global para cada dropdown abierto
            globalZIndex += 10;

            // Crear dropdown DOM
            this.$nextTick(() => {
                this.createDropdownElement();
                this.updateDropdownPosition();
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },

        createDropdownElement() {
            // Si ya existe, removerlo
            if (this.dropdownElement && this.dropdownElement.parentNode) {
                this.dropdownElement.parentNode.removeChild(this.dropdownElement);
            }

            // Crear el dropdown
            this.dropdownElement = document.createElement('div');
            this.dropdownElement.className = 'searchable-dropdown-teleport';
            this.dropdownElement.style.cssText = `
                position: fixed;
                z-index: ${globalZIndex};
                pointer-events: auto;
                background: white;
                border: 1px solid #ced4da;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                border-radius: 0.25rem;
                max-height: 400px;
                overflow-y: auto;
                min-width: 150px;
            `;

            // Prevenir que el blur cierre el dropdown
            this.dropdownElement.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.preventClose();
            });

            // Agregar al contenedor
            const container = document.getElementById('searchable-dropdown-container');
            if (container) {
                container.appendChild(this.dropdownElement);
            }

            // Renderizar items
            this.renderDropdownItems();
        },

        renderDropdownItems() {
            if (!this.dropdownElement) return;

            const itemsContainer = document.createElement('div');
            itemsContainer.className = 'searchable-items';

            if (this.filteredItems.length === 0) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'text-muted p-2';
                emptyDiv.textContent = 'No se encontraron resultados';
                itemsContainer.appendChild(emptyDiv);
            } else {
                this.filteredItems.forEach((item, index) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'searchable-item';
                    if (index === this.hoveredIndex) {
                        itemDiv.classList.add('active');
                    }

                    const mainDiv = document.createElement('div');
                    mainDiv.className = 'item-main';
                    mainDiv.textContent = this.getDisplayText(item);
                    itemDiv.appendChild(mainDiv);

                    const searchText = this.getSearchText(item);
                    if (searchText) {
                        const metaDiv = document.createElement('div');
                        metaDiv.className = 'item-meta text-muted small';
                        metaDiv.textContent = searchText;
                        itemDiv.appendChild(metaDiv);
                    }

                    itemDiv.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        this.selectItem(item);
                    });

                    itemDiv.addEventListener('mouseenter', () => {
                        this.hoveredIndex = index;
                        this.renderDropdownItems();
                    });

                    itemsContainer.appendChild(itemDiv);
                });
            }

            this.dropdownElement.innerHTML = '';
            this.dropdownElement.appendChild(itemsContainer);
        },

        updateDropdownPosition() {
            if (!this.$refs.wrapper || !this.dropdownElement) return;

            const rect = this.$refs.wrapper.getBoundingClientRect();
            const inputHeight = this.$refs.wrapper.querySelector('.form-control')?.offsetHeight || 38;

            this.dropdownElement.style.top = (rect.top + inputHeight) + 'px';
            this.dropdownElement.style.left = rect.left + 'px';
            this.dropdownElement.style.width = rect.width + 'px';
        },

        closeDropdown() {
            this.isOpen = false;
            this.searchTerm = '';
            this.hoveredIndex = -1;

            // Remover dropdown del DOM
            if (this.dropdownElement && this.dropdownElement.parentNode) {
                this.dropdownElement.parentNode.removeChild(this.dropdownElement);
                this.dropdownElement = null;
            }
        },

        closeDropdownDelayed() {
            // Usar timeout para permitir que el evento mousedown en el item se complete
            this.closeTimeout = setTimeout(() => {
                this.closeDropdown();
            }, 100);
        },

        preventClose() {
            // Prevenir que el blur cierre el dropdown cuando se hace click en un item
            if (this.closeTimeout) {
                clearTimeout(this.closeTimeout);
                this.closeTimeout = null;
            }
        },

        filterItems(event) {
            const searchTerm = this.searchTerm.toLowerCase();

            if (!searchTerm) {
                this.filteredItems = this.items;
                this.hoveredIndex = 0;
            } else {
                this.filteredItems = this.items.filter(item => {
                    const searchText = this.getSearchText(item).toLowerCase();
                    return searchText.includes(searchTerm);
                });
                this.hoveredIndex = 0;
            }

            // Re-renderizar dropdown
            this.renderDropdownItems();
        },

        selectItem(item) {
            this.selectedValue = item.id;
            this.isOpen = false;
            this.searchTerm = '';
            this.handleChange(item.id);
        },

        handleKeyboard(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.hoveredIndex = Math.min(this.hoveredIndex + 1, this.filteredItems.length - 1);
                this.renderDropdownItems(); // Re-renderizar para mostrar hover
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.hoveredIndex = Math.max(this.hoveredIndex - 1, 0);
                this.renderDropdownItems(); // Re-renderizar para mostrar hover
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (this.hoveredIndex >= 0 && this.filteredItems[this.hoveredIndex]) {
                    this.selectItem(this.filteredItems[this.hoveredIndex]);
                }
            } else if (event.key === 'Escape') {
                this.closeDropdown();
            }
        },

        /**
         * Obtiene el texto de búsqueda desde múltiples campos
         */
        getSearchText(item) {
            if (typeof item === 'string') return item;

            const searchValues = this.searchFields
                .map(field => {
                    const value = item[field];
                    return value ? String(value).toLowerCase() : '';
                })
                .filter(v => v);

            return searchValues.join(' | ');
        },

        /**
         * Obtiene el texto a mostrar en la opción
         */
        getDisplayText(item) {
            if (this.displayTemplate) {
                return this.displayTemplate(item);
            }

            // Formato por defecto según tipo de elemento
            if (item.codigo && item.nombre) {
                // Cuenta contable
                return `${item.codigo} - ${item.nombre}`;
            } else if (item.number && item.name) {
                // Tercero (persona)
                return `${item.number} - ${item.name}`;
            } else if (item.nombre) {
                return item.nombre;
            } else if (item.name) {
                return item.name;
            }

            return item.text || item.id;
        },

        handleChange(value) {
            this.selectedValue = value;
            this.$emit('input', value);
            this.$emit('change', value);
        },

        handleScroll() {
            if (this.isOpen) {
                this.updateDropdownPosition();
            }
        }
    }
};
</script>

<style scoped>
.searchable-select-wrapper {
    position: relative;
    width: 100%;
    z-index: 100;
}

.searchable-input {
    width: 100%;
    z-index: 10020;
    position: relative;
}

.searchable-dropdown-teleport {
    position: fixed;
    background: white;
    border: 1px solid #ced4da;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    border-radius: 0.25rem;
    z-index: 999999;
    pointer-events: auto !important;
}

.searchable-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ced4da;
    border-top: none;
    max-height: 400px;
    overflow-y: auto;
    z-index: 10021;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    margin-top: -1px;
}

.searchable-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.searchable-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    user-select: none;
}

.searchable-item:hover,
.searchable-item.active {
    background-color: #007bff;
    color: white;
}

.searchable-item.active .item-main {
    color: white;
    font-weight: 600;
}

.searchable-item.active .item-meta {
    color: rgba(255, 255, 255, 0.8);
}

.searchable-item:hover .item-main {
    color: white;
}

.searchable-item:hover .item-meta {
    color: rgba(255, 255, 255, 0.9);
}

.item-main {
    font-weight: 500;
    color: #333;
}

.item-meta {
    font-size: 0.85em;
    margin-top: 2px;
    color: #6c757d;
}

.form-control {
    width: 100%;
}
</style>
