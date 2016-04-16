Vue.component('captainhook-create-webhook', {
    props: ['availableEvents'],


    /**
     * The component's data.
     */
    data() {
        return {
            allAbilitiesAssigned: false,

            form: new SparkForm({
                name: '',
                event: null
            })
        };
    },

    methods: {

        /**
         * Create a new webhook.
         */
        create() {
            Spark.post('/settings/api/webhook', this.form)
                .then(response => {
                    this.resetForm();

                    this.$dispatch('updateWebhooks');
                });
        },


        /**
         * Reset the webhook form back to its default state.
         */
        resetForm() {
            this.form.url = '';
            this.form.event = '';
        }
    }
});