<template>
    <div id="styleSwitcher" class="style-switcher">

        <a id="styleSwitcherOpen" class="style-switcher-open" href="#"><i class="fas fa-cogs"></i></a>

        <form class="style-switcher-wrap" autocomplete="off">

            <h4>Configuraciones visuales</h4>

            <div v-if="visual == null">
                <h5 class="">No posee ajustes actualmente</h5>
                <a href="" class="text-warning" v-if="typeUser != 'integrator'">cargar ajustes por defecto</a>
                <br>
            </div>
            <div v-if="typeUser != 'integrator'">

                <h5>Fondo oscuro</h5>
                <el-switch
                    v-model="visuals.bg"
                    active-text="Dark"
                    inactive-text="Light"
                    active-value="dark"
                    inactive-value="light"
                    active-color="#383f48"
                    inactive-color="#ccc"
                    @change="submit">
                </el-switch>

                <div class="hidden-on-dark pt-3">
                    <h5>Encabezado</h5>
                    <el-switch
                        v-model="visuals.header"
                        active-text="Dark"
                        inactive-text="Light"
                        active-value="dark"
                        inactive-value="light"
                        active-color="#383f48"
                        inactive-color="#ccc"
                        @change="submit">
                    </el-switch>
                </div>

                <div class="hidden-on-dark pt-3">
                    <h5>Paneles</h5>
                    <el-switch
                        v-model="visuals.sidebars"
                        active-text="Dark"
                        inactive-text="Light"
                        active-value="dark"
                        inactive-value="light"
                        active-color="#383f48"
                        inactive-color="#ccc"
                        @change="submit">
                    </el-switch>
                </div>

                <div class="pt-3">
                    <h5>Tipo de navegación</h5>
                    <div :class="{'has-danger': errors.horizontal_menu}">
                        <el-switch
                            v-model="form.horizontal_menu"
                            active-text="Horizontal"
                            inactive-text="Lateral"
                            @change="onNavigationTypeChange">
                        </el-switch>
                        <br>
                        <small class="form-control-feedback" v-if="errors.horizontal_menu" v-text="errors.horizontal_menu[0]"></small>
                        <small class="form-control-feedback">Horizontal: menú en la parte superior | Lateral: menú tradicional a la izquierda</small>
                    </div>
                </div>

                <div class="pt-3">
                    <h5>Menú lateral contraído</h5>
                    <div :class="{'has-danger': errors.compact_sidebar}">
                        <el-switch
                            v-model="form.compact_sidebar"
                            active-text="Si"
                            inactive-text="No"
                            @change="submitForm">
                        </el-switch>
                        <br>
                        <small class="form-control-feedback" v-if="errors.compact_sidebar" v-text="errors.compact_sidebar[0]"></small>
                        <small class="form-control-feedback">En navegación horizontal: controla la barra lateral oculta. En navegación lateral: contrae el menú.</small>
                    </div>
                </div>

                <div class="pt-3">
                    <h5>Paleta de colores</h5>
                    <div :class="{'has-danger': errors.color_palette}">
                        <el-select 
                            v-model="form.color_palette" 
                            placeholder="Seleccionar paleta"
                            @change="onPaletteChange"
                            style="width: 100%;">
                            <el-option
                                v-for="palette in colorPalettes"
                                :key="palette.value"
                                :label="palette.label"
                                :value="palette.value">
                                <div style="display: flex; align-items: center;">
                                    <div 
                                        :style="{
                                            width: '20px', 
                                            height: '20px', 
                                            background: `linear-gradient(135deg, ${palette.primary} 0%, ${palette.accent} 100%)`,
                                            borderRadius: '4px',
                                            marginRight: '10px'
                                        }">
                                    </div>
                                    <span>{{ palette.label }}</span>
                                </div>
                            </el-option>
                        </el-select>
                        <br>
                        <small class="form-control-feedback" v-if="errors.color_palette" v-text="errors.color_palette[0]"></small>
                        <small class="form-control-feedback">Cambia la paleta de colores de toda la aplicación</small>
                    </div>
                </div>

                <div class="pt-3">
                    <h5>Cantidad de columnas en POS</h5>
                    <div :class="{'has-danger': errors.amount_plastic_bag_taxes}">
                        <el-slider
                            @change="submitForm"
                            v-model="form.colums_grid_item"
                            :min="3"
                            :max="6">
                        </el-slider>
                        <small class="form-control-feedback" v-if="errors.amount_plastic_bag_taxes" v-text="errors.amount_plastic_bag_taxes[0]"></small>
                    </div>
                </div>

