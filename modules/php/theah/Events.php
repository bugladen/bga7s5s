<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class Events
{
    const TableSetup = 'EventTableSetup';
    
    //Phase Events
    const NewDay = 'EventNewDay';
    const PhaseDawnBeginning = 'EventPhaseDawnBeginning';
    const PhaseDawnEnding = 'EventPhaseDawnEnding';
    const FirstPlayerDetermined = 'EventFirstPlayerDetermined';
    const PhaseMuster = 'EventPhaseMuster';
    const PhasePlanningBeginning = 'EventPhasePlanningBeginning';
    const PhasePlanningEnd = 'EventPhasePlanningEnd';
    const PhaseHighDrama = 'EventPhaseHighDrama';
    const PlayerTurnEnd = 'EventPlayerTurnEnd';

    //Planning Phase Events
    const ApproachCharacterPlayed = 'EventApproachCharacterPlayed';
    const SchemeCardRevealed = 'EventSchemeCardRevealed';
    const SchemeMovedToCity = 'EventSchemeMovedToCity';
    const ResolveScheme = 'EventResolveScheme';

    //City Card Events
    const CardAddedToCityDeck = 'EventCardAddedToCityDeck';
    const CardAddedToFactionDeck = 'EventCardAddedToFactionDeck';
    const CityCardAddedToLocation = 'EventCityCardAddedToLocation';
    const CardAddedToCityDiscardPile = 'EventCardAddedToCityDiscardPile';
    const CardRemovedFromCityDiscardPile = 'EventCardRemovedFromCityDiscardPile';

    //Reknown Events
    const PlayerLosesReknown = 'EventPlayerLosesReknown';
    const PlayerGainsReknown = 'EventPlayerGainsReknown';
    const ReknownAddedToCard = 'EventReknownAddedToCard';
    const ReknownRemovedFromCard = 'EventReknownRemovedFromCard';
    const ReknownAddedToLocation = 'EventReknownAddedToLocation';
    const ReknownRemovedFromLocation = 'EventReknownRemovedFromLocation';

    //Player Hand events
    const CardAddedToHand = 'EventCardAddedToHand';
    const CardDiscardedFromHand = 'EventCardDiscardedFromHand';
    const CardRemovedFromPlayerDiscardPile = 'EventCardRemovedFromPlayerDiscardPile';
    const CardRemovedFromPlayerFactionDeck = 'EventCardRemovedFromPlayerFactionDeck';

    //High Drama Events
    const PressureOccuring = 'EventPressureOccuring';
    const LocationPressured = 'EventLocationPressured';
    const LocationPressureResult = 'EventLocationPressureResult';
    const LocationClaimed = 'EventLocationClaimed';
    const CharacterRecruited = 'EventCharacterRecruited';
    const HighDramaPhasePlayerPassed = 'EventHighDramaPhasePlayerPassed';
    const HighDramaPhaseEnd = 'EventHighDramaPhaseEnd';
    
    //Challenge Events
    const ChallengeIssued = 'EventChallengeIssued';
    const ChallengeAccepted = 'EventChallengeAccepted';
    const ChallengeRejected = 'EventChallengeRejected';
    const ChallengerSwapped = 'EventChallengerSwapped';
    const CharacterIntervened = 'EventCharacterIntervened';
    const DefenderSwapped = 'EventDefenderSwapped';
    const GenerateChallengeThreat = 'EventGenerateChallengeThreat';
    const ThreatModified = 'EventThreatModified';
    const TechniqueActivated = 'EventTechniqueActivated';
    const ManeuverActivated = 'EventManeuverActivated';

    //Duel Events
    const CombatCardAnnounced = 'EventCombatCardAnnounced';
    const DuelStarted = 'EventDuelStarted';
    const DuelNewRound = 'EventDuelNewRound';
    const ResolveTechnique = 'EventResolveTechnique';
    const DuelCalculateTechniqueValues = 'EventDuelCalculateTechniqueValues';
    const ResolveManeuver = 'EventResolveManeuver';
    const DuelCalculateManeuverValues = 'EventDuelCalculateManeuverValues';
    const DuelCalculateCombatCardStats = 'EventDuelCalculateCombatCardStats';
    const DuelPlayerGambled = 'EventDuelPlayerGambled';
    const DuelAttemptGamble = 'EventDuelAttemptGamble';
    const DuelGambleCardsRevealed = 'EventDuelGambleCardsRevealed';
    const DuelActionsDone = 'EventDuelActionsDone';
    const DuelEndOfRound = 'EventDuelEndOfRound';
    const DuelEnd = 'EventDuelEnd';

    //Plunder Phase events
    const PlunderPhaseBegin = 'EventPlunderPhaseBegin';
    const PlunderPhaseEnd = 'EventPlunderPhaseEnd';
    const PlayerTakeReknownForControlledLocation = 'EventPlayerTakeReknownForControlledLocation';
    const PlunderPhaseAdditionalReknownEvent = 'EventPlunderPhaseAdditionalReknownEvent';
    
    //Card Manipulation events
    const AttachmentEquipped = 'EventAttachmentEquipped';
    const AttachmentUnequipped = 'EventAttachmentUnequipped';
    const AttachmentMoved = 'EventAttachmentMoved';
    const CardMoving = 'EventCardMoving';
    const CardMoved = 'EventCardMoved';
    const CardDiscardedFromPlay = 'EventCardDiscardedFromPlay';
    const CardDrawn = 'EventCardDrawn';
    const CardEngaged = 'EventCardEngaged';
    const CardEngarded = 'EventCardEngarded';
    const CardHidden = 'EventCardHidden';
    const CardSentToLocker = 'EventCardSentToLocker';
    const CardRemovedFromLocker = 'EventCardRemovedFromLocker';
    const CardRemovedFromPlay = 'EventCardRemovedFromPlay';
    const CharacterDestroyed = 'EventCharacterDestroyed';
    const CharacterBeingHealed = 'EventCharacterBeingHealed';
    const CharacterHealed = 'EventCharacterHealed';
    const CharacterPutIntoApproachDeck = 'EventCharacterPutIntoApproachDeck';
    const CharacterBeingWounded = 'EventCharacterBeingWounded';
    const CharacterWounded = 'EventCharacterWounded';
    const ManeuverCanceled = 'EventManeuverCanceled';
    const TechniqueCanceled = 'EventTechniqueCanceled';
    const SorcererAbilityPlayed = 'EventSorcererAbilityPlayed';
    const SorcererAbilityStart = 'EventSorcererAbilityStart';
    const RiskPlayed = 'EventRiskPlayed';
    const RangedAbilityPlayed = 'EventRangedAbilityPlayed';

    //Character events
    const CharacterCombatModified = 'EventCharacterCombatModified';
    const CharacterInfluenceModified = 'EventCharacterInfluenceModified';
    const CharacterFinesseModifed = 'EventCharacterFinesseModifed';
    const CardMustered = 'EventCardMustered';
    const CharacterMustered = 'EventCharacterMustered';
    const CharacterLostBrute = 'EventCharacterLostBrute';
    
    //Dusk Phase events
    const DuskPhaseBegin = 'EventDuskPhaseBegin';
    const DuskPhaseEnd = 'EventDuskPhaseEnd';
    const DuskEndOfDay = 'EventDuskEndOfDay';
    
    //Game Flow events
    const Transition = 'EventTransition';
    const ChangeActivePlayer = 'EventChangeActivePlayer';
    const EnteringPayState = 'EventEnteringPayState';
    const CalculatePayDiscount = 'EventCalculatePayDiscount';

    const ActionTriggered = 'EventActionTriggered';
    const ActionActivated = 'EventActionActivated';
    const ActionResolved = 'EventActionResolved';
    const RiskReactionTriggered = 'EventRiskReactionTriggered';
    const ActionUsed = 'EventActionUsed';
    const ReactionActivated = 'EventReactionActivated';
    const ReactionUsed = 'EventReactionUsed';
    const ManeuverUsed = 'EventManeuverUsed';
    const TechniqueUsed = 'EventTechniqueUsed';

    const LocationBecomesUncontrolled = 'EventLocationBecomesUncontrolled';
}