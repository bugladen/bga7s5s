# CityCharacter create wiki pages

## Task
User wants beginner-facing GitHub-push-ready wiki pages on creating a complete CityCharacter class set. Place in `misc/wiki/create/citycharacter`, push to GitHub wiki. Use create-city-character skill + Leader/Character wiki as templates. Explain when to use CityCharacter.

## Context inherited
2026-09-20-02 shipped Leader wiki with same folder/filename/`[[wiki link]]` conventions. PUBLISH.md maps local folders → `bga7s5s.wiki` root. Home.md already links Character/Leader/Scheme — need Home update for CityCharacter entry point.

## Plan / WHY
Mirror Character/Leader page set (hub + 12 guides) so beginners see one familiar map across card types. Walking example = Penya `_03cd01` (skill canonical: Negotiable-capable skeleton + hard ban + City Forced + multi-step City Action). Julius `_03cd10` for Reactions. Deliberately thinner on edge-case essays than the skill — point to examples + skill for agents.

CityCharacter-specific teaching beats (not in Character wiki):
- Use-case: lives in city deck, mustered with WealthCost, optional Negotiable
- `CardNumber = 0`, `CityCardNumber = N`
- No `initializeFaction`
- Hard ban = `canIntervene`/`canChallenge` + `eventCheck`
- City Forced gated `cardInCity`
- Action base = `CharacterAction` NOT `EventCityAction`
- New states: class + States.php + states.inc.php only (skill says skip states.7s5s.php)

## Status
Done. Pushed wiki commit `973c1ef` to `bga7s5s.wiki` master.

## What shipped (local + wiki)
13 pages under `misc/wiki/create/citycharacter/`:
1. `Implementing-A-CityCharacter-Card.md` — hub, use-case table, Penya walking example
2. What Is / Card Class / Classify
3. Passives (hard ban + City Forced + ordinary passives)
4. Actions (`CharacterAction` not `EventCityAction`, engage-as-cost)
5. Reactions (Julius, toLocation gotcha, no JS)
6. Techniques / Challenges (thin; point at Character siblings)
7. Wiring (Penya states; **skip states.7s5s.php** — skill rule)
8. Checklist / Examples / Glossary

Also: PUBLISH.md map + Home.md wiki link; Character/Leader hubs now link to CityCharacter guide instead of "different skill" dead end.

## WHY Penya as walking example
Skill already treats Penya as the all-patterns CityCharacter. Real complete files. Same reason Cesca was used for Leaders.

## WHY thinner Challenges/Techniques pages
Almost no CityCharacter-specific challenge/technique lore in the skill (Pattern E is one paragraph). Duplicating the full Character challenge essay would rot. Point sideways.

## Feel
Same altitude as Leader wiki — teach the map. Biggest beginner landmine called out repeatedly: EventCityAction vs CharacterAction, and CardNumber=0 / CityCardNumber.

## Pronoun fix
User: Penya is a boy. Fixed she/her → he/him/himself in hub, classify, and actions pages. Pushed wiki `aab416f`.
