<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;

class _03057 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Censure");
        $this->Image = '03057.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 57;

        $this->initializeFaction('Ussura');

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Challenge'),
            clienttranslate('Bureaucracy')
        ];

        $this->Text = clienttranslate("<b>City Action:</b> Engage your performer • Issue an [Influence] challenge to target opposing character. If the challenge is refused, claim your performer's location.");

        $this->resetCard();

        $this->Actions = [
            new Action_03057(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        $game = $event->theah->game;

        // WHY gate on CENSURE_CHALLENGE_TYPE: EventChallengeRejected has no actionId;
        // challengerId is the performer, not a stable Risk identity. See Pattern A / _03021.
        if ($event instanceof EventChallengeRejected
            && $game->globals->get(Game::CHALLENGE_TYPE) == Game::CENSURE_CHALLENGE_TYPE)
        {
            $challenger = $event->theah->getCharacterById($event->challengerId);
            if ($challenger == null)
            {
                return;
            }

            $location = $challenger->Location;
            if (! $event->theah->cardInCity($challenger))
            {
                return;
            }

            if (! $event->theah->canLocationBeClaimedBy($challenger->ControllerId, $location))
            {
                $game->notify->all(
                    "message",
                    clienttranslate('${challenge_card}: ${location_name} cannot be claimed after the challenge was refused.'),
                    [
                        "i18n" => ["location_name"],
                        "challenge_card" => $this->getInjectCode(),
                        "location_name" => $location,
                    ]
                );
                return;
            }

            $game->notify->all(
                "message",
                clienttranslate('${challenge_card}: The challenge was refused. ${player_name} claims ${location_name}.'),
                [
                    "i18n" => ["location_name"],
                    "challenge_card" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($challenger->ControllerId),
                    "location_name" => $location,
                ]
            );

            $claimEvent = EventFactory::createLocationClaimedEvent(
                $challenger->ControllerId,
                $challenger->Id,
                $location
            );
            $event->theah->queueEvent($claimEvent);
        }
    }
}
