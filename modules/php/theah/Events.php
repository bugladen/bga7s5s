<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

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
    const ReknownAddedToCard = 'EventReknownAddedToCard';
    const ReknownAddedToLocation = 'EventReknownAddedToLocation';
    const ReknownRemovedFromLocation = 'EventReknownRemovedFromLocation';

    //Player Hand events
    const CardAddedToHand = 'EventCardAddedToHand';
    const CardDiscardedFromHand = 'EventCardDiscardedFromHand';
    const CardRemovedFromPlayerDiscardPile = 'EventCardRemovedFromPlayerDiscardPile';
    const CardRemovedFromPlayerFactionDeck = 'EventCardRemovedFromPlayerFactionDeck';

    //High Drama Events
    const LocationClaimed = 'EventLocationClaimed';
    const ChallengeIssued = 'EventChallengeIssued';
    const CharacterRecruited = 'EventCharacterRecruited';
    const AttachmentEquipped = 'EventAttachmentEquipped';
    
    //Challenge Events
    const CharacterIntervened = 'EventCharacterIntervened';
    const TechniqueActivated = 'EventTechniqueActivated';
    const ResolveTechnique = 'EventResolveTechnique';
    const GenerateThreat = 'EventGenerateThreat';

    //Duel Events
    const DuelStarted = 'EventDuelStarted';
    const DuelNewRound = 'EventDuelNewRound';
    
    const CharacterWounded = 'EventCharacterWounded';
    
    const CardMoved = 'EventCardMoved';
    const CardDrawn = 'EventCardDrawn';
    const CardEngaged = 'EventCardEngaged';
    
    const Transition = 'EventTransition';
    const ChangeActivePlayer = 'EventChangeActivePlayer';
}