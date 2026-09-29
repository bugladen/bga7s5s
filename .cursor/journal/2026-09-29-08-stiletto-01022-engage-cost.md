# Stiletto (01022) — Engage is a cost

## Bug
`Reaction_01022::handleEvent` offered the reaction whenever a challenge was
issued at the attachment's location, ignoring whether Stiletto was already
Engaged. Printed text: `engage this card • Wound the challenger or…`.

## Fix
Gate on `$owner->Engaged` before queuing the reaction transition — same pattern
as Reaction_04026 / 04053 / 03019 (engage-this-card left of •).

## WHY (contrast with 03022 Overzealous earlier today)
Overzealous: `Final Strike • En garde target` — Engaged is the *effect*, not a
cost, so it must not gate availability or targets.

Stiletto: Engaged *is* the cost (left of •). If already Engaged you cannot pay,
so the reaction must not offer.

## Left alone
Still uses `!$this->Used` rather than `isAvailable()` — pre-commit wants
isAvailable for AttachmentReaction subclasses, but that wasn't the ask and
Used is what performReaction sets. Don't expand scope.
