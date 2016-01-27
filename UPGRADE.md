# Upgrade Captain Hook from version 1.* to 2.*

## Breaking changes
- Custom event listeners are no longered declared in an extended Service Provider. Place your custom events in the `config/captain_hook.php` instead.
- There's no longer the need to extend the Service Provider.
