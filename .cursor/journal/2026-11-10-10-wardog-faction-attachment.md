# _05Wardog Smuggled Long Rifle

Finishing CAD FactionAttachment via create-faction-attachment skill.

## Context inherited

Last CAD attachment work was Excalibur (issue-challenge Reaction + free +1 Riposte) and Timebomb/Porcelain Heart. Same prototype CAD rules: plain strings, no clienttranslate.

## Text → shapes

1. **Covert Reaction:** After an enemy moves to an adjacent City location • Wound them. → Pattern D AttachmentReaction (`Reaction_05Wardoga`).
2. **Covert Reaction:** When a pressure occurs at an adjacent City location • Add +1 to your total. → Pattern D AttachmentReaction (`Reaction_05Wardogb`) + Loyal-style PRESSURE_TYPE flag.

No equip restriction, no Action/Technique, no engage cost, no GameState/JS.

## WHY enemy ≠ opposing (no fromLocation)

Pattern D documents Kaiser `_03019` / Horatio with `fromLocation == host location` because printed **"opposing"** requires same-location-before-move. Wardog says **"enemy"** and "moves **to** an adjacent City location" — sniper fantasy is shoot anyone arriving next door, including teleports/recruits into adjacent. Skipping fromLocation is deliberate; do not "fix" by copying Kaiser.

## WHY Covert gate on host location

Same as Seadog / Valeri / Robin: Covert abilities only while the equipped character is at an **uncontrolled City** location (`cardInCity` + `!isControlled()`). Pressure destination being adjacent is separate; Covert does not require the pressure site uncontrolled.

## WHY pressure half = Loyal flag, not aura

It's a **Reaction** (player choice), not a standing aura like Valeri/Orel. Mirror Loyal `_03035` / Meeting of the Minds `_04035`: offer on `EventPressureOccuring`, on accept `setGlobalFlag(WARDOG_PRESSURE_TYPE)` + store player id; `pressureLocation` adds +1 later. New bit `2097152` after OREL_WIN_TIES. Clear `WARDOG_PLAYER_ID` in stNextPlayer with the other pressure player ids.

No "you must be the pressuring player" gate — text is "when a pressure occurs … add +1 to **your** total" (defender or attacker from afar).

## Refs

- Move: Kaiser bones without engage/fromLocation; Seadog Covert gate; Excalibur wound queue (attachment sourceId).
- Pressure: Reaction_04035 / Loyal flag path; Vantage adjacent pressure offer shape for adjacency only.

## Done

- `_05Wardog` + `Reaction_05Wardoga` / `Reaction_05Wardogb`
- `Game::WARDOG_PRESSURE_TYPE` (2097152) + `WARDOG_PLAYER_ID`; FakeGame mirror; UtilitiesTrait +1; stNextPlayer clear
- `Contraband` added to TraitNames
- Tests: `Card_05Wardog_Test` (15), UtilitiesTrait_Pressure WARDOG case, StatesTrait clear list
- Skill docs updated (see `2026-11-10-11-faction-attachment-skill-wardog.md`)

All related suites green.
