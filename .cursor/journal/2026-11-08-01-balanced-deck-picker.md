# Balanced deck-picker group

## Task
Add a "Balanced" deck-picker-group-button with 10 leader-named buttons (alphabetical), reusing existing leader descriptions/images, new sailsdb deck links. Wire actual starter deck JSON so Confirm works.

## WHY this UI shape
Existing groups (Core/TaC/FaF) are 5 faction buttons offset by `deckPickerGroup = 5*(n-1)`. Balanced has **10 leaders**, not 5 factions — can't reuse the faction offset math for button labels.

Approach:
- Keep faction button row for groups 1–3
- Separate balanced button row (hidden by default), shown only for group 4
- Balanced tabs sit at indices 16–25 (after FaF 11–15); Custom moves 16→26
- `deckPickerGroup = 15` for Balanced so `group+1..+10` maps to tabs 16–25

## Deck source
Pulled BGA exports from `https://sailsdb-backend.onrender.com/api/decks/{uuid}/export/bga` (SPA host returns HTML; backend host has the real API).

IDs: SDBalanced{Leader}, set `"Balanced"`. Random Core/TaC/FaF assignment filters still exclude them. Unfiltered random pool will include them — acceptable unless user says otherwise.

## Descriptions
Reuse existing Core/FaF flavor copy per leader; only the sailsdb href + link label change to the Balanced deck URLs.

## Done
- Templates.js: Balanced group btn, 10 alphabetical leader tabs (16–25), Custom→26, faction vs balanced button rows
- Utilities.js: group 4 toggles rows + offset 15; highlight scoped to active row
- OnUpdateActionButtons.js: 10 `*_description_balanced` strings
- CSS: wrap + tighter padding for balanced buttons
- StarterDecks.php: 10 `SDBalanced*` entries, set `Balanced` (25 decks total)

Tab index math is brittle — if BaS starters get added as group 5 later, Custom and Balanced offsets both move again. Consider data-driven tabs someday.

## Follow-up
User wanted 2 rows of 5, not flex-wrap. Split into two `_7sfs-deck-picker-tab-buttons` children inside the balanced wrapper. Highlight still `querySelectorAll('.deck-picker-button')` on the wrapper so order stays Angeline→Yevgeni.

## Remove TaC picker group
User: remove Tooth & Claw button group + its tab entries. Left StarterDecks TaC JSON alone so random-TaC table option still works.
New indices: Core 1–5, FaF 6–10, Balanced 11–20, Custom 21. Balanced is now group 3 with offset 10.

## Remove TaC+FaF starters and FaF picker
User: drop TaC decks from StarterDecks; drop FaF picker group/tabs AND FaF decks from StarterDecks.
StarterDecks now Core (5) + Balanced (10) only.
Picker: Core + Balanced Leaders. Tabs Core 1–5, Balanced 6–15, Custom 16. Balanced groupIndex=2 offset 5.
Also removed gameoptions 101 values 2/3 (random TaC/FaF), Game.php constants, and State_DeckAssignment TaC/FaF filters — those options would empty-pool crash without the decks.
WHY leave unfiltered random path as Core+Balanced pool: only remaining non-Core set; no Balanced-specific table option yet.

## Random Balanced table option
Added `OPTIONS_PLAYER_DECKS_RANDOM_BALANCED = 2` (reuses vacated TaC option value), gameoptions.json entry, and State_DeckAssignment filter on `set === "Balanced"`. Pool is the 10 SDBalanced* decks — unique assignment still removes chosen decks per player, so max 10p before empty-pool (same pattern as Core's 5).

## Legacy option fallback
Waiting tables may still have option 101=3 (old random FaF) or 2 (old random TaC, now Balanced). Assignment `else` after Core/Balanced filters to Balanced so those games don't empty-pool or mix Core. Kept `OPTIONS_PLAYER_DECKS_LEGACY_RANDOM_FATE_AND_FORTUNE = 3` as a named breadcrumb only — not listed in gameoptions.json.

## Core button rows
User: Core factions as 2 rows of 3+2 (Castille/Eisen/Montaigne, then Ussura/Vodacce). Shared `_7sfs-deck-picker-button-stack` with Balanced; highlight still querySelectorAll on the wrapper.

## Random Balanced same-faction bug
Unique-by-deck-id left both Castille leaders (etc.) assignable. Now remove all decks sharing the chosen faction after each pick; also exclude factions already restored under tournament lock. Core still fine (one deck/faction).

## Group button colors
User: differentiate deck-picker-group-button from deck-picker-button.
WHY slate (#4a6678 / hover+selected #355468, light text) vs parchment (#e8d5b7 / #c9a97a): cool vs warm so Core/Balanced read as section tabs, not same-tier as faction/leader picks. Confirmed selectors beat the shared `button:hover` / `button._7sfs-deck-picker-tab-selected` rules via higher specificity.
Follow-up: selected alone darkened to #1e3340 (hover stays #355468) so active group reads clearer than hover.

## Faction image taller when deck selected
User: grow `_7sfs-deck-picker-faction-image` vertically when a deck-picker-button is selected to show more of the art.
WHY: that class uses fixed `background-size`/`position` as a crop window — element height IS how much portrait you see. Used `:has(active … faction-image)` so only deck tabs change: modal 690→780, blurb max-height 100px + scroll. Banner/custom untouched. Did not retune crop coords — taller viewport already reveals more below the face.
