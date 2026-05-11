## TraitNames JSON fix

`TraitNames::$TraitsJson` had a broken heredoc: opening `{ "traits": [` then numbered lines (`2. Academic`, …) instead of JSON strings.

**Fix:** Parsed those lines with regex `^\s*\d+\.\s+(.+)$`, built `["traits" => [...]]`, used `json_encode(..., JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)` so unicode (Fusō, Porté, Tură's Touch, bullet in compound traits) stays readable.

**WHY heredoc kept:** Consumer likely expects the same shape `{"traits":[...]}`. No usages in repo yet—grep only hits `Traits.php`.

**Line endings:** Applied replacement via one-off script using `preg_replace` on `<<<JSON ... JSON;` so original CRLF in `Traits.php` stayed intact.

**Deliverable:** `_results/traits_names.json` — same JSON as embedded in PHP for tooling/copy-paste.

205 trait strings (original list numbered 2–206).
