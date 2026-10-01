# Bastien (01063) — technique aura missing after muster

## Report
Action_01072 mustered Bastien (01063) at Odette's (01062) location. Bastien's
swap Technique aura was not granted to Odette.

## Context
Prior Bastien work (2026-05-12) was about Technique_01063Swap sticking
DUEL_CHALLENGER on rejection — unrelated to aura application.

## Root cause
Bastien's `handleEvent` only grants the aura on:
- `EventCharacterRecruited` (other character recruited at Bastien's location)
- `EventCardMoved` (Bastien moves in/out, or ally moves in/out)

Action_01072 musters via `EventFactory::createCharacterMusteredEvent` →
`EventCharacterMustered`. Hub `moveCard`s the character and updates Location
**before** cards handle the event. Muster does **not** also emit
`EventCardMoved` (same invariant noted in Reaction_03cd10).

So when Bastien arrives by muster onto an occupied city location, neither
existing branch fires:
- Recruited handler requires *other* characterId
- CardMoved "Bastien arrived" never runs

## Fix
Handle `EventCharacterMustered` in `_01063`:
1. Bastien himself mustered to a city location → grant swap technique to all
   controlled allies already there
2. Another controlled character mustered to Bastien's city location → grant to them

WHY hub-before-cards: `$event->location` / `$this->Location` are already post-muster
(unlike EventCardMoved which is `runEventHubAfterCards=true`). Mirrored Rosine
(_01041) muster handling shape.

## Same gap elsewhere
Jean Urbain (_01067) had the identical EventCardMoved/Recruited-only aura.
Applied the same `EventCharacterMustered` grant (Musketeer + Technique_PlusOneRiposte
filter preserved). Done same session at user request.

## Unfinished
None.
