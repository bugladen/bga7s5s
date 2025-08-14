# Zombie Turn Implementation Plan for 7th Sea: City of Five Sails

## Overview
This document outlines the implementation plan for handling zombie turns in the `doZombieTurn()` method of `ZombieTrait.php`. When a player drops out of the game, this method is called to prevent the game from hanging by automatically progressing the game state.

## State Types Analysis

### 1. Game States (type: "game")
- **No zombie handling needed** - These states run automatically and don't wait for player input
- Examples: `gameSetup`, `buildTable`, `dawnNewDay`, etc.

### 2. Active Player States (type: "activeplayer")
- **Require zombie handling** - Single player must take action
- Zombie should take the most reasonable default action or pass

### 3. Multiple Active Player States (type: "multipleactiveplayer")
- **Require zombie handling** - Multiple players must take action
- Zombie should mark themselves as non-multiactive with appropriate transition

## Implementation Strategy

### Active Player States - Default Actions

#### Planning Phase States
```php
case "planningPhaseResolveSchemes_01016":
case "planningPhaseResolveSchemes_01016_2":
case "planningPhaseResolveSchemes_01016_3":
case "planningPhaseResolveSchemes_01045":
case "planningPhaseResolveSchemes_01098":
case "planningPhaseResolveSchemes_01125":
case "planningPhaseResolveSchemes_01125_2":
case "planningPhaseResolveSchemes_01125_3":
case "planningPhaseResolveSchemes_01125_4":
case "planningPhaseResolveSchemes_01126":
case "planningPhaseResolveSchemes_01143":
case "planningPhaseResolveSchemes_01144":
case "planningPhaseResolveSchemes_01144_2":
case "planningPhaseResolveSchemes_01145":
case "planningPhaseResolveSchemes_01152":
case "planningPhaseResolveSchemes_01152_2":
case "planningPhaseResolveSchemes_01152_3":
case "planningPhaseEnd_01098":
case "planningPhaseEnd_01098_2":
    // Default action: Pass or take first available option
    $this->actPass(); // or appropriate default action
    break;
```

#### High Drama Player Turn States
```php
case "highDramaPlayerTurn":
    // Default action: Pass turn
    $this->actHighDramaPass();
    break;

case "highDramaChallengeActionChoosePerformer":
case "highDramaChallengeActionChooseTarget":
case "highDramaChallengeActionActivateTechnique":
case "highDramaChallengeActionAcceptChallenge":
    // Default action: Go back or reject
    $this->actBack(); // or appropriate default
    break;

case "highDramaClaimActionChoosePerformer":
case "highDramaEquipActionChoosePerformer":
case "highDramaEquipActionChooseAttachmentLocation":
case "highDramaEquipActionChooseAttachmentFromHand":
case "highDramaEquipActionPayForAttachmentFromHand":
case "highDramaEquipActionChooseAttachmentFromPlay":
case "highDramaEquipActionPayForAttachmentFromPlay":
case "highDramaMoveActionChoosePerformer":
case "highDramaMoveActionChooseLocation":
case "highDramaRecruitActionChoosePerformer":
case "highDramaRecruitActionParley":
case "highDramaRecruitActionChooseMercenary":
case "highDramaRecruitActionPayForMercenary":
case "highDramaInPlayActionChooseAction":
case "highDramaInPlayActionChoosePerformer":
case "highDramaInHandActionChooseAction":
case "highDramaInHandActionChoosePerformer":
case "highDramaInHandActionPay":
    // Default action: Go back to main turn
    $this->actBack();
    break;
```