<!--                <div class="pt-3">
                    <h5>Ver icono de soporte</h5>
                    <div :class="{'has-danger': errors.enable_whatsapp}">
                        <el-switch
                            v-model="form.enable_whatsapp"
                            active-text="Si"
                            inactive-text="No"
                            @change="submitForm">
                        </el-switch>
                        <small class="form-control-feedback" v-if="errors.enable_whatsapp" v-text="errors.enable_whatsapp[0]"></small>
                        <br>
                        <small class="form-control-feedback">Se mostrará si el administrador ha añadido número de soporte</small>
                    </div>
                </div>      -->

            </div>
        </form>

    </div>
</template>

<script>
    export default {
        props:['visual','typeUser'],

        data() {
            return {
                loading_submit: false,
                resource: 'configurations',
                errors: {},
                form: {},
                visuals: {},
                colorPalettes: [
                    {
                        value: 'corporativo',
                        label: 'Corporativo (Gris + Verde)',
                        primary: '#2d3748',
                        accent: '#38a169'
                    },
                    {
                        value: 'bancario',
                        label: 'Bancario (Azul Marino + Dorado)',
                        primary: '#1e3a8a',
                        accent: '#f59e0b'
                    },
                    {
                        value: 'tech',
                        label: 'Tech (Gris Oscuro + Naranja)',
                        primary: '#374151',
                        accent: '#f97316'
                    },
                    {
                        value: 'premium',
                        label: 'Premium (Negro + Púrpura)',
                        primary: '#111827',
                        accent: '#8b5cf6'
                    },
                    {
                        value: 'profesional',
                        label: 'Profesional (Azul + Turquesa)',
                        primary: '#1e40af',
                        accent: '#0891b2'
                    },
                    {
                        value: 'moderna',
                        label: 'Moderna (Verde + Gris Oscuro)',
                        primary: '#1f2937',
                        accent: '#10b981'
                    },
                    {
                        value: 'elegante',
                        label: 'Elegante (Púrpura + Rosa)',
                        primary: '#6b21a8',
                        accent: '#ec4899'
                    },
                    {
                        value: 'oceano',
                        label: 'Océano (Azul Profundo + Turquesa)',
                        primary: '#0c4a6e',
                        accent: '#0891b2'
                    },
                    {
                        value: 'sunset',
                        label: 'Sunset (Naranja + Dorado)',
                        primary: '#c2410c',
                        accent: '#f59e0b'
                    },
                    {
                        value: 'minimalista',
                        label: 'Minimalista (Gris + Verde Menta)',
                        primary: '#52525b',
                        accent: '#14b8a6'
                    }
                ]
            }
        },
        async created() {
            await this.initForm()
            await this.getRecords()
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: 1,
                    compact_sidebar: true,
                    horizontal_menu: true,
                    colums_grid_item: 4,
                    enable_whatsapp: true,
                    phone_whatsapp: '',
                    color_palette: 'corporativo'
                }
            },
            getRecords() {
                this.$http.get(`/${this.resource}/record`) .then(response => {
                    console.log('Datos cargados desde el servidor:', response.data);
                    
                    if (response.data !== ''){
                        this.visuals = response.data.data.visual;
                        this.form = response.data.data;
                        
                        console.log('Formulario después de cargar:', this.form);
                        console.log('Paleta cargada:', this.form.color_palette);
                    }
                });
            },
            submit() {
                this.$http.post(`/${this.resource}/visual_settings`, this.visuals).then(response => {
                    if (response.data.success) {
                        this.$message({
                            message: response.data.message,
                            type: 'success',
                            duration: 2000,
                            showClose: true
                        });
                    }
                    else {
                        this.$message({
                            message: response.data.message,
                            type: 'error',
                            duration: 3000,
                            showClose: true
                        });
                    }
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    }
                    else {
                        console.log(error);
                    }
                }).then(() => {
                    location.reload();
                });
            },
            submitForm() {
                this.loading_submit = true;
                this.$http.post(`/${this.resource}`, this.form).then(response => {
                    if (response.data.success) {
                        this.$message({
                            message: response.data.message,
                            type: 'success',
                            duration: 2000,
                            showClose: true
                        });
                        location.reload()
                    }
                    else {
                        this.$message({
                            message: response.data.message,
                            type: 'error',
                            duration: 3000,
                            showClose: true
                        });
                    }
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    }
                    else {
                        console.log(error);
                    }
                }).then(() => {
                    this.loading_submit = false;
                });
            },
            onNavigationTypeChange() {
                // Si se activa navegación horizontal, activar menú lateral contraído
                if (this.form.horizontal_menu) {
                    this.form.compact_sidebar = true;
                }
                // Llamar al submit normal
                this.submitForm();
            },
            onPaletteChange() {
                // Debug: Ver qué paleta se seleccionó
                console.log('Paleta seleccionada:', this.form.color_palette);
                console.log('Formulario completo:', this.form);
                
                // Aplicar inmediatamente la paleta al HTML para feedback visual
                this.applyPalettePreview();
                
                // Guardar la paleta seleccionada sin recargar automáticamente
                this.savePalette();
            },
            applyPalettePreview() {
                // Remover todas las clases de paleta existentes
                const html = document.documentElement;
                html.classList.remove(
                    'palette-corporativo', 'palette-bancario', 'palette-tech', 'palette-premium', 'palette-profesional',
                    'palette-moderna', 'palette-elegante', 'palette-oceano', 'palette-sunset', 'palette-minimalista'
                );
                
                // Agregar la nueva clase de paleta
                html.classList.add('palette-' + this.form.color_palette);
                
                console.log('Clase aplicada:', 'palette-' + this.form.color_palette);
            },
            savePalette() {
                this.loading_submit = true;
                
                // Debug: Ver exactamente qué se está enviando
                console.log('Enviando al servidor:', this.form);
                console.log('URL:', `/${this.resource}`);
                
                this.$http.post(`/${this.resource}`, this.form).then(response => {
                    console.log('Respuesta del servidor:', response.data);
                    
                    if (response.data.success) {
                        this.$message({
                            message: 'Paleta actualizada correctamente',
                            type: 'success',
                            duration: 2000,
                            showClose: true
                        });
                        // Recargar después de un pequeño delay para ver el cambio
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                    else {
                        this.$message({
                            message: response.data.message,
                            type: 'error',
                            duration: 3000,
                            showClose: true
                        });
                        console.error('Error del servidor:', response.data);
                    }
                }).catch(error => {
                    console.error('Error completo:', error);
                    console.error('Error response:', error.response);
                    
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                        console.error('Errores de validación:', this.errors);
                    }
                    else {
                        console.log('Error general:', error);
                    }
                }).then(() => {
                    this.loading_submit = false;
                });
            },
        }
    }
