<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;

class Events
{
    //Phase Events
    const NewDay = 'EventNewDay';
    const PhaseDawnBeginning = 'EventPhaseDawnBeginning';
    const PhaseDawnEnding = 'EventPhaseDawnEnding';
    const FirstPlayerDetermined = 'EventFirstPlayerDetermined';
    const PhaseMuster = 'EventPhaseMuster';
    const PhasePlanningBeginning = 'EventPhasePlanningBeginning';
    const PhasePlanningEnd = 'EventPhasePlanningEnd';
    const PhaseHighDrama = 'EventPhaseHighDrama';

    //Planning Phase Events
    const ApproachCharacterPlayed = 'EventApproachCharacterPlayed';
    const SchemeCardRevealed = 'EventSchemeCardRevealed';
    const SchemeMovedToCity = 'EventSchemeMovedToCity';
    const ResolveScheme = 'EventResolveScheme';

    //City Card Events
    const CardAddedToCityDeck = 'EventCardAddedToCityDeck';
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
    const ClaimOccuring = 'EventClaimOccuring';
    const LocationClaimed = 'EventLocationClaimed';
    const CharacterRecruited = 'EventCharacterRecruited';
    const HighDramaPhasePlayerPassed = 'EventHighDramaPhasePlayerPassed';
    const HighDramaPhaseEnd = 'EventHighDramaPhaseEnd';
    
    //Challenge Events
    const ChallengeIssued = 'EventChallengeIssued';
    const CharacterIntervened = 'EventCharacterIntervened';
    const GenerateChallengeThreat = 'EventGenerateChallengeThreat';

    //Duel Events
    const DuelStarted = 'EventDuelStarted';
    const DuelNewRound = 'EventDuelNewRound';
    const ResolveTechnique = 'EventResolveTechnique';
    const DuelCalculateTechniqueValues = 'EventDuelCalculateTechniqueValues';
    const ResolveManeuver = 'EventResolveManeuver';
    const DuelCalculateManeuverValues = 'EventDuelCalculateManeuverValues';
    const DuelGetCostForManeuverFromHand = 'EventDuelGetCostForManeuverFromHand';
    const DuelCalculateCombatCardStats = 'EventDuelCalculateCombatCardStats';
    const DuelPlayerGambled = 'EventDuelPlayerGambled';
    const DuelActionsDone = 'EventDuelActionsDone';
    const DuelEnd = 'EventDuelEnd';

    //Plunder Phase events
    const PlunderPhaseBegin = 'EventPlunderPhaseBegin';
    const PlunderPhaseEnd = 'EventPlunderPhaseEnd';
    const PlayerTakeReknownForControlledLocation = 'EventPlayerTakeReknownForControlledLocation';
    const PlunderPhaseAdditionalReknownEvent = 'EventPlunderPhaseAdditionalReknownEvent';
    
    //Card Manipulation events
    const AttachmentEquipped = 'EventAttachmentEquipped';
    const AttachmentUnequipped = 'EventAttachmentUnequipped';
    const CardMoved = 'EventCardMoved';
    const CardDiscardedFromPlay = 'EventCardDiscardedFromPlay';
    const CardDrawn = 'EventCardDrawn';
    const CardEngaged = 'EventCardEngaged';
    const CardEngarded = 'EventCardEngarded';
    const CharacterWounded = 'EventCharacterWounded';
    const CharacterHealed = 'EventCharacterHealed';
    const CharacterDestroyed = 'EventCharacterDestroyed';
    const SchemeSentToLocker = 'EventSchemeSentToLocker';

    //Dusk Phase events
    const DuskPhaseBegin = 'EventDuskPhaseBegin';
    const DuskPhaseEnd = 'EventDuskPhaseEnd';
    const DuskEndOfDay = 'EventDuskEndOfDay';
    
    //Game Flow events
    const Transition = 'EventTransition';
    const ChangeActivePlayer = 'EventChangeActivePlayer';

    const ActionTriggered = 'EventActionTriggered';
    const ActionUsed = 'EventActionUsed';
    const ReactionUsed = 'EventReactionUsed';
    const ManeuverUsed = 'EventManeuverUsed';
    const TechniqueUsed = 'EventTechniqueUsed';
}