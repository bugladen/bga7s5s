# Shifting Tides vs Blood in the Water renown order

## Bug
Shifting Tides (_01151) resolve + Blood in the Water (_04cd19) revealed by that resolve: Blood's Forced "add Renown to this location" was surviving the Tides clear. 2p game ended with 3 Renown on the board (Blood + 2 player places) instead of 2.

## Correct rules order
Card text: "Add a City Card to each City location. **Then**, discard all Renown from all locations and add a Renown…"

1. Add city cards (Blood Forced fires → +1 Forums)
2. Discard **all** Renown (Blood's +1 included)
3. Each player places 1 Renown → net 2 in 2p

## Root cause (WHY it was wrong)
Old `_01151` loop per location: queue CityCardAdded, then queue RenownRemoved with `$location->Renown` snapshot **if > 0**.

Two failure modes:
1. Forums at 0 → no remove queued for Forums at all; Blood's later RenownAdded sticks.
2. Forums had Renown → remove queued with **old** amount and processed **before** Blood's cascaded RenownAdded (Blood queues during CityAdd; that add lands later in the queue). Blood's +1 still sticks.

Interleaving add+remove per location also disagreed with the printed "Then".

## Fix
- `_01151`: two passes — all city-card adds (MEDIUM), then `removeAll` clears at LOW_PRIORITY, then player transition at LOWEST_PRIORITY.
- `EventRenownRemovedFromLocation::$removeAll` + EventHub reads **live** Renown at process time (amount unknown when queuing; Blood hasn't fired yet).
- Skip notify when amount resolves to 0.

WHY priorities not FIFO: Blood's Forced RenownAdded is queued mid-CityAdd at default MEDIUM. Clears must be later (LOW); transition must be after clears (LOWEST). Leaving transition at MEDIUM would open player picks before the clear.

## Do not "fix"
- Do not stop Blood from adding on reveal — it should add, then get wiped.
- Do not snapshot remove amount at queue time.

## Status
php -l clean on touched files. Not studio-playtested.
