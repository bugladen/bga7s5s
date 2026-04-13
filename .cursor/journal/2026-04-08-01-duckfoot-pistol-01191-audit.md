# Duckfoot Pistol (01191) Audit

## Card Text
> Action: Destroy this card • Wound all non-Leader characters at this location.

## Verdict: No Bugs

### What's correct
- **Destroy this card**: Unequip event + discard to city discard pile. `$asEffect = false` because destruction is the cost (before the bullet). `setUsed()` correctly skipped since the card ceases to exist.
- **Wound all non-Leader characters**: `getCharactersAtLocation($location)` → `array_filter` keeping `!hasTrait("Leader")` → wound event (1 wound each) with pistol as source. Trait string `"Leader"` matches codebase convention.
- **Event ordering**: Unequip → Discard → Wounds → ActionResolved. Cost before effect.
- **Action availability**: Inherits from `AttachmentAction` — just checks owning character exists. No extra preconditions needed; card text imposes none.
- **Notification**: Announces the action to all players with card name and location.

### Considered: includeUncontrolled flag
`getCharactersAtLocation($location)` is called without `includeUncontrolled = true`. Checked the codebase pattern — the vast majority of card effect calls omit it. Only Cirilo (01009, targeting uncontrolled Mercenaries specifically) and framework-level challenge/intervention code pass `true`. The convention is that card effects target controlled characters unless specifically designed to interact with uncontrolled adversaries. Left as-is.

### Only change: removed misleading comment
Line 37 had `// Filter out characters are not leaders` which was grammatically broken and semantically backwards (the code keeps non-Leaders, not filters them out). The `array_filter` with `!hasTrait("Leader")` is self-explanatory. Removed per coding standards.

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01191.php` — removed misleading comment
