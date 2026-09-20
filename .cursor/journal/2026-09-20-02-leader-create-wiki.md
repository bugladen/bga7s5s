# Leader create wiki pages

## Task
User asked for beginner-facing wiki pages on how to create a complete Leader class set, using the create-character skill as reference, placed in `misc/wiki/create/leader`.

## What I wrote
13 markdown pages:

1. README — index, golden rules, Cesca as walking example
2. 01-what-is-a-leader — Leader vs Character vs CityCharacter, file layout, state machine one-liner
3. 02-card-class — constructor skeleton, CrewCap/Panache/Leader trait
4. 03-classify-text — pattern picker + word traps (City, target, opposing, En Garde, Sorcerer)
5. 04-passives — handleEvent gates, Cesca dawn draw, Resolve special case
6. 05-actions — CharacterAction skeleton, City gate, no setUsed, string Id trap
7. 06-reactions — button reactions (no JS), Cesca sorcerer trigger, Continuous, En Garde
8. 07-techniques-maneuvers — duel gates, Gambling both globals, subclass PlusOne*
9. 08-challenges — engagement trichotomy, when to mint CHALLENGE_TYPE, file touch list
10. 09-wiring — States.php + states.inc + three JS files, location/hand/chooseList traps
11. 10-checklist — beginner checkbox finish list
12. 11-examples — mirror table by need
13. 12-glossary-helpers — jargon + Theah helpers

## WHY this shape (not one giant page)
Skill files are agent-oriented progressive disclosure with huge edge-case tables. Humans starting out need a linear path + jump links. I deliberately **omitted** most checklist-item edge cases (discard-salvage, mid-duel Hand deferral, etc.) and pointed to example Leaders instead — beginners drown if you paste the whole skill. The examples page is the escape hatch when text matches a weird case.

## WHY Cesca as walking example
Skill already treats Cesca as the canonical "all three patterns" Leader (passive + Reaction + multi-step Action). Real files exist and are complete. Avoids inventing a fictional teaching card.

## Intentionally not covered in depth
- Full Pattern A location-aura timing essays (`runEventHubAfterCards`) — mentioned lightly; mirror Angeline/Axelle
- Every challenge integration micro-detail — table of files + trichotomy only
- CityCharacter / Risk / Attachment skills — out of scope; README points away

## Unfinished / follow-ups if user wants
- Sibling wikis for Character (non-Leader), CityCharacter, Risk, FactionAttachment using the same folder pattern
- Optional: link from CLAUDE.md or misc/README to this wiki
- Screenshots of BGA UI for picker states (not requested)

## Feel
This feels like the right altitude — teach the map, not every trail. Risk is that a beginner hits a weird Leader (Giacinto reveal-hand, Tomas salvage) and the wiki says "mirror X" without enough local detail. That's acceptable; the skill remains the deep reference for agents, wiki for humans.

## GitHub wiki publish
Pushed to https://github.com/bugladen/bga7s5s/wiki/Implementing-A-Leader-Card
- Replaced short Soline stub Implementing-A-Leader-Card.md with full hub
- Added 12 Leader-Guide-*.md pages with [[wiki links]]
- Commit 9eded03 on bga7s5s.wiki master
WHY replace stub instead of new top-level page: Home.md already links [[Implementing a Leader Card]] � keep that entry point so existing wiki nav still works.
