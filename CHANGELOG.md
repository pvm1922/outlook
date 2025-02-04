[5.0.0-alpha]

### Calendar changes

- Calendar construct will now require clientId, clientSecret and token as required readonly parameters

```php
public function __construct(
    private readonly string $clientId,
    private readonly string $clientSecret,
    private readonly string $token,
    array $args = []
)
```

- Sync method is removed. Call push/pull to send and receive events from outlook


- Get event and get event instances will require outlook id instead of url

```php
public function getEventBy(
    string $id,
    ?EventItemRequestBuilderGetQueryParameters $params = null,
    ?Closure $beforeReturn = null,
    array $args = []
): ?ReaderEntityInterface

public function getEventInstances(
    string $id,
    ?InstancesRequestBuilderGetQueryParameters $params = null,
    array $args = []
): void
```

- Writer/Delete entities are removed and are replaced by outlook model Event

- Delete method will not return any response except a 204 status code


- Batch push events will now return a generator instead of Batch Response entity

```php
public function handleBatchResponse(?Generator $responses = null): void;
```
- Update deleteEventLocal type declaration to ?string

```php
public function deleteEventLocal(?string $eventId): void
```
### Token Changes

- None or very limited changes to Token handler

### Subscription Changes

- construct now requires clientId, clientSecret and token as required readonly parameters

```php
public function __construct(
    private readonly string $clientId,
    private readonly string $clientSecret,
    private readonly string $token,
    array $args = []
)
```

- Subscription entity will not be available anymore. The graph api sdk Subscription model and entity is used instead

```php
public function subscribe(
    Microsoft\Graph\Generated\Models\Subscription $subscriptionEntity, array $args = []
): ?Microsoft\Graph\Generated\Models\Subscription
```

- Renew subscription will only update the expiry date of the token.

```php
public function renew(
    string $subscriptionId,
    \DateTime $expiration,
    array $args = []
): ?Microsoft\Graph\Generated\Models\Subscription
```

### Notification Changes

- With the new graph api the sequence number will not be available. Call the event api to get the changes and validate it.

## [2.0.0]

### Added
- [2](https://github.com/Symplicity/outlook/pull/2) - Add support for outlook push notifications
- [82ef0](https://github.com/Symplicity/outlook/commit/82ef0c79c991dcda3c8ef45ff1b4d941f857dc55) - Add retry handler for batch push
- [5ac8b](https://github.com/Symplicity/outlook/commit/5ac8b294d722ca937afa9632d5b91b95ed778f55) - Ability to pass skip query params to connection handler
- [2bf32](https://github.com/Symplicity/outlook/commit/2bf3229523ae5c997a8d531fb832eace45753635), [54e1f](https://github.com/Symplicity/outlook/commit/54e1fc2434c76f6df272c1afe2fe7f86c802ab69), [e5e0d](https://github.com/Symplicity/outlook/commit/e5e0db2daff1321296f53a0032d3a4a00c0409b4) - Add support for batch requests

### Removed
- [4b134](https://github.com/Symplicity/outlook/commit/4b1340c16af1a1bb268070313aef6e49dd2f4daa) - Remove concurrent pool upsert request option

## [1.0.0]

Initial Release.
