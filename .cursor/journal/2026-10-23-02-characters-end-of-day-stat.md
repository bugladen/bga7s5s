# Characters-at-end-of-day written when the game ends

User saw Day 3 end with "Characters at end of Day 3" still 0. That stat was only written in stDuskEndOfDay. Dominance, economic, and fifth-day victories return from Plunder, and assassination can fire any time. recordEndOfGameStats stored the day, the victory type, and Renown, and left the character stat at its setup default of 0.

## What I changed

recordEndOfGameStats now calls recordCharactersAtEndOfDayStat first. Both goToEndOfGame and the assassination EventTransition already go through recordEndOfGameStats, so every ending writes the current day's count.

## Why the same-day guard

Dusk still snapshots before Brute discards are queued. If a Leader dies while those events resolve, game end runs on the same day, after Brutes have started leaving. Rewriting the stat there would replace the Dusk count.

globals CHARACTERS_END_OF_DAY_RECORDED stores the day number already snapshotted. A second call on that day returns immediately. The next day (Dawn increments DAY) is a different number, so it still writes. Do not remove the guard to "always count the board at the exact end" — the printed stat is the end-of-day crew, and Dusk is that moment when the day actually finishes.

Days that never reach Dusk get the board as it stands when victory is declared. That includes fifth day, which also ends in Plunder, so characters_day_5 was 0 on a normal five-day game too.
