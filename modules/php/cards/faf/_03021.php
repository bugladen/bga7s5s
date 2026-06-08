<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;

class _03021 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Cornered');
        $this->Image = '03021.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 21;

        $this->initializeFaction('Eisen');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate('Challenge'),
            clienttranslate('Hunt'),
            clienttranslate('Zeal')
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Engage your performer • They issue a [Combat] challenge to target opposing <b>Sorcerer</b> or <b>Monster</b>. If they refuse, engage them. Wound any character that intervenes.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03021(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        $game = $event->theah->game;

        if ($event instanceof EventChallengeRejected
            && $game->globals->get(Game::CHALLENGE_TYPE) == Game::CORNERED_CHALLENGE_TYPE)
        {
            $target = $event->theah->getCharacterById($event->targetId);
            if ($target && ! $target->Engaged) {
                $game->notify->all(
                    "message",
                    clienttranslate('${target_inject_code} has rejected the challenge from ${challenge_card}. ${target_inject_code} will be Engaged.'),
                    [
                        "target_inject_code" => $target->getInjectCode(),
                        "challenge_card" => $this->getInjectCode(),
                    ]
                );
                $engageEvent = EventFactory::createCardEngagedEvent($target->ControllerId, $target->Id);
                $event->theah->queueEvent($engageEvent);
            }
        }

        if ($event instanceof EventCharacterIntervened
            && $game->globals->get(Game::CHALLENGE_TYPE) == Game::CORNERED_CHALLENGE_TYPE)
        {
            $intervener = $event->theah->getCharacterById($event->newTargetId);
            if ($intervener) {
                $game->notify->all(
                    "message",
                    clienttranslate('${intervener_inject_code} has intervened in the challenge from ${challenge_card}. ${intervener_inject_code} will be Wounded.'),
                    [
                        "intervener_inject_code" => $intervener->getInjectCode(),
                        "challenge_card" => $this->getInjectCode(),
                    ]
                );
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $intervener->Id,
                    $this->Id,
                    1,
                    $this->getInjectCode()
                );
                $event->theah->eventCheck($woundEvent);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}