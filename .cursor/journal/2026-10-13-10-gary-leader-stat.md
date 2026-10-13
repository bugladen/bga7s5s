# Rena (_05Gary) leader stat label

User asked to add 05Gary to the leader stats.

`Game::LEADER_STAT_LABELS` already had index 13 => `Rena Klingenhalter` (landed in the 05Gary commit 58eddc9e) but `stats.json` value_labels for id 12 stopped at Don Vissenta. Appended so the two stay in lockstep — BGA displays via stats.json; DeckTrait `array_search` uses the PHP const.

Name matches `$card->Name` exactly (CAD, no clienttranslate). Never reorder live labels.
