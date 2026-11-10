# Deck picker Core buttons → leader names

## Ask
User: In the deck chooser convert the button names from faction names to the Leader Names.

## Change
Core row in `Templates.js` only — labels Castille/Eisen/Montaigne/Ussura/Vodacce → Soline/Kaspar/Odette/Yevgeni/Constanzo.

## WHY keep faction order
Tab indices and `SDCastille`…`SDVodacce` deck ids still follow faction order. Renaming labels only; remapping order to alphabetical like Balanced would break `deckPickerGroup + N` → tab content wiring. Balanced already used leader names.

`data-deck-name` on tabs stays faction (assignment / uniqueness still faction-based). Description blurbs still lead with faction strong text — user asked for buttons only.

## Alphabetize follow-up
User: alphabetize buttons by leader names.

Core buttons → Constanzo, Kaspar, Odette, Soline, Yevgeni.
WHY also reorder Core tab DOM: highlight uses button index == tabIndex - group - 1. Pointing onclick offsets at old faction-order tabs would desync selection chrome. Reordering content keeps +1..+5 and highlight aligned; deck ids (`SDVodacce` etc.) unchanged so Confirm still resolves correctly.
Balanced was already alpha — no change.
