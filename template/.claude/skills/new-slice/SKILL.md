---
name: new-slice
description: >-
  Add a new feature endpoint (vertical slice) inside an existing {{PRODUCT_NAME}}
  module: the command or query, handler, controller, request, resource, event,
  and tests for one action. Controller files are skipped for slices dispatched
  from a job or a listener. Not for creating a brand-new module (see new-module).
---

# Creating a New Vertical Slice

You are adding one action to an existing module. Follow every rule below. The
full rules are in `.ai/rules/architecture.md` and `.ai/rules/orchestration-di.md`;
read both before writing.

## Pre-flight

0. If the slice adds an aggregate, a cross-module interaction, or a term missing
   from the glossary in the `{{PRODUCT_SLUG}}-docs` brain vault, run
   `/domain-design` first. Otherwise proceed.
1. Read `docs/ARCHITECTURE.md` and the module's existing slices.
2. Identify the module that owns the capability. If the slice needs another
   module's classes to do its job, it is in the wrong place or the collaboration
   must become a contract or an event (`.ai/rules/isolation.md`).
3. If the module has more than ten action folders, pick the domain concern
   group (for example `Lifecycle/`, `Queries/`, `Management/`).

## File placement: entity concept folders

- Group files by entity, not by type. Models, factories, enums, exceptions,
  events and listeners live in entity concept folders such as
  `{{PRODUCT_NAMESPACE}}/Billing/Subscription/`.
- Never create type folders: no `Enums/`, `Events/`, `Listeners/`,
  `Exceptions/`, `Models/`, `Contracts/`.
- Service providers, route files and the module base exception stay at the
  module root.

## Slice structure (write operation)

```
{{PRODUCT_NAMESPACE}}/<Module>/<Action>/
  <Action>Command.php          final readonly, promoted props
  <Action>CommandHandler.php   final readonly, orchestrator only
  <Action>Controller.php       final readonly, single-action __invoke()
  <Action>Request.php          array-based validation rules
  <Action>Resource.php         response shape
  <Action>Event.php            past tense, for example OrderPlaced
  Tests/
    <Action>ControllerTest.php
```

For a read operation, replace Command and Handler with Query and QueryHandler.
A fixed-criteria listing may omit the query object.

## Slices with no HTTP surface

Skip the controller, request, resource and route entry when no route reaches the
command, for example when a queued job or an event listener dispatches it.

- Create: Command, CommandHandler, an Event when state changes, an exception only
  when the caller must tell a failure apart, and a handler test.
- Test: `Tests/<Action>CommandHandlerTest.php` calls the handler and asserts the
  state change and the event. There is no controller test because there is no
  endpoint.
- Still required: the `Bus::map()` entry in the service provider.

## Orchestrator pattern

Application-layer classes coordinate, they do not execute. Controllers receive a
request, dispatch a command or query, and return a resource. Handlers resolve
dependencies, call model methods, and dispatch events. Business rules and state
transitions live on the model. Handlers never update model state directly, create
related records inline, or call external services directly. The DI replacement
table is in `.ai/rules/orchestration-di.md`.

## Tell, don't ask

Push the decision into the object. Write `recoverIfPastDue()` on the model, not
`if ($sub->isPastDue()) { $sub->activate(); }` in the handler. Lookups that throw
on absence are `findByXOrFail()` on the model.

## PHP standards

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders\PlaceOrder;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;

final readonly class PlaceOrderController
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function __invoke(PlaceOrderRequest $request): JsonResponse
    {
        // build the command from the validated input, dispatch it, return a resource
    }
}
```

Commands and events are `final readonly` with promoted public properties and carry
ids and scalars only. Use `declare(strict_types=1)` in every file.

## API resources

Never map collections by hand. Use `::collection()` with a dedicated resource
class, and give nested relations their own resource class:
`'items' => ItemResource::collection($this->items)`.

## Service provider registration

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders;

use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Support\ServiceProvider;
use {{PRODUCT_NAMESPACE}}\Orders\PlaceOrder\PlaceOrderCommand;
use {{PRODUCT_NAMESPACE}}\Orders\PlaceOrder\PlaceOrderCommandHandler;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->make(Bus::class)->map([
            PlaceOrderCommand::class => PlaceOrderCommandHandler::class,
        ]);
    }
}
```

Add the new command or query to the existing `Bus::map()` call in the module's
service provider.

## After creating the slice

1. Add the named route to the module's route file (`<module-name>-routes.php`).
2. Map the command or query to its handler in the module's service provider.
3. Register event listeners if the slice dispatches events.
4. Write tests following `.ai/rules/testing.md`: happy path, failure paths, edge
   cases, and a route-level assertion for every rendered HTTP status.
5. Run `/preflight`.
