<captainhook-webhooks :webhooks="webhooks" :available-events="availableEvents" inline-template>
    <div>
        <div class="panel panel-default" v-if="webhooks.length > 0">
            <div class="panel-heading">Webhooks</div>

            <div class="panel-body">
                <table class="table table-borderless m-b-none">
                    <thead>
                    <th>Name</th>
                    <th>Event</th>
                    <th>Last Response Code</th>
                    <th></th>
                    <th></th>
                    </thead>

                    <tbody>
                    <tr v-for="webhook in webhooks">
                        <!-- Name -->
                        <td>
                            <div class="btn-table-align">
                                @{{ webhook.url }}
                            </div>
                        </td>

                        <!-- Event -->
                        <td>
                            <div class="btn-table-align">
                                @{{ webhook.event | readableName }}
                            </div>
                        </td>

                        <!-- Last Used At -->
                        <td>
                            <div class="btn-table-align">
                                    <span v-if="webhook.last_log">
                                        @{{ webhook.last_log.status }}
                                    </span>

                                    <span v-else>
                                        Never called
                                    </span>
                            </div>
                        </td>

                        <!-- Edit Button -->
                        <td>
                            <button class="btn btn-primary" @click="editWebhook(webhook)">
                            <i class="fa fa-pencil"></i>
                            </button>
                        </td>

                        <!-- Log Button -->
                        <td>
                            <button class="btn btn-primary" @click="showWebhookLogs(webhook)" :disabled="! webhook.last_log">
                            <i class="fa fa-list"></i>
                            </button>
                        </td>

                        <!-- Delete Button -->
                        <td>
                            <button class="btn btn-danger-outline" @click="approveWebhookDelete(webhook)">
                            <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Webhook Modal -->
    <div class="modal fade" id="modal-update-webhook" tabindex="-1" role="dialog">
        <div class="modal-dialog" v-if="updatingWebhook">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button " class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

                    <h4 class="modal-title">
                        Edit Webhook
                    </h4>
                </div>

                <div class="modal-body">
                    <!-- Update Webhook Form -->
                    <form class="form-horizontal" role="form">
                        <!-- Webhook Name -->
                        <div class="form-group" :class="{'has-error': updateWebhookForm.errors.has('url')}">
                            <label class="col-md-4 control-label">Webhook URL</label>

                            <div class="col-md-6">
                                <input type="text" class="form-control" name="url" v-model="updateWebhookForm.url">

                                <span class="help-block" v-show="updateWebhookForm.errors.has('url')">
                                    @{{ updateWebhookForm.errors.get('url') }}
                                </span>
                            </div>
                        </div>

                        <!-- Webhook Event -->
                        <div class="form-group" :class="{'has-error': updateWebhookForm.errors.has('event')}" v-if="availableEvents.length > 0">
                            <label class="col-md-4 control-label">Select Event</label>

                            <div class="col-md-6">
                                <select class="form-control" name="event" v-model="updateWebhookForm.event">
                                    <template v-for="event in availableEvents">
                                        <option value="@{{event.event}}">@{{event.name}}</option>
                                    </template>
                                </select>

                                <span class="help-block" v-show="updateWebhookForm.errors.has('event')">
                                    @{{ updateWebhookForm.errors.get('event') }}
                                </span>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Actions -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

                    <button type="button" class="btn btn-primary" @click="updateWebhook" :disabled="updateWebhookForm.busy">
                    Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Webhook Modal -->
    <div class="modal fade" id="modal-delete-webhook" tabindex="-1" role="dialog">
        <div class="modal-dialog" v-if="deletingWebhook">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button " class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

                    <h4 class="modal-title">
                        Delete Webhook
                    </h4>
                </div>

                <div class="modal-body">
                    Are you sure you want to delete this webhook? If deleted, the webhook endpoint (@{{ deletingWebhook.url }}) will no
                    longer receive HTTP requests.
                </div>

                <!-- Modal Actions -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">No, Go Back</button>

                    <button type="button" class="btn btn-danger" @click="deleteWebhook" :disabled="deleteWebhookForm.busy">
                    Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Webhook Logs Modal -->
    <div class="modal fade" id="modal-webhook-logs" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" v-if="logForWebhook">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button " class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

                    <h4 class="modal-title">
                        Webhook Logs
                    </h4>
                </div>

                <div class="modal-body">
                    <table class="table table-borderless m-b-none">
                        <thead>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Response Code</th>
                        <th></th>
                        </thead>

                        <tbody>
                        <tr v-for="log in logForWebhook.logs">
                            <!-- ID -->
                            <td>
                                <div class="btn-table-align">
                                    @{{ log.id }}
                                </div>
                            </td>

                            <!-- Date -->
                            <td>
                                <div class="btn-table-align">
                                    @{{ log.created_at | datetime }}
                                </div>
                            </td>

                            <!-- Status -->
                            <td>
                                <div class="btn-table-align">
                                    @{{ log.status }}
                                </div>
                            </td>


                            <!-- Info Button -->
                            <td>
                                <button class="btn btn-primary" @click="toggleInspectLog(log)">
                                <i class="fa fa-eye" :class="{ 'fa-eye': inspectedLog !== log, 'fa-eye-slash': inspectedLog === log }"></i>
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div v-if="inspectedLog">
                        <hr></hr>
                        <strong>Event Data</strong>
                        <pre>@{{ inspectedLog.payload }}</pre>
                        <strong>Response Data</strong>
                        <pre>@{{ inspectedLog.response }}</pre>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</captainhook-webhooks>