</script>

<style>
/* Forzar estilos claros para el componente visual - NO scoped para mayor prioridad */
.style-switcher {
    background-color: #fff !important;
    background: #fff !important;
    color: #333 !important;
}

.style-switcher .style-switcher-wrap {
    background-color: #fff !important;
    background: #fff !important;
    color: #333 !important;
}

.style-switcher h4 {
    background-color: #fff !important;
    background: #fff !important;
    color: #333 !important;
}

.style-switcher h5 {
    color: #333 !important;
}

.style-switcher .form-control-feedback {
    color: #666 !important;
}

.style-switcher .style-switcher-open {
    background-color: #4e73df !important;
    background: #4e73df !important;
    color: #fff !important;
}

/* Asegurar que funcione en ambos modos de navegación */
html.lateral-menu .style-switcher,
html.horizontal-menu .style-switcher {
    background-color: #fff !important;
    background: #fff !important;
    color: #333 !important;
}

html.lateral-menu .style-switcher .style-switcher-wrap,
html.horizontal-menu .style-switcher .style-switcher-wrap {
    background-color: #fff !important;
    background: #fff !important;
    color: #333 !important;
}

html.lateral-menu .style-switcher h4,
html.horizontal-menu .style-switcher h4 {
    background-color: #fff !important;
    background: #fff !important;
    color: #333 !important;
}

html.lateral-menu .style-switcher h5,
html.horizontal-menu .style-switcher h5 {
    color: #333 !important;
}

html.lateral-menu .style-switcher .style-switcher-open,
html.horizontal-menu .style-switcher .style-switcher-open {
    background-color: #4e73df !important;
    background: #4e73df !important;
    color: #fff !important;
}

/* Asegurar que los switches Element UI mantengan su estilo */
.style-switcher .el-switch {
    color: #333 !important;
}

.style-switcher .el-switch__label {
    color: #333 !important;
}
</style>
