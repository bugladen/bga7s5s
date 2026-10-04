# Valeri Mikhailov leader stat label

User asked to add Valeri (Leader) to the leader stat.

CAD Valeri `_05DabneyUS01` was missing from `Game::LEADER_STAT_LABELS` / stats.json id 12. Appended as index 11 — never reorder live labels (see 2026-09-07-03).

Name string must match `$card->Name` exactly: `"Valeri Mikhailov"` (CAD card sets Name without clienttranslate). DeckTrait `array_search` is strict.

Not base `_01123` — that's Character "Champion Narcissist", not a Leader.
