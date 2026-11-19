# {{PRODUCT_NAME}} Architecture

{{PRODUCT_PITCH}}

The product's decisions, glossary, brief and event model live in the `{{PRODUCT_SLUG}}-docs` brain vault. This file covers only how the code is laid out.

## Layout

```
{{PRODUCT_NAMESPACE}}/
  <Module>/          one bounded context per module
Framework/           shared infrastructure, no product rules
tests/Architecture/  architecture tests (module isolation, class shape, naming)
database/            seeders and shared database config; migrations live in each module
routes/              route bootstrap only; each module owns its route file
```

There is no `app/` folder. A module's namespace is `{{PRODUCT_NAMESPACE}}\<Module>` and mirrors its directory path.

## Modules

A module is a bounded context. It owns its models, migrations, slices and tests. Another module reaches it only through contracts and domain events, never through its classes, relationships or tables (`.ai/rules/isolation.md`).

The module list is the single file that registers every module. Add a new module there with `/new-module`.

## Slices

One action is one folder holding every piece it needs:

```
{{PRODUCT_NAMESPACE}}/<Module>/<Action>/
  <Action>Command.php          write slices; read slices use <Action>Query.php
  <Action>CommandHandler.php   orchestrates, holds no business logic
  <Action>Controller.php       single-action __invoke()
  <Action>Request.php          validation rules
  <Action>Resource.php         response shape
  <Action>Event.php            past-tense fact, only when state changes
  Tests/                       tests live beside the code
```

Models, factories, enums and exceptions sit in entity concept folders (`<Module>/<Entity>/`), never in type folders such as `Models/` or `Enums/`. The module's service provider maps each command to its handler with `Bus::map()` and loads its routes and migrations.

## Gates

The checks every change must pass are listed in `.factory/recipe.yml`, and they run in Docker. Run them with `/preflight`.
