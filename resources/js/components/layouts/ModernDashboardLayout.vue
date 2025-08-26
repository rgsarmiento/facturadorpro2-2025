<template>
    <div class="modern-dashboard-layout">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <slot></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ModernDashboardLayout',
    props: {
        title: {
            type: String,
            default: ''
        }
    },
    mounted() {
        if (this.title) {
            document.title = this.title;
        }
    }
}
</script>

<style scoped>
.modern-dashboard-layout {
    min-height: 100vh;
    background-color: #f8f9fa;
}

.container-fluid {
    padding: 20px;
}
</style>
