<captainhook-create-webhook :available-events="availableEvents" inline-template>
    <div class="panel panel-default">
        <div class="panel-heading">
            Create Webhook
        </div>

        <div class="panel-body">
            <form class="form-horizontal" role="form">
                <!-- Webhook Name -->
                <div class="form-group" :class="{'has-error': form.errors.has('url')}">
                    <label class="col-md-4 control-label">Webhook URL</label>

                    <div class="col-md-6">
                        <input type="text" class="form-control" name="url" v-model="form.url">

                        <span class="help-block" v-show="form.errors.has('url')">
                            @{{ form.errors.get('url') }}
                        </span>
                    </div>
                </div>

                <!-- Webhook Event -->
                <div class="form-group" :class="{'has-error': form.errors.has('events')}" v-if="availableEvents.length > 0">
                    <label class="col-md-4 control-label">Select Event</label>

                    <div class="col-md-6">
                        <select class="form-control" name="event" v-model="form.event">
                            <template v-for="event in availableEvents">
                                <option value="@{{event.event}}">@{{event.name}}</option>
                            </template>
                        </select>

                        <span class="help-block" v-show="form.errors.has('event')">
                            @{{ form.errors.get('event') }}
                        </span>
                    </div>
                </div>

                <!-- Create Button -->
                <div class="form-group">
                    <div class="col-md-offset-4 col-md-6">
                        <button type="submit" class="btn btn-primary"
                                @click.prevent="create"
                                :disabled="form.busy">

                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</captainhook-create-webhook>
