# Red Tape CharacterTargeted Q

User asked whether Action_05RedTape emits a character targeted event.

Answer: yes. On character choose in 05RedTape state it queues `EventFactory::createCharacterTargetedEvent`. Reveal/sink only runs from handleEvent when that event is not canceled (Terraria/UL/Maryam path). Implemented deliberately that way in 2026-11-20-11-redtape-risk.
