---
name: exceptions
description: >-
  Add a domain exception to a {{PRODUCT_NAME}} module: the module base exception,
  a specific exception with a named constructor, and the right HTTP status.
---

# Domain Exception Hierarchy

Exceptions render themselves, so controllers never need try-catch.

## Pattern

Each module has an abstract base exception that extends `DomainException` and
implements `render()`. Specific exceptions extend the base and override `render()`
when the status differs.

```
<Module>Exception (abstract, default 409)
  EntityNotFoundException (overrides to 404)
  InvalidStateException (inherits 409)
  NotAuthorizedException (overrides to 403)
```

## Module base exception

Create it at the module root, next to the service provider:

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders;

use DomainException;
use Illuminate\Http\JsonResponse;

abstract class OrdersException extends DomainException
{
    public function render(): JsonResponse
    {
        return new JsonResponse(
            data: ['message' => $this->getMessage()],
            status: 409,
        );
    }
}
```

The default is 409 Conflict, which fits most state violations such as already
cancelled or duplicate purchase.

## Specific exceptions

Place each in the entity concept folder it relates to, and build it through a
named constructor:

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders\Order;

use Illuminate\Http\JsonResponse;
use {{PRODUCT_NAMESPACE}}\Orders\OrdersException;

final class OrderNotFoundException extends OrdersException
{
    public static function forId(int $id): self
    {
        return new self("The order with ID {$id} was not found.");
    }

    public function render(): JsonResponse
    {
        return new JsonResponse(
            data: ['message' => $this->getMessage()],
            status: 404,
        );
    }
}
```

## HTTP statuses

| Status | When | Example |
|--------|------|---------|
| 404 | Entity not found | `OrderNotFoundException::forId($id)` |
| 403 | Authorization failure | `NotOrderOwnerException` |
| 409 | State conflict (default) | `OrderAlreadyCancelledException` |
| 422 | Business rule validation | `SeatLimitReachedException` |

## Rules

- The base exception lives at the module root; specific exceptions live in entity
  concept folders.
- Always use a named constructor. Never `throw new Exception('message')`.
- Every business-rule exception has a test that asserts the rendered HTTP status
  at the route level. A test that only calls `expectException()` cannot catch a
  wrong status code (`.ai/rules/testing.md`).
- An expected domain exception answering an HTTP client is a 4xx, not an incident.
  The same exception killing a queued job or a listener is an incident and must
  still be reported.
