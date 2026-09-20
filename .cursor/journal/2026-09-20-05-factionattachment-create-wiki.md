# FactionAttachment create wiki pages

## Task
User wants beginner-facing GitHub-push-ready wiki pages on creating a complete FactionAttachment class set. Place in `misc/wiki/create/factionattachment`, push to GitHub wiki. Use create-faction-attachment skill + Character/CityCharacter/Scheme wiki as templates. Explain when to use FactionAttachment.

## Context inherited
2026-09-20-03/04 shipped Character, Leader, CityCharacter, Scheme wiki sets with same folder/filename/`[[wiki link]]` conventions. PUBLISH.md maps local folders → `bga7s5s.wiki` root. Home already linked Character/Leader/Scheme/CityCharacter; old stub `Implementing an Attachment Card` covered both City+Faction in one thin page.

## Plan / WHY
Mirror Character/CityCharacter page set (hub + 12 guides) so beginners see one familiar map across card types. Deliberately **replace Challenge Actions page** with **Equip Restrictions** — that is the FactionAttachment-specific beginner landmine (dual-gate `canAttachTo` + `eventCheck`). Challenges on attachments are rare (Triskelion) and pointed from Actions/Examples instead.

Walking examples:
- Cavalier Hat `_01073` — cleanest equip + City Action
- Tabard `_01075` — equip + passive trait + Action
- Lodestone `_03065` — while-equipped condition + immediate sink Action
- Torres Cloak `_03044` — Reaction cancel-unless-discard

WHY thinner on edge-case essays than the skill: skill companions stay the dense agent reference; wiki stays teachable. Point to exemplars + skill for agents.

## Status
Done. Pushed wiki commit `e01ec16` to `bga7s5s.wiki` master.

## What shipped (local + wiki)
13 pages under `misc/wiki/create/factionattachment/`:
1. `Implementing-A-FactionAttachment-Card.md` — hub, use-case table, walking examples
2. What Is / Card Class / Classify
3. Equip Restrictions (Pattern A — dual gate, opponent-equip, footguns)
4. Passives And Forced (trait grant, B'', Forced destroy)
5. Actions (`AttachmentAction`, no AttachmentCityAction, sink chain)
6. Reactions (ownerIsAttached, cancel, multi-stage)
7. Techniques And Maneuvers (Gambling, remainder-of-duel, sourceId)
8. Wiring / Checklist / Examples / Glossary

Also: PUBLISH.md map; Home.md wiki link; old Attachment stub rewritten as Faction-vs-City router; Character/Leader/Scheme/CityCharacter hubs link FactionAttachment.

## WHY keep old Implementing-An-Attachment-Card
Originally kept as a short router. User later asked to remove the stub link and page entirely — deleted from wiki Home + `Implementing-An-Attachment-Card.md` (follow-up same day).

## Feel
Same altitude as CityCharacter wiki. Biggest beginner landmines called out repeatedly: dual-gate equip, no AttachmentCityAction, sink ≠ discard, while-equipped vs remainder-of-duel clear timing, attachment sourceId on transitions.
