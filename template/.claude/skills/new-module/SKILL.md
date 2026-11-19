---
name: new-module
description: >-
  Scaffold a brand-new {{PRODUCT_NAME}} bounded context (module): the module
  folder, its service provider, its route file, and its entry in the module list.
  Not for adding a feature to an existing module (see new-slice). The isolation
  rules a new module must follow are in .ai/rules/isolation.md.
---

# Creating a New Module

## Pre-flight

This skill needs `/domain-design` output as input: the context name, its slice
list, and the glossary delta. A new module without it is a placement decision
made by accident. Run `/domain-design` first if it has not run for this change.

Read `docs/ARCHITECTURE.md` and `.ai/rules/isolation.md`. A module talks to other
modules through contracts and domain events only: no cross-module imports, no
Eloquent relationships across modules, plain id columns with no foreign key for
cross-module references.

## Module structure

```
{{PRODUCT_NAMESPACE}}/<Module>/
  <Module>ServiceProvider.php     bootstrap, Bus::map(), event listeners
  <module-name>-routes.php        named routes
  Migrations/                     this module's migrations
  <Entity>/                       entity concept folder
    <Entity>.php                  model
    <Entity>Factory.php           factory
    <Entity>Status.php            enum
  <Action>/                       vertical slice (see /new-slice)
    Tests/
  <Module>Exception.php           abstract base exception (see /exceptions)
```

The namespace mirrors the directory: `{{PRODUCT_NAMESPACE}}\<Module>\<Action>`.
Group actions into concern folders (`Lifecycle/`, `Queries/`) only once the module
passes about ten action folders.

## Service provider

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Events\Dispatcher as Event;
use Illuminate\Support\ServiceProvider;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCommandHandlers();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/orders-routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->registerEventListeners();
    }

    private function registerCommandHandlers(): void
    {
        $this->app->make(Bus::class)->map([
            // PlaceOrderCommand::class => PlaceOrderCommandHandler::class,
        ]);
    }

    private function registerEventListeners(): void
    {
        /** @var Event $event */
        $event = $this->app->make(Event::class);
        // $event->listen(SomeEvent::class, SomeListener::class);
    }
}
```

Register scheduled jobs in the module's service provider with
`Schedule::job(new ExpireStaleOrdersJob)->daily()`, never in `routes/console.php`.

## Register the module

Add the new module to the module list, the file that registers every module's
service provider. A module that is not in the list does not boot. Then update the
module section of `docs/ARCHITECTURE.md` if it names the modules, and add the
module's key terms to the glossary in the `{{PRODUCT_SLUG}}-docs` brain vault.

## Checklist

- [ ] Module folder created under `{{PRODUCT_NAMESPACE}}/`
- [ ] Service provider created with command handlers, event listeners, route and
      migration loading
- [ ] Module added to the module list
- [ ] Route file created with named routes
- [ ] Migrations created under the module's `Migrations/` folder
- [ ] Isolation honored (`.ai/rules/isolation.md`): no cross-module imports or
      relationships, no facades or global helpers in production code
- [ ] Tests live in `Tests/` inside each action folder
- [ ] `/preflight` passes (the architecture tests cover isolation)
