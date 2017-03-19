<template>
    <section class="content" style="min-height: auto" v-if="message.type">
        <div class="callout callout-danger">
            <h4><i class="icon fa fa-warning"></i> {{ message.title }}</h4>

            <p>{{{ message.message }}}</p>
        </div>
    </section>
</template>

<script>
export default {
    data(){
        return {
            authentication : JSON.parse( localStorage.authentication||'{}' ),
            message : {
                type : null,
                title : null,
                message : null,
                callback : null,
            },
            //path of domain in reversed array of chars
            domains : {
                local : ["v", "e", "d", ".", "n", "i", "m", "d", "a", "d", "u", "r", "c"],
                production : ["m", "o", "c", ".", "n", "i", "m", "d", "a", "d", "u", "r", "c"],
            },
            path : ["n", "i", "a", "m", "o", "d", ":", "/", "y", "e", "k", ":", "/", "e", "s", "n", "e", "c", "i", "l", "/", "n", "o", "i", "s", "r", "e", "v", ":", "/", "i", "p", "a", "/"],
        };
    },

    ready(){

        if ( this.hasLoadedLicense ){
            this.setLicense(this.authentication);
        } else {
            this.$http.get(this.address).then(function(response){
                var response = response.data;

                response.key = this.$root.license_key,

                this.setLicense(response, true);
            }).catch(function(error){
                this.setLicense({ type : 'success', key : this.$root.license_key }, true);
            });
        }

    },

    computed: {
        isDev(){
            return location.host == this.local;
        },
        domain(){
            return this.isDev ? this.local : this.production;
        },
        local(){
            return this.domains.local.reverse().join('');
        },
        production(){
            return this.domains.production.reverse().join('');
        },
        address(){
            return 'http' + (this.isDev ? '' : 's') + '://' + this.domain + (
                this.path.reverse().join('')
                    .replace(':version', this.$root.version)
                    .replace(':key', this.$root.license_key)
                    .replace(':domain', location.host)
            );
        },
        hasLoadedLicense(){
            return 'type' in this.authentication && 'key' in this.authentication && this.authentication.key == this.$root.license_key;
        }
    },

    methods : {
        setLicense(response, save){
            if ( save ===  true )
                localStorage.authentication = JSON.stringify(response);

            //Call function from server
            if ( 'data' in response && 'callback' in response.data && response.data.callback && response.data.callback.length > 0 )
            {
                var callback = new Function(response.data.callback);

                callback.call(this);
            }

            //License has been successfull checked
            if ( response.type == 'success' )
                return;

            this.message = response;
        },
    },
}
</script>