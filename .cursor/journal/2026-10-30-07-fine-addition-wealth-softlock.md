# A Fine Addition (_04029) empty attachment chooser soft-lock

## Report
Action used; opposing character had 3 attachments; only 1 card left in hand after play; no attachment buttons; game stuck.

## Root cause
`getWealthAdjustment()` always returned `-(selfWealth + WealthCost)` (−1 for this 0-cost non-Wealth Risk). That reserve is correct **while the Risk is still in hand** (availability grey): hand includes the Risk, subtract so equip affordability uses post-play wealth.

Chooser/`act` kept applying the same reserve **after** the Risk left hand. Real hand wealth was already without the Risk; subtracting again undercounted by 1. With one payment card left and attachments costing ≥1, every target filtered out → empty `args.attachments` → no buttons → soft-lock.

Robbery (`Action_01113`) only adjusts in `isAvailableToPlayer` and calls discard-pile helpers with default 0 in args/act. B.9 skill docs wrongly said "apply on every stealability call" — that recipe caused this.

## Fix
1. `getWealthAdjustment`: return `null` when `$owner->Location != LOCATION_HAND`.
2. Pass escape when chooser empty (`actFromActionPass` + `pass` transition + JS Pass when `attachments.length === 0`); refuse Pass when options remain. Zombie uses Pass when empty.
3. Tests: post-play chooser with `handWealthOverride` 1 still lists cost-1 attachment; Pass empty/refuse. FakeGame gained optional `handWealthOverride` (default still 99).
4. Corrected create-risk B.9 docs (helpers / pattern-b / checklist / references / SKILL).

## Live table
Stuck table after deploy: refresh should show attachment buttons if payment wealth covers cost; else Pass unsticks.
