# Censure (_03057) — engage cost vs Night of Drinking

## Bug
Opponent played Censure; Night of Drinking (01109) cancelled effects; performer was
not engaged. Card text: "Engage your performer • Issue an [Influence] challenge…"
01109: "Cancel its effects. (All costs are still paid.)"

## Root cause
Engage was queued inside `EventActionTriggered`. 01109 deletes ActionTriggered on
cancel, so the cost never ran. Same class as Unyielding Loyalty costs living on
RiskReactionTriggered (2026-08-31-02).

## Fix
Move printed engage into `announceAction()` *before* `parent::announceAction()`:
wealth already paid → engage queued → ActionActivated (01109 may cancel) →
ActionTriggered deleted on cancel, but CardEngaged stays.

Applied to sibling Risk City Actions with the same engage-on-Triggered shape:
- `Action_03057` Censure
- `Action_03021` Cornered
- `Action_03034` Diplomat engarde (also removed Triggered "already engaged" throw —
  engage from announce may already have applied before Triggered runs)

Left Scheme `Action_03042` / Character `Action_03030` on Triggered — 01109 does not
cancel those.

## Skill
Updated create-risk Pattern A engage guidance + checklist 25/39 so agents do not
re-teach engage-on-Triggered for Risk Actions.

## Feelings
The Pattern A.5 recipe actively taught the bug. Glad we caught it on Censure before
more FAF Risks copied it blindly — Cornered would have been the same report next.
