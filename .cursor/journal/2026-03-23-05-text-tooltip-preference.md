# Text Tooltip for Character Cards (Preference 100)

## What was done

Added a conditional text-based tooltip for Character cards, gated on user preference 100 ("Card Hover Style") being set to 2 ("Text"). When active, Character card hovers show a simple HTML text popup instead of the card image.

### Implementation

Added a gate in `createTooltipForCard()` in `Utilities.js` (after the `!controllerId` early return, before the existing image tooltip logic):

```js
if (card.type === 'Character' && this.getGameUserPreference(100) == 2)
```

New method `createTextTooltipForCharacter(card)` builds the HTML from `this.cardProperties` data:
- Name, Type, Title, Resolve, Combat, Finesse, Influence, Traits, Text — always shown
- Actions, Reactions, Techniques — only shown if the card has them; each ability on its own line, struck through with `<s>` if `!available`
- Combat/Finesse/Influence use the dashed vs modified logic (same as `createCharacterCard`)

Uses `this.bga.gameui.addTooltipHtml()` (BGA's native tooltip) instead of `addTippyTooltip()` per Eddie's request. This was deliberate — Eddie specified the BGA native method signature for the text variant.

### Why BGA native instead of Tippy

Eddie explicitly asked for `this.bga.gameui.addTooltipHtml` for the text tooltip. Non-Character cards and image tooltips still use `addTippyTooltip`. The two tooltip systems coexist — the BGA native one will only fire for Character cards when the preference is set to Text.

### Data shape reference

Character card JS properties used: `name`, `type`, `title`, `modifiedResolve`, `modifiedCombat`, `modifiedFinesse`, `modifiedInfluence`, `dashedCombat`, `dashedFinesse`, `dashedInfluence`, `traits` (array), `text` (string with `<p>` wrapped lines from earlier session), `actions`/`reactions`/`techniques` (arrays of `{shortName, available, ...}`).

### Non-Character cards unaffected

The preference gate only triggers for `card.type === 'Character'`. Attachments, Schemes, and uncontrolled cards continue through the existing code paths unchanged.