#### High Drama Player Turn Event States (Card-Specific)
```php
case "highDramaPlayerTurn_01029": // The Pressure Is On
case "highDramaPlayerTurn_01035_3": // Kaspar recruit choice
case "highDramaPlayerTurn_01035_4": // Kaspar parley choice
case "highDramaPlayerTurn_01038_3": // Otto Streit attachment choice
case "highDramaPlayerTurn_01044": // Armed and Marshaled
case "highDramaPlayerTurn_01044_2": // Armed and Marshaled target
case "highDramaPlayerTurn_01044_3": // Armed and Marshaled manipulation
case "highDramaPlayerTurn_01046a": // Dark Gift location
case "highDramaPlayerTurn_01049": // Polished Flintlock
case "highDramaPlayerTurn_01049_2": // Polished Flintlock engage
case "highDramaPlayerTurn_01055": // Last Word character
case "highDramaPlayerTurn_01055_2": // Last Word location
case "highDramaPlayerTurn_01056": // Move Along
case "highDramaPlayerTurn_01056_2": // Move Along choice
case "highDramaPlayerTurn_01056_3": // Move Along technique
case "highDramaPlayerTurn_01058": // Press the Advantage
case "highDramaPlayerTurn_01059": // Regroup
case "highDramaPlayerTurn_01060": // Stratege location
case "highDramaPlayerTurn_01060_2": // Stratege performers
case "highDramaPlayerTurn_01060_3": // Stratege destination
case "highDramaPlayerTurn_01068": // Léontine Giroux character
case "highDramaPlayerTurn_01068_2": // Léontine Giroux location
case "highDramaPlayerTurn_01069": // Maxime De Lafayette discard
case "highDramaPlayerTurn_01069_2": // Maxime De Lafayette attachment
case "highDramaPlayerTurn_01072_2": // Réputation Méritée city card
case "highDramaPlayerTurn_01072_3": // Réputation Méritée muster
case "highDramaPlayerTurn_01076": // Blood Mark location
case "highDramaPlayerTurn_01076_2": // Blood Mark character
case "highDramaPlayerTurn_01081": // Gallant Deeds
case "highDramaPlayerTurn_01085": // Porté Travel
case "highDramaPlayerTurn_01086": // Status Matters
case "highDramaPlayerTurn_01147": // Let's Haggle
case "highDramaPlayerTurn_01149": // Midnight Shipment
case "highDramaPlayerTurn_01156": // Matchlock Musket discard
case "highDramaPlayerTurn_01156_2": // Matchlock Musket target
case "highDramaPlayerTurn_01156_3": // Matchlock Musket choice
case "highDramaPlayerTurn_01180_3": // Kaj Kousei artifact choice
case "highDramaPlayerTurn_01180_4": // Kaj Kousei performer
case "highDramaPlayerTurn_01180_5": // Kaj Kousei payment
case "highDramaPlayerTurn_01185": // Risky Undertaking
case "highDramaPlayerTurn_01189a": // Move reknown from
case "highDramaPlayerTurn_01189b": // Move reknown to
case "highDramaPlayerTurn_01192_3": // Gustavo risk choice
case "highDramaPlayerTurn_01194": // Adelheide attachment
case "highDramaPlayerTurn_01194_2": // Adelheide character
case "highDramaPlayerTurn_01197": // Kalla character from
case "highDramaPlayerTurn_01197_2": // Kalla attachment
case "highDramaPlayerTurn_01197_3": // Kalla character to
case "highDramaPlayerTurn_01200": // Crystal Eye opponent
case "highDramaPlayerTurn_01200_2": // Crystal Eye card
case "highDramaPlayerTurn_01205": // Kidnap character
case "highDramaPlayerTurn_01205_2": // Kidnap location
    // Default action: Pass or take first available option
    $this->actPass(); // or appropriate default action
    break;
```

#### Duel States
```php
case "duelChooseAction":
case "duelChooseTechnique":
case "duelUseManeuverFromCombatCard":
case "duelPayForManeuverFromCombatCard":
case "duelChooseGambleCard":
    // Default action: End round or pass
    $this->actDuelDoneRound(); // or appropriate default
    break;

case "duelChooseTechnique_01036": // Daniela's Technique
case "duelChooseTechnique_01063": // Bastien's Technique
case "duelChooseTechnique_01067": // Jean Urbain's Technique
case "duelResolveManeuver_01051": // Answering the Call
case "duelResolveManeuver_01059": // Regroup
case "duelResolveManeuver_01077": // Broken Time
case "duelResolveManeuver_01079": // Disarm
case "duelResolveManeuver_01079_2": // Disarm choice
case "duelResolveManeuver_01165": // Copy Technique
case "duelApplyCombatCardStats_01085": // Porté Travel
    // Default action: Pass or take first available option
    $this->actPass(); // or appropriate default action
    break;
```

