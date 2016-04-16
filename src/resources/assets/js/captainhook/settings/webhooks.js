Vue.component('captainhook-webhooks', {

    props: ['webhooks', 'availableEvents'],


    /**
     * The component's data.
     */
    data() {
        return {
            logForWebhook: null,
            updatingWebhook: null,
            deletingWebhook: null,
            inspectedLog: null,

            updateWebhookForm: new SparkForm({
                url: '',
                event: '',
            }),

            deleteWebhookForm: new SparkForm({})
        }
    },

    filters: {
        readableName: function(event) {
            var readable = '';
            this.availableEvents.forEach(function(value, key){
                if(value.event === event) {
                    readable = value.name;
                }
            });
            return readable;
        }
    },


    methods: {
        /**
         * Show the edit webhook modal.
         */
        editWebhook(webhook) {
            this.updatingWebhook = webhook;

            this.initializeUpdateFormWith(webhook);

            $('#modal-update-webhook').modal('show');
        },


        /**
         * Show the edit webhook modal.
         */
        showWebhookLogs(webhook) {
            this.logForWebhook = webhook;
            this.inspectedLog = null;

            $('#modal-webhook-logs').modal('show');
        },

        /**
         *
         */
        toggleInspectLog(log) {
            if (this.inspectedLog && log === this.inspectedLog) {
                this.inspectedLog = null;
            } else {
                this.inspectedLog = log;
            }
        },

        /**
         * Initialize the edit form with the given webhook.
         */
        initializeUpdateFormWith(webhook) {
            this.updateWebhookForm.url = webhook.url;

            this.updateWebhookForm.event = webhook.event;
        },


        /**
         * Update the webhook being edited.
         */
        updateWebhook() {
            Spark.put(`/settings/api/webhook/${this.updatingWebhook.id}`, this.updateWebhookForm)
                .then(response => {
                this.$dispatch('updateWebhooks');

            $('#modal-update-webhook').modal('hide');
        })
        },

        /**
         * Get user confirmation that the webhook should be deleted.
         */
        approveWebhookDelete(webhook) {
            this.deletingWebhook = webhook;

            $('#modal-delete-webhook').modal('show');
        },


        /**
         * Delete the specified webhook.
         */
        deleteWebhook() {
            Spark.delete(`/settings/api/webhook/${this.deletingWebhook.id}`, this.deleteWebhookForm)
                .then(() => {
                this.$dispatch('updateWebhooks');

            $('#modal-delete-webhook').modal('hide');
        });
        }
    }
});