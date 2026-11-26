# Blackmail discount vs Patricia (05Shakedown / 05Serenissima)

## Context
Last session notes were wealth-cost test sync / cancel-sibling audit / Otto-Kaj-Gustavo drop ack. User report: Blackmail (-1 if performer Scoundrel/Villain) not discounting when Patricia performs.

## Bug
`Action_05Shakedown::getActionFromHandDiscount` called `$performer->hasTrait("Scoundrel")` / `Villain` with **no** `$queryCard`.

Patricia's `hasTrait` (Uwe shape) only invents traits when `$queryCard !== null` + same ControllerId + not blanked. Null query = printed traits only — she is Hero/Academic/…, not Scoundrel/Villain.

## Fix
Pass `$owner` (the Risk) as `$queryCard`:
`$performer->hasTrait("Scoundrel", $owner) || $performer->hasTrait("Villain", $owner)`

WHY owner not action: `hasTrait` signature is `?Card $queryCard`; Action is not a Card. Same-controller gate uses `$queryCard->ControllerId` — Risk in hand shares Patricia's controller. Matches Maneuver_01051 / Action_01036 passing the ability owner.

## Test
`Action_05Shakedown_Test` — Patricia at Docks, assert discount 1. Suite 6 PASS.

## Footgun for later (not fixed this pass)
Same bare `hasTrait` on other E.2 performer-trait discounts: `Action_01171`, `Action_01176`, `Action_04030`/`04059`, etc. Any of those will also miss Patricia until they pass queryCard. create-risk E.2 checklist does not yet say "pass owning card as queryCard" — skill update if user asks / next E.2 card.

## Follow-up audit (user asked who else misses)
Audit via explore agent 258174d8 — systemic: almost all trait gates/discounts omit queryCard. Only Shakedown + Uwe consumers (01036/01039/01051) pass it.

**Cost discounts still broken for Patricia:**
- `Action_04030` / `Maneuver_04030` Merchant|Scoundrel
- `Action_04059` Merchant|Scoundrel
- `Action_01171` Villain|Scoundrel
- `Action_01176` Scoundrel half (Hero already printed → no-op)
- `Action_01160` Leader Villain
- `Action_03071` Leader Villain|Pirate

**Not misses (she already prints the trait):** Academic/Hero/Leader gates (04018 Academic half, 01159 Hero, RedTape/Refuge Academic performers, etc.)

**Ability-use gates (dozens):** Pirate/Scoundrel/Duelist/Sorcerer/Strega/Diplomat/Musketeer/Merchant/Villain/Thug/Red Hand/Berserker/… performer & Maneuver/Technique/Reaction availability — all bare hasTrait. Same systemic limit as Uwe journal noted.

**Do NOT pass fiction:** opponent trait gates; Mercenary parley block (`StatesTrait`); When Least Expected Duelist refuse penalty.

User asked for full sweep → see `2026-11-26-03-patricia-querycard-full-sweep.md`.

## Sweep: pass queryCard in _7s5s own-character trait checks (follow-up)
User asked for the mechanical sweep of the footgun above. Passed the ability's owning card (or Character owner / $this for equip host) as hasTrait(..., $queryCard) on OWN performer/actor/ctrl checks: discounts (01159/01160/01171/01176, Reaction_01023 Brute), performer gates (Action 01008/01011/01012/01024 Leader/01025/01026/01028/01030/01062/01068/01071/01072 Leader/01076/01103a/01113/01132/01133/01134/01161/01172/01201), maneuvers (01031/01077/01108a/b/01113/01114/01133), techniques (01010/01011 helper/01063Swap/01067), reactions (01013/01014/01032/01181), equip hosts (_01073/_01074/_01154), _01045 parley discount Leader.
WHY: Patricia's hasTrait only invents traits when queryCard is non-null + same controller.
Deliberately SKIPPED: opponent/adversary checks, uncontrolled Mercenary filters, !Leader exclusions, Action/Maneuver_01087 NOT Mercenary, Reaction_01006 (Don: target loses Brute - Patricia fiction would offer a pointless target), passive auras (_01006 pressure, _01065, _01067 aura grant/strip, _01071 passives) - changing only some sites of a grant/strip pair risks asymmetric add/remove; Action_01024 Thug-in-discard (cards in discard, not ability user). Full suite: 6151 passed.

## Sweep: pass queryCard across bas / cad / framework (user asked)
Did the mechanical sweep from the footgun list above. Pattern: hoist `getOwningCard($theah)` (scheme/equip cards pass ``), pass as 2nd arg to `hasTrait` on OWN performer/participant gates + discounts. First fix per file has the `// WHY queryCard:` breadcrumb. Where a helper had no owner in scope (Reaction_04004 findDuelistAtLocation, Reaction_04025 countControlledMerchants, cad BoyBand/Nevermore/SnowWhite helpers) I added a ?Card param or looked the owner up inside the helper.
Deliberately SKIPPED (not Patricia-relevant or user said no): opponent/target trait checks (04018:87 Sorcerer|Monster, 04027/04008 Leader targets, 04059:34/:99 uncontrolled Mercenary, 04059:55 Hero exclusion, _04024:76 defender Diplomat static en-garde protection), Action_05Vulture Scion (opposing target only), ArgumentsTrait/Theah Sworn Swords Duelist intervene (filters opposing intervenors; owning card belongs to challenger, no clean owner to pass), When Least Expected refuse penalty, Mercenary parley block.
Action_04011 Mercenary passes $hans per user instruction even though targets can be any controlled Mercenary.
php tests/run.php: 6151 pass. No new tests written for Patricia in these cards (only Shakedown has one) � would be the next step if user wants coverage.
