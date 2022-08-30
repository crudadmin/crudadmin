<template>
    <div class="sitebuilder__add form-group" :class="{ '--selected' : value ? true : false }">
        <h2 v-if="!value">{{ field.name }}</h2>

        <div class="sb_add_items">
            <div class="row">
                <div class="sb_add_items__col col-md-3" v-for="option in field.options" @click="selectBlock(option)">
                    <div class="sb_add_item" :class="{ '--active' : option[0] == value }">
                        <div class="icon-sb-circle">
                            <i class="fa" :class="option[1].icon"></i>
                        </div>
                        <span>{{ option[1].name }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Wrapped properly for correct error message -->
        <div class="radio-wrapper">
            <div>
                <input v-for="option in field.options" type="radio" :checked="option[0] == value" :name="field_key" :value="value">
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row', 'model'],

    mounted(){
        this.reloadAllBlocks();
    },

    watch : {
        'field.options'(options){
            this.hideAllBlockGroups();
        },
        value(){
            this.reloadAllBlocks();
        },
    },

    computed : {
        //Get input value
        value(){
            return this.field.value || this.field.default;
        },
    },

    methods : {
        hideAllBlockGroups(){
            this.field.options.forEach(item => {
                this.model.hideGroup('sb_block_'+item[0]);
            });
        },
        //Update input value
        selectBlock(option){
            this.field.value = option[0];
        },
        reloadAllBlocks(){
            this.hideAllBlockGroups();
            this.model[this.value ? 'showGroup' : 'hideGroup']('sitebuilder_blocks');

            if ( this.value ) {
                this.model.showGroup('sb_block_'+this.value);
            }
        }
    }
}
</script>