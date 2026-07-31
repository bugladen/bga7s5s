# _01050 Weapon self-destruct Q

Eddie asked whether Unsavory Salve removes itself when the attached character no longer has a Weapon-trait attachment.

Answer: yes. Existing audit (2026-03-30-02) already covered this; code unchanged in spirit. `handleEvent` on `EventAttachmentUnequipped` + `hasWeaponEquipped()` (trait check) → unequip + discard.

No code changes this session.
