# Laravel template

Template repository for the software factory. `scripts/new-repo.py` copies `template/` into a new product repository: PHP 8.5, Laravel 13, modules as root namespaces (no `app/` folder), one sample slice, and five gates that run in Docker.

## Make a repository

```
python3 scripts/new-repo.py --name "Sample Product" --slug sample-product \
    --pitch "One line." --target ../sample-product --modules Orders,Billing
```

The target must be missing or empty. The script fills the tokens, refuses to finish if any `{{TOKEN}}` is left, runs `git init` and makes the first commit. Exit code 2 means bad input.

| Flag | Meaning |
|------|---------|
| `--name` | Product name. No double quote, backslash or dollar sign. |
| `--slug` | Lowercase letters, digits and hyphens. Names the Compose project `<slug>-factory`. |
| `--pitch` | One line. |
| `--target` | Directory to create. |
| `--namespace` | PHP namespace and folder for the product modules. Default: StudlyCase of `--name`. |
| `--modules` | Comma-separated StudlyCase names. Each gets an empty module: a service provider, a route file, a `Migrations/` folder and one passing test. |
| `--date` | `YYYY-MM-DD`, default today. |
| `--no-git` | Skip `git init` and the first commit. |

## Tokens

| Token | Filled with |
|-------|-------------|
| `{{PRODUCT_NAME}}` | `--name` |
| `{{PRODUCT_SLUG}}` | `--slug` |
| `{{PRODUCT_PITCH}}` | `--pitch` |
| `{{PRODUCT_NAMESPACE}}` | `--namespace` |
| `{{DATE}}` | `--date` |

Folder and file names may carry tokens. `module-template/` is the stub for one `--modules` entry and uses `{{MODULE}}` and `{{MODULE_LOWER}}`.

## What the generated repository has

- `modules.php`: the one list of modules. Provider registration, PHPArkitect, PHPStan, Rector and the module isolation rule read it. PHPUnit scans the namespace folder and `ModuleListTest` fails when the folder and the list disagree.
- `Health/GetStatus`: the sample slice. A query, its handler wired with `Bus::map`, a controller, a resource and a feature test that needs MySQL.
- `.factory/recipe.yml`: setup, checks and teardown. Checks run in Docker with a dedicated Compose file and project name, publish no host ports and do not touch a running dev stack.
- Gates: `pint --test`, `phparkitect check`, `phpstan` at level max, `rector --dry-run`, `php artisan test`. `composer gates` runs all five.
- Dev stack: `docker-compose.yml` plus `docker-compose.dev.yml` (Traefik, PHP, MySQL 8.4, Redis, Mailpit, queue, scheduler), started with `spin up`.
- GitHub Actions for the five gates.

## Test the template

```
python3 -m unittest discover -s tests
```

These tests build sample repositories and inspect them, and test the import sorter. They do not need Docker.

The gate run builds three repositories (namespaces that sort before and after Illuminate, Framework and Tests) and runs the whole recipe on each. It needs Docker and about three minutes:

```
LARAVEL_TEMPLATE_GATES=1 python3 -m unittest tests.test_gates -v
```

`new-repo.py` re-sorts the `use` block of every PHP file after filling tokens, matching Pint's `ordered_imports` (alpha, const then class then function), because a namespace token changes the sort order per product.

## Rule copies

The files in `template/.ai/rules/` (except `index.md`) are copies of the Kanon references in [codysseydev/codyssey-skills](https://github.com/codysseydev/codyssey-skills) under `kanon/`. Each copy is a `paths:` header plus the source body. `scripts/rules-manifest.json` lists every copy with its source path and globs.

```
python3 scripts/sync-rules.py          # rewrite the copies from codyssey-skills main
python3 scripts/sync-rules.py --check  # write nothing, exit 1 and list every copy that drifted
```

Without `--skills <dir>` or `KANON_SKILLS_DIR`, the script keeps a shallow clone in `.cache/codyssey-skills` (gitignored) and resets it to `origin/main` on each run. `tests/test_rules_drift.py` runs `--check`, so the unit tests fail when a copy falls behind and need network access. Adding a rule means adding a manifest entry, running the sync and adding a row to `template/.ai/rules/index.md`.

## Refresh `composer.lock`

`template/composer.lock` is committed so a fresh clone installs the same versions. `new-repo.py` rewrites its `content-hash` for the new package name. After changing `template/composer.json`, run `composer update` in a generated repository and copy its `composer.lock` back.
