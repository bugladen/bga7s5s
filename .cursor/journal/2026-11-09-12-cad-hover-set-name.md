# CAD hover Set display name

Eddie: card hover "Set" row was showing raw `cad` because `getSetDisplayName` in `Utilities.js` had Core/tac/faf/bas but not cad.

Added `'cad': _('Cloak & Dagger')`. WHY ampersand not "and": Eddie asked for that exact string; other sets in the map use "and" (Tooth and Claw, etc.) — keep CAD as specified, don't normalize.

Fallback was `?? expansionName`, which is why players saw the folder code. No PHP change / no regression run.
