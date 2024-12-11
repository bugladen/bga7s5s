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

    const CardAddedToHand = 'EventCardAddedToHand';
    const CardRemovedFromPlayerDiscardPile = 'EventCardRemovedFromPlayerDiscardPile';

    const CardMoved = 'EventCardMoved';
    
    const Transition = 'EventTransition';
}