<template>
    <div class="overlay">
      <i class="fa fa-refresh fa-spin"></i>
      <p>{{ trans('loading') }}</p>
    </div>
</template>

<script>
export default {
    methods: {
      trans(key){
        return this.$root.trans(key);
      }
    }
}
</script>