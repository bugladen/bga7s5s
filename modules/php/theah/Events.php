<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class Events
{
    const NewDay = 'EventNewDay';
    const PhaseDawnBeginning = 'EventPhaseDawnBeginning';

    const CityCardAddedToLocation = 'EventCityCardAddedToLocation';
    const CardRemovedFromCityDiscardPile = 'EventCardRemovedFromCityDiscardPile';
    const CardAddedToCityDeck = 'EventCardAddedToCityDeck';

    const PhaseDawnEnding = 'EventPhaseDawnEnding';
    const PhasePlanningBeginning = 'EventPhasePlanningBeginning';
    const PhasePlanningEnd = 'EventPhasePlanningEnd';
    const PhaseHighDrama = 'EventPhaseHighDrama';

    const ApproachCharacterPlayed = 'EventApproachCharacterPlayed';
    const SchemeCardRevealed = 'EventSchemeCardRevealed';
    const ResolveScheme = 'EventResolveScheme';

    const PlayerLosesReknown = 'EventPlayerLosesReknown';
    const ReknownAddedToCard = 'EventReknownAddedToCard';
    const ReknownAddedToLocation = 'EventReknownAddedToLocation';
    const ReknownRemovedFromLocation = 'EventReknownRemovedFromLocation';

    const CardAddedToHand = 'EventCardAddedToHand';
    const CardRemovedFromPlayerDiscardPile = 'EventCardRemovedFromPlayerDiscardPile';
    
    const Transition = 'EventTransition';
}