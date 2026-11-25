# WealthCost test sync — Vantage Point + Pocketknife

## Failures
Full suite: 6148 pass, 2 fail.
1. `_04020` expected WealthCost 1, got 0
2. `_05Pocketknife` expected WealthCost 2, got 0

## WHY
User intentionally changed both in commits (not regressions):
- `6427b304` Vantage Point → 0 "matching printed card"
- `c692dab9` Pocketknife → 0 "wealth update"

Tests (and Pocketknife WHY comment claiming art II) were left behind.

## Fix
- `Card_04020_Test`: expect 0
- `Card_05Pocketknife_Test`: expect 0
- Pocketknife comment: note design update; art may still show II

8/8 green on those suites.

## Watch
Pocketknife.v5.jpg still shows Roman II — art lag or intentional free card; code/test now agree on 0.
