<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class Events
{
    const NewDay = 'EventNewDay';
    const PhaseDawnBeginning = 'EventPhaseDawnBeginning';
    const CityCardAddedToLocation = 'EventCityCardAddedToLocation';
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
    const CardRemovedFromDiscardPile = 'EventCardRemovedFromDiscardPile';
    
    const Transition = 'EventTransition';
}