<template>
    <div class="refreshing">
      <div class="loading">
        <div class="middle">
          <i class="updating fa fa-refresh"></i>
          <p>{{ trans('loading') }}</p>
        </div>
      </div>
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