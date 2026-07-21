# Create-City-Character Skill

Authored `.claude/skills/create-city-character/SKILL.md` as the third sibling skill alongside `create-city-event-card` and `create-city-attachment`. The motivation: city characters are the third leaf of the city-deck class tree (`CityEventCard`, `CityAttachment`, `CityCharacter`) and they combine Character semantics (stats, wounds, attachments, techniques, can be mustered into play) with city-deck plumbing (can be shuffled back in, `CityCardNumber`, `WealthCost`). The other two skills cover their leaves cleanly but a CityCharacter genuinely needs its own page — the Negotiable / dashed-stat / `canIntervene`+`eventCheck`/Character-not-Event-Action distinctions don't appear in either sibling.

## Source material

Only one canonical implementation exists today: Penya `_03cd01`, journaled at `2026-04-26-01-penya-03cd01-implementation.md`. I used:

- The journal entry as the WHY backbone — duel vs challenge, "would be wounded" vs "wounded," event-ordering trick, `getCardsOnTopOfCityDeck` returning raw rows, the duel-mid-trigger edge case.
- `_03cd01.php` itself for the exact constructor field set and the `canIntervene` + `eventCheck` pair.
- `Action_03cd01.php` for the `CharacterAction` two-step flow, `CHOSEN_TARGET` global, and engage-as-cost / move-with-engage-false.
- Both state files for the `403XXXX` ID convention and the `<` back-button pattern in step 2.
- The two faf JS files for the canonical UI/buttons.
- `CityCharacter.php` / `Character.php` / `Theah::interventionCheck` to verify what the base class actually provides (just `Negotiable` over `Character`).

## Patterns lifted (and WHY they go in the skill)

- **`CharacterAction`, NOT `EventCityAction`.** This is the single biggest landmine. A future me reading "city character with a city action" might pick `EventCityAction` by analogy with `_03cd03`. But `EventCityAction` discards the card after use and Penya doesn't get discarded — she stays in the city deck. The skill calls this out explicitly with a short "resist the analogy" paragraph.
- **The `canIntervene` + `eventCheck` belt-and-suspenders pair.** Penya overrides both. The predicate filters the UI; `eventCheck` filters the engine. Without explaining that distinction I'd later wonder why the `eventCheck` was "redundant" and try to delete it.
- **Event-ordering inside `handleEvent` (self-listening).** Lifted from the Penya journal verbatim — queue `CardRemovedFromPlay`, then have another branch of the same `handleEvent` listen for `EventCardRemovedFromPlay(this.Id)` to shuffle. This is a non-obvious move and exactly the kind of thing a future session would "simplify" away.
- **`getCardsOnTopOfCityDeck` returns raw rows, cast id to int.** Direct port from the Penya open-questions section.
- **Why `createCardRemovedFromPlayEvent` over `createCardAddedToCityDeckEvent`.** Animation/notification difference. The skill spells it out so a future agent doesn't "fix" it backwards.
- **Engage as cost, move with engage=false.** Subtle and easy to invert. Spelled out with the Penya snippet.
- **`403XXXX` state-ID convention.** Same as the sibling skills, but stated again locally so the agent doesn't have to cross-skill to find it.
- **Dashed stats** — set numeric stat to `0` AND `Dashed<Stat> = true`. Easy to miss the dual setting.
- **Negotiable** — single boolean. Trivial but I want it explicitly in the "pick the right pattern" table so it isn't forgotten.

## Scoping decisions

- **Project-local under `.claude/skills/`.** Same as the siblings. Tightly coupled to this repo's layout (`States/faf/`, `_03cdNN.php` filename pattern, `OnEnteringState.faf.js`).
- **Single `SKILL.md`, no assets.** All three city-deck skills follow this. Anything heavier would be premature when there's only one CityCharacter in the codebase.
- **Pushy description** that names the file pattern (`_03cdNN.php` extending `CityCharacter`) and lists natural-language triggers ("the city character musters and …", "wire up the City Forced"). Cross-references the other two skills so the agent picks the right one when the stub extends `CityEventCard` or `CityAttachment` instead.
- **Reference Implementations table is short** because there's only Penya. I leaned on `_01186` (Maryam) as a comparison for the "would be wounded + cancel" pattern even though it's a regular Character, not a CityCharacter. Better one comparable card than a padded list of irrelevant ones.

## Open questions / followups

- **The duel-mid-trigger edge case is unresolved in the code.** The skill flags it but doesn't pretend there's a fix. When the second CityCharacter with a similar trigger lands, this needs to become real.
- **No Reaction-bearing CityCharacter exists yet.** Pattern D is sketched from general Character/Reaction knowledge, not from a worked example. If a faf CityCharacter ships with a City Reaction, I'll need to verify the `cardInCity` gating actually plays nice with `CardReaction::isAvailable`.
- **No Technique-bearing CityCharacter exists yet.** Same caveat.
- **Single-source bias.** Almost every WHY in this skill traces back to a single card. If a second CityCharacter implementation reveals one of the patterns was Penya-specific rather than CityCharacter-general, this skill will mislead. The mitigation: the skill repeatedly says "mirror Penya rather than invent" when in doubt — which is honest about the evidence base.

## Feels

This was the easiest of the three city-deck skills to write because the Penya journal is unusually thorough. The sibling skills had to synthesize across multiple journals; this one is essentially a re-organization of one journal's content into a "next time, do it this way" frame. The risk is that I baked Penya-specific oddities (the duel-mid-trigger gap, the specific notify pattern) into something that reads as general guidance. I tried to keep those flagged as "open question" or "Penya's choice" rather than "the way." Future me, read with that grain of salt.
