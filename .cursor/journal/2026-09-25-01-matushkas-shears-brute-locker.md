# Matushka's Shears (03007) — fired when Thug went to discard

## Bug
Shears offered its Sorcerer City Reaction when an opposing Red Hand Thug was destroyed. Card text: "When an opposing character is sent to **The Locker**." Thug went to discard, not locker.

## Cause
`Reaction_03007::handleEvent` listened to `EventCharacterDestroyed` with no filter. That event fires for every destroyed character. EventHub then routes:
- `instanceof Brute` → discard
- else → locker

Red Hand Thugs (Alcee/Angelo/Buratino/Dante) are the only printed Thugs — all are Brute-class. User framed it as "Thug death → discard"; mechanically discard is the **Brute** keyword rule ("when destroyed they enter the discard pile instead of The Locker"), not a Thug rule. All current Thugs discard only because they are Brutes.

Destroy→locker deliberately does **not** fire `EventCardSentToLocker`. So `EventCharacterDestroyed` is the right trigger for destroy→locker — but only for the locker branch.

## Fix
Skip `instanceof Brute` in the destroy handler (matches EventHub). WHY not `hasTrait("Thug")`: Thug isn't what routes to discard. WHY not `hasTrait("Brute")`: Cirilo-granted trait-only Brute still goes to locker per Agent006 — Shears should still fire.

## Not done
Did not also wire `EventCardSentToLocker` for non-destroy character→locker. Revisit if a real opposing case shows up.
