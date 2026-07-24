# _04cd15 Syrneth Puzzle Box (bas City Attachment)

## Card Text
- Equipped character gains **Sorcerer**.
- **City Action:** Engage this card • Look at the top three cards of your deck. Sink any and replace the rest in any order. Then, you may discard a card to draw a card.

## Classification
1. Passive grant Sorcerer → Pattern B (equip/unequip pair) — mirror `_02047` Temnota / `_01198`.
2. City Action → Pattern C AttachmentAction — engage attachment + multi-step deck look.

## Plan / WHY
Mirror High Drama deck-look chain from `Action_01134` / `Action_02002` + Academic "sink any" Pass from `Maneuver_03059`, then optional hand discard→draw like `01134_4` but discard instead of engage.

States:
1. `04cd15` — sink any (multi-select + Pass = sink none)
2. `04cd15_2` — reorder remaining (skip if 0/1 via finishReplaceOrReorder helper)
3. `04cd15_3` — may discard 1 hand card to draw, or Pass

Engage paid on `EventActionTriggered` (action already confirmed centrally). Deck is controller's faction deck. Sink = immediate bottom insert (03059 WHY: queued sink races reorder top-inserts).

Availability: attachment unengaged + owner in city (City Action). Empty deck: look yields 0 cards → skip sink/reorder → optional discard-to-draw still offered via transition `04cd15_3`.

## Implemented
- `_04cd15.php` — IHasActions + ActionTrait + Sorcerer equip/unequip
- `Action_04cd15.php` — full multi-step flow + `createActionResolvedEvent` on discard and pass of final step
- States `04cd15` / `_2` / `_3` (4040015 / 40400152 / 40400153)
- `states.inc.php` transitions `04cd15`, `04cd15_3` (empty-deck skip)
- bas JS + EventHandlers for chooseList sink/reorder and hand discard

## Alternatives considered
- Private args for looked-at cards (03052 style): High Drama peers (01134/02002) use public args; stayed consistent.
- Pay engage on final commit: no picker before look, and in-play confirm already committed the action — engage on trigger is correct.
- Skip `04cd15_3` when hand empty: still show Pass-only state so actionResolved always has a clear terminal step (zombie auto-passes).

## Follow-up
Eddie asked for a log when declining discard-to-draw. Added notify on Pass of `04cd15_3` — mirrors the discard-to-draw success log so spectators see the choice either way.

## Skill update (same day)
Folded `_04cd15` lessons into `create-city-attachment`:
- Shape table: Sorcerer CityAttachment + deck-look City Action pointer
- Pattern B: `_04cd15` as CityAttachment Sorcerer exemplar beside Temnota
- Pattern C: engage-on-trigger (no picker) vs engage-on-location-commit; full look/sink-any/reorder/discard-to-draw section (immediate bottom-insert race WHY, empty-look skip transition, decline notify, JS/EventHandlers)
- helpers / checklist / references: matching gotchas

WHY document in the skill now: next agent will otherwise "fix" immediate sink to queued faction-deck events and reintroduce the reorder race, or skip decline logging / empty-deck skip registration.

## Status
php -l clean. Not playtested on BGA Studio. Skill updated.

## Gotchas for next agent
- Sink uses **immediate** `insertCardOnExtremePosition(..., false)` — do not switch to queued `createCardAddedToFactionDeckEvent` without fixing the reorder race.
- Reorder JS uses `onCardsSorted` (descending order tags) — last selected ends on top.
- Do not call `setUsed` / `announceAction` / `resetPlayerPassCount` from AttachmentAction subclasses.