#### Challenge Action States
```php
case "highDramaChallengeActionResolveTechnique_01063": // Bastien's Technique
case "highDramaChallengeActionActivateTechnique_01067": // Jean Urbain's Technique
    // Default action: Pass or take first available option
    $this->actPass(); // or appropriate default action
    break;
```

#### Dusk Phase States
```php
case "duskPhaseBegin01177": // Penya choice
case "duskPhaseBegin01177_2": // Penya card order
    // Default action: Pass
    $this->actPass();
    break;
```

#### Generic Reaction States
```php
case "playerReaction":
    // Default action: Done with reaction
    $this->gamestate->nextState("done");
    break;

case "playerPayForReaction":
    // Default action: Go back
    $this->actBack();
    break;
```

### Multiple Active Player States

#### Planning Phase
```php
case "planningPhase":
    // Default action: Mark as planned (auto-pick first available)
    $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned');
    break;
```

#### Deck Picking
```php
case "pickDecks":
    // Default action: Pick first available deck
    $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked');
    break;
```

#### Acknowledgment States
```php
case "planningPhaseResolveSchemes_01147": // Let's Haggle
case "planningPhaseEnd_01098_2": // The Cat's Embargo
case "highDramaPlayerTurn_01035": // Kaspar
case "highDramaPlayerTurn_01038": // Otto Streit
case "highDramaPlayerTurn_01072": // Réputation Méritée
case "highDramaPlayerTurn_01180": // Kaj Kousei
case "highDramaPlayerTurn_01192": // Gustavo
    // Default action: Acknowledge
    $this->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    break;
```

#### Dusk Phase Discard
```php
case "duskPhaseDiscard":
    // Default action: Auto-discard to panache limit
    $this->gamestate->setPlayerNonMultiactive($playerId, 'cardsDiscarded');
    break;
```

## Implementation Notes

### 1. Default Action Priority
1. **Pass/Decline** - For optional actions
2. **Go Back** - For multi-step actions
3. **Take First Option** - For required choices
4. **Auto-complete** - For complex actions

### 2. State Transition Handling
- Use `$this->gamestate->nextState()` for activeplayer states
- Use `$this->gamestate->setPlayerNonMultiactive()` for multipleactiveplayer states
- Ensure transitions match the state machine expectations

### 3. Error Handling
- Log zombie actions for debugging
- Fall back to safe defaults if specific actions fail
- Consider game balance when choosing defaults

### 4. Testing Considerations
- Test each state type with zombie players
- Verify game progression continues normally
- Check that zombie actions don't break game logic

## Code Structure

```php
protected function doZombieTurn(array $state, int $playerId): void
{
    $stateName = $state["name"];
    
    if ($state["type"] === "activeplayer") {
        switch ($stateName) {
            // Planning Phase States
            case "planningPhaseResolveSchemes_01016":
            case "planningPhaseResolveSchemes_01016_2":
            // ... other planning states
                $this->actPass();
                break;
                
            // High Drama States
            case "highDramaPlayerTurn":
                $this->actHighDramaPass();
                break;
                
            // ... other state cases
                
            default:
                throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
        }
        return;
    }
    
    if ($state["type"] === "multipleactiveplayer") {
        switch ($stateName) {
            case "planningPhase":
                $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned');
                break;
                
            case "pickDecks":
                $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked');
                break;
                
            // ... other multipleactiveplayer states
                
            default:
                throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
        }
        return;
    }
    
    throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
}
```

## Conclusion

This implementation plan covers all identified states that could hang the game when a player drops out. The strategy focuses on:

1. **Minimal disruption** - Choose actions that keep the game flowing
2. **Consistent behavior** - Use similar default actions for similar state types
3. **Game balance** - Avoid actions that give unfair advantages
4. **Error prevention** - Ensure the game can continue without the dropped player

The implementation should be tested thoroughly to ensure it handles all edge cases and maintains game integrity.
