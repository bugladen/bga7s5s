# Rename actCityLocationsForReknownSelected → actCityLocationsForRenownSelected

Typo fix only. Framework helper + every caller/possibleaction/JS actionMap/docs/skills.

## Touched

- `FrameworkActionsTrait.php` method
- State wrappers: 01098, 03006, 03017, 03030, 03053, 04015, 04044
- `states.7s5s.php` possibleactions for 01016 + 01071
- `PlayerActions.js` actionMap (all nine planning resolve keys)
- create-scheme skill docs + wiki scheme guides

## WHY leave historical journals alone

Past journal entries still say Reknown — that's what the code was named then. Rewriting history would muddy "what did we call it when we wrote this note."

## Footgun

PowerShell `Set-Content -NoNewline` on Windows ate trailing newlines and corrupted UTF-8 arrows in wiki md. Restored wiki from git then re-applied rename with Python utf-8. Prefer Python for bulk renames here.

## Wiki publish

Pushed the two scheme pages to `bga7s5s.wiki` master as `ac0a66d`. Clone left at `wiki-tmp/` then deleted on request. PowerShell can't do bash HEREDOC for commit -m; used a plain -m string.
