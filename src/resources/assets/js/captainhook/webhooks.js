require('./settings/webhooks');
require('./settings/create-webhook');

Vue.component('captainhook-webhook', {
    /**
     * The component's data.
     */
    data() {
        return {
            webhooks: [],
            availableEvents: []
        };
    },


    /**
     * Prepare the component.
     */
    ready() {
        this.getWebhooks();
        this.getAvailableEvents();
    },


    events: {
        /**
         * Broadcast that child components should update their webhooks.
         */
        updateWebhooks() {
            this.getWebhooks();
        }
    },


    methods: {
        /**
         * Get the current API webhooks for the user.
         */
        getWebhooks() {
            this.$http.get('/settings/api/webhooks')
                .then(function(response) {
                    this.webhooks = response.data;
                });
        },


        /**
         * Get all of the available webhook events.
         */
        getAvailableEvents() {
            this.$http.get('/settings/api/webhooks/events')
                .then(function(response) {
                    this.availableEvents = response.data;
                });
        }
    }
});


