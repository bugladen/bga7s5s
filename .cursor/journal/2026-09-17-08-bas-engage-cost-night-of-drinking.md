# bas Risk engage costs vs Night of Drinking

## Context
User on `bas` after merging main (Censure announceAction engage fix). Asked to
ensure the same 01109-safe pattern for `modules/php/cards/bas`.

## Audit

Risk/RiskCityAction with printed Engage cost:

| Card | Action | Engage target known at announce? | Was | Fix |
|------|--------|----------------------------------|-----|-----|
| Rapsodia `_04039` | RiskCityAction | Yes (CHOSEN_PERFORMER) | Engage on location confirm | `announceAction` |
| No More Words `_04019` | RiskAction | No (attachment chooser after Triggered) | Engage on attachment confirm | **Not moved** — see below |

Not in scope (01109 does not cancel): CharacterAction (Raven/Danilo auto-engage via
stIssueChallenge), AttachmentAction (`_04036` / `_04cd01` / `_04cd15`), EventCityAction
(`_04cd09`), SchemeCityAction.

Other bas Risks with costs before bullet (e.g. `_04037` discard City Card) also pick
the cost target after Triggered — same chooser-after-cancel gap as 04019; not engage.

## Fix applied
`Action_04039`: engage in `announceAction` before parent; confirm only does
lose-control + claim. Removed Engaged throw on confirm (engage may already apply).

WHY reverse the old "delay engage until confirm to avoid zombie" comment: 01109
explicitly requires the zombie ("All costs are still paid"). Lose-control still
cannot run without a location; cancel after announce pays engage only.

## Deferred: Action_04019
Attachment is chosen in `HIGH_DRAMA_PLAYER_TURN_04019` after ActionTriggered.
01109 deletes Triggered → attachment never engaged. Full fix needs attachment
selection before `announceAction` (pre-pay state or stash on performer confirm).
Not done this pass — larger UX/framework change than Censure/Rapsodia.

## Feelings
bas only had one Censure-shaped Risk. 04019 is the real remaining hole if Eddie
hits No More Words + Night of Drinking.
