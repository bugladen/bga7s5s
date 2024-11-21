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

    const AddReknownToTwoDifferentLocations = 'EventAddReknownToTwoDifferentLocations';
    const PlayerLosesReknown = 'EventPlayerLosesReknown';
    const ReknownAddedToCard = 'EventReknownAddedToCard';
    const ReknownAddedToLocation = 'EventReknownAddedToLocation';

    const Transition = 'EventTransition';
}