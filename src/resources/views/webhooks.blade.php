<captainhook-webhook inline-template>
    <div>
        <!-- Create Webhooks -->
        <div>
            @include('captainhook::settings.webhooks.create-webhook')
        </div>

        <!-- Webhooks -->
        <div>
            @include('captainhook::settings.webhooks.webhooks')
        </div>
    </div>
</captainhook-webhook>
