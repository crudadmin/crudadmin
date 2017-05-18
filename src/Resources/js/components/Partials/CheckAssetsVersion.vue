<template>
    <section class="content" style="min-height: auto" v-if="isDifferent">
        <div class="callout callout-warning">
            <h4><i class="icon fa fa-warning"></i> Oooops!</h4>

            <p>Vaša nainštalovaná verzia administrácie <strong>CrudAdmin {{ version }}</strong> v systéme Laravel sa nezhoduje s verziou <strong v-if="version_assets">({{ version_assets }})</strong> v public priečinku.<br>
            Kôli lepšiej stabilite odporúčame spustiť príkaz <strong>php artisan admin:update</strong> pre synchronizáciu vendor priečinkov.</p>
        </div>
    </section>
</template>

<script>
export default {
    data(){
        return {
            version : this.$root.version,
            version_assets : this.$root.version_assets,
        };
    },

    computed: {
        isDifferent(){
            return this.version != this.version_assets;
        },
    },
}
</script>