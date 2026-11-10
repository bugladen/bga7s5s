# Deck picker group colors

## Ask
User: two group buttons pirate-themed but different colors; each group's child buttons match their group tab.

## WHY these two hues
Earlier both group tabs shared one cool slate vs parchment children — that separated tier, not Core vs Balanced. Need two pirate colors that still contrast hard:

- **Core mahogany** `#703535` / hover `#5a2a2a` / selected `#421f1f` — coat / wine / blood-on-wood warm
- **Balanced sea** `#2f5c5a` / hover `#244846` / selected `#1a3534` — deep ocean cool

Warm vs cool is the tell, not saturation. Light parchment text `#f0ebe3` on both. Custom + Confirm stay default parchment (no group class).

## Wiring
`_7sfs-deck-picker-group-core` / `_7sfs-deck-picker-group-balanced` on each group button AND on the matching button-stack wrapper so `.deck-picker-button` descendants inherit the same fill without duplicating classes on every faction/leader button.

Specificity must beat shared `button:hover` / `button._7sfs-deck-picker-tab-selected` parchment rules — same pattern as the old single-slate group styles.

## Not changed
Select confirm-ready green / disabled gray still apply only to bottom-row buttons outside the stacks.
