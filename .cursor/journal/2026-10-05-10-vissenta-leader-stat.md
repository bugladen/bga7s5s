# Don Vissenta Scarpa leader stat label

User asked to add Vissenta (Leader) to the leader stat — same pattern as Valeri yesterday (2026-10-04-01).

CAD `_05Cooper` Name is `"Don Vissenta Scarpa"` (not bare Vissenta). Appended as index 12 to `Game::LEADER_STAT_LABELS` + stats.json id 12. Never reorder live labels.

Not base `_01013` — that's Character `"Vissenta Scarpa"`, not a Leader. DeckTrait array_search is strict on `$card->Name`.
