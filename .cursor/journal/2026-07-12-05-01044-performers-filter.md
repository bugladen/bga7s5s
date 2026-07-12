# 01044 Armed and Marshaled — performer filter bug

## Context
Eddie reported `_01044:getAvailablePerformers()` including performers without attachments.

Last session work (from journal filenames): Soline 03040 / Inigo 03039 character skills, Damya Kahina, Loyal, etc. Unrelated to this bug.

## Actual bug
`getAvailablePerformers()` itself was fine — requires unengaged attachment + opposing with ≤ attachments.

The leak was `getPerformersForAction`:

```php
$performers = parent::getPerformersForAction(...); // SchemeCityAction: ALL city chars
$performers += $this->getAvailablePerformers(...);
```

WHY this fails: both arrays use numeric indices from `[] =`. PHP `+=` keeps left keys, so parent’s unfiltered list wins and the filtered list’s entries for keys 0..n are ignored. UI then offers performers with no attachments.

WHY not just `array_merge`: that would concatenate, still including the unfiltered parent set. Correct approach is return only the filtered list (like Action_01058).

## Fix
`getPerformersForAction` now returns `$this->getAvailablePerformers(...)` only. Left a WHY comment so nobody “restores” the parent merge.
