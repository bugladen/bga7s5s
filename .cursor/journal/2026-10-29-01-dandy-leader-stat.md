# Claude (_05Dandy) leader stat label

User asked to add 05Dandy to the leader stats.

Appended index 14 => `Claude de la Roche` to both `Game::LEADER_STAT_LABELS` and `stats.json` value_labels for id 12. Same pattern as Gary/Valeri — keep the two in lockstep; never reorder live labels.

Name matches `$card->Name` exactly (CAD, no clienttranslate). DeckTrait `array_search` uses the PHP const; BGA displays via stats.json.
