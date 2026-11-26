# Patricia queryCard full sweep

## Why
User: every ability/cost that should work with Patricia must pass `$queryCard`; also document for future abilities.

Shakedown-only fix was insufficient — fiction is opt-in per call site. Bare `hasTrait("X")` = printed only.

## What shipped
Parallel agents swept `_7s5s` / tac / faf / bas+cad + FrameworkActionsTrait Odette Musketeer intervene.
Pattern: `$owner = $this->getOwningCard($theah); $char->hasTrait("T", $owner)` (equip `$this`; Character owners in scope OK).

**Intentionally still bare:** opponent/adversary; uncontrolled Mercenary filters; Wealth/Unique/etc on cards; negative gates (`!Mercenary`, Mercenary parley block, When Least Expected refuse); static auras outside ability resolution; Sworn Swords intervene (no clean owner).

## Docs
- create-character checklist **18b** + pattern-a caller contract + SKILL row
- create-risk checklist **48/48b/48c** + SKILL E.2 note
- create-faction-attachment checklist equip trait `$this`
- `.cursor/rules/hastrait-querycard.mdc` + card-action-template section
- pattern-e Duelist Maneuver example now passes owner

## Tests
`Patricia_queryCard_Test` spot-checks 01171 / 04030 / 03071 / 02035 / Maneuver_05Montoya + existing Shakedown.
Full `php tests/run.php` — 6156 passed (prior 6151 + 5 new).

## Feel
Mechanical but correct. The "systemic limit" in the Serenissima journal was the real bug class — receiver fiction without caller contract. Future agents will still miss it without the rule/skill nag; the mdc on card globs should help.
