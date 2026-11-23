# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file. Each rule file is a copy of a Kanon reference with a `paths:` header added, kept in line with the Kanon source repository by the template repository's `scripts/sync-rules.py`. Edit a rule here only to record a decision the product made on purpose.

| Applies to | Rule file | Governs |
| --- | --- | --- |
| {{PRODUCT_NAMESPACE}}/**, Framework/** | .ai/rules/architecture.md | Modules as bounded contexts, vertical slices, placement, naming |
| {{PRODUCT_NAMESPACE}}/** | .ai/rules/authorization.md | Gates, ownership, guards, authorization from the authenticated principal |
| AdminPanel/** | .ai/rules/filament.md | Filament resources, pages and actions. Loads nowhere yet: the repo has no admin panel folder |
| routes/**, {{PRODUCT_NAMESPACE}}/**/*-routes.php, *Controller.php, *Request.php, *Resource.php | .ai/rules/http-api.md | Routes, middleware, form requests, API resources, webhooks |
| {{PRODUCT_NAMESPACE}}/**, Framework/** | .ai/rules/integrations.md | Third-party APIs, SDKs, outbound HTTP |
| {{PRODUCT_NAMESPACE}}/**, Framework/**, tests/Architecture/** | .ai/rules/isolation.md | What may cross a module boundary: contracts and events only |
| {{PRODUCT_NAMESPACE}}/**, Framework/** | .ai/rules/models-domain.md | Models, enums, DTOs, events, exceptions, naming |
| {{PRODUCT_NAMESPACE}}/**, Framework/** | .ai/rules/orchestration-di.md | Orchestrator pattern, handlers, jobs, listeners, dependency injection |
| {{PRODUCT_NAMESPACE}}/**, Framework/** | .ai/rules/performance.md | Eloquent and query-builder queries, list and report endpoints, eager loading, `$with` |
| database/**, config/**, bootstrap/providers.php, service providers, migrations, factories | .ai/rules/persistence.md | Migrations, factories, config, service providers, schedules |
| {{PRODUCT_NAMESPACE}}/**, Framework/**, tools/** | .ai/rules/php-core.md | Every production PHP class, whatever the framework |
| {{PRODUCT_NAMESPACE}}/** | .ai/rules/reliability.md | Batch imports, bulk jobs and other code that processes many independent records |
| **/Tests/**, tests/**, database/** | .ai/rules/testing.md | Test shape, coverage, factories in tests |
