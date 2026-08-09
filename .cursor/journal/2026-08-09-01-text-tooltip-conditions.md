# Text tooltips: character conditions?

Eddie asked whether text tooltips include conditions on characters.

**Yes.** `createTextTooltipForCharacter` calls `conditionsRow(card, row)` and pushes that row after Text / before Card #. Shared helper used by all five text-tooltip builders (Character/Scheme/Attachment/Risk/Event). Image tooltips also show conditions via `buildImageTooltipHtml` overlay. `prependCardImageToTextTooltip` deliberately does NOT re-show conditions on the image side — they're already in the table.

Requires `card.conditions` to be populated on the client object; `refreshTooltipForCard` exists specifically so notifications can rebuild after condition mutations.

## Drop cityCardNumber from text hover

Eddie: no longer need to display `card.cityCardNumber` for text hover.

Removed from Character, Attachment, and Event builders (only places that had it). Scheme/Risk never showed it. Also scrubbed the WHY comments that said City Card # stays near Set — that rationale is obsolete now.

## Move Set before Card #

Eddie: for text tooltips, move Set to right before cardNumber.

All five builders now order catalog fields as: … → conditions → Set → Card # (Card # still gated on `!= '0'` for Character/Attachment/Event). WHY: Set is catalog metadata like Card #, not identity — keep it with Card # at the bottom rather than up with Name/Type.


