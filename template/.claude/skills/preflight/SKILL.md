---
name: preflight
description: >-
  Run the {{PRODUCT_NAME}} pre-PR verification chain: every gate in
  .factory/recipe.yml in order, stopping at the first failure, then a Kanon
  audit of the branch diff. Use when the user says "preflight", "run the checks",
  "verify before pushing", "is this ready for a PR", or before opening or updating
  a pull request. Not for reviewing someone else's PR.
---

# Preflight verification chain

The gates are the checks in `.factory/recipe.yml`. Read the recipe, then run each
check in the order it lists them, in Docker as the recipe says. Do not copy the
commands from memory; the recipe is the single source and it changes.

## Run order

1. Read `.factory/recipe.yml` and list the checks in order.
2. Run the first check. If it fails, stop. Report the failure with its output,
   propose the fix, and continue only once that check passes. A later gate passing
   means nothing while an earlier one fails.
3. Repeat for each check until all pass.

Notes that hold for any recipe:

- A formatter check that only reports (a `--test` flag) must pass without changing
  files. If it fails, run the formatter, then re-run the check, and mention the
  files it changed so they get committed.
- An architecture check enforces module isolation, class shape and naming. Never
  fix a failure by adding to a baseline file; fix the code.
- A static analysis check is at zero errors. Keep it there. Add no new ignore
  annotation without a stated reason.
- The test check runs the whole suite. If other agents may be running tests, use a
  throwaway test database so runs do not collide.

## Standards review

With every gate green, run the `laravel-kanon-audit` skill on the branch diff. It audits the changes against the Kanon rules, which are the same references copied into `.ai/rules/`. It covers what tooling cannot check: the orchestrator pattern, dependency injection over facades, tell-don't-ask, events as plain fact objects, isolation intent. Report each finding with the rule and the file and line.

## Final report

One line per gate: PASS or FAIL plus the relevant number (files formatted,
violations, errors, tests passed). Then the review findings, most severe first,
and a verdict: ready to push, or what must change first.
