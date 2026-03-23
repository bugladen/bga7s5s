# Murder (_02009) Audit

## Context

Seventh audit in the series. Eddie asked to audit _02009 (Murder) against its card text. A Vodacce Risk with three maneuvers, all doing the same thing (wound adversary) but gated by different traits (Thug, Duelist, Spy).

## Bug Found and Fixed

**Single maneuver instead of three separate ones**

The original code had one `Maneuver_02009` instance with an OR check for all three traits. The card text defines three distinct maneuvers. This matters because each maneuver tracks its own "used" state independently — a character with multiple qualifying traits (e.g. Thug+Duelist) should be able to use the wound effect multiple times across duel rounds, once per qualifying maneuver.

WHY this is a real bug and not just pedantry: Maneuvers in this engine have per-instance used tracking (`CardAbilityTrait::setUsed`). They reset at duel end, not per round. With one combined maneuver, multi-trait characters get one use per duel. With three, they get up to three. The card text clearly intends three separate abilities.

## Implementation Choice

Rather than creating three nearly-identical classes (Maneuver_02009a/b/c), I parameterized the existing `Maneuver_02009` with a `$requiredTrait` constructor arg and instantiated it three times with distinct IDs via `setId()`. This follows the `Maneuver_PlusOneParry` / `_01155` pattern. The unique IDs (`Maneuver_02009_Thug`, etc.) ensure `setOwnerId` creates distinct final IDs so used-state tracking works correctly.

WHY not separate classes: The `handleEvent` logic is identical for all three — wound the adversary for 1. Only the trait gate differs. Three classes would mean maintaining three identical `handleEvent` methods. The parameterized approach keeps the logic in one place.

## Pattern Note

This is the first card I've seen where multiple maneuvers share the same effect but differ only by required trait. If more cards follow this pattern, the parameterized approach scales well. Future auditors: if you see `Maneuver_02009` being constructed without args, that's wrong — it now requires `(string $requiredTrait, string $name)`.
