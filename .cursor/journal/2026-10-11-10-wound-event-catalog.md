# Wound event caller catalog (research)

User asked for every `EventFactory::createCharacterBeingWoundedEvent` caller + other wound APIs, grouped by ability type, with SELF/ANOTHER/BOTH/CONDITIONAL.

## Findings
- **Sole damage API**: `createCharacterBeingWoundedEvent`. Heals are separate (`createCharacterBeingHealedEvent`). Direct `$character->Wounds =` is flip/heal/reset/tiebreak, not damage apply.
- Pipeline: factory → EventHub BeingWounded → CharacterWounded → `Character::$Wounds +=`.
- ~98 caller files under `modules/php` (cards + StatesTrait). No Game.php callers.
- StatesTrait: challenge-reject threat→wounds (challenger + defender) and duel end-of-round leftover threat→wounds on actor.

## WHY categories
SELF = cost on performer/owner/equipped. ANOTHER = target/adversary/opponent/other friendly. BOTH = multi-target including self+other or AoE that can hit both. CONDITIONAL = only when choice/gate met (may still be OTHER-only).

## Note for future
Catalog was research-only; no code changes. If implementing wound-redirect/prevention gates, filter on OTHER/CONDITIONAL ANOTHER first — that's the Kaspar-style surface.
