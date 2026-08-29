# Amour (_01104) + Lodestone engage bug

## Context from prior sessions
Recent work was Smuggled Item citywide equip (`2026-08-29-01`) and city attachment equip animation (`2026-08-29-02`). Lodestone condition pattern documented in `2026-07-20-02-lodestone-03065.md`.

## Bug report
Eddie used Amour on an opposing character with Lodestone. Lodestone blocks opponent Home moves. Amour text: en garde performer + target opposing character **both engage and go Home**. Opponent **remained en garde** (Engaged=false at city) instead of engaging.

## Root cause
`Action_01104` only engaged via `createCardMovingEvent(..., engage=true)`. Engage runs in `EventCardMoved` handler — if Lodestone throws in `Character::eventCheck` on `EventCardMoving`, `queueEvent` swallows the exception and **no CardMoved fires**, so no Engage.

Lodestone correctly blocked Home; the bug was skipping the separate Engage clause.

## Fix
Split like `Action_03cd01` / `Reaction_02058`:
1. `createCardEngagedEvent` for target + performer (batched)
2. `createCardMovingEvent` with `engage=false` for both Home moves

Result with Lodestone: both Engage, opponent move Home fails with message, performer goes Home. Target is valid — only the move clause is blocked.

## Not done
- Studio retest
- No activate-time Lodestone filter on target list (engage still legal; mirrors not blocking Move Along targets with Lodestone for engage-only paths)
