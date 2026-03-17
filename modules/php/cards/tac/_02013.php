<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02013;

class _02013 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Wilhelm Dünst');
        $this->Image = '02013.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 13;

        $this->initializeFaction('Eisen');
        $this->Title = 'Zealous Warrior';
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            'Hero',
            'Zealot',
            'Hunter',
            'Eisen',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_02013(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeIssued && $event->challengerId == $this->Id)
        {
            $challengeStat = $event->theah->game->globals->get(Game::CHALLENGE_STAT);
            if ($challengeStat != Game::STAT_COMBAT)
            {
                throw new UserException($event->theah->game->translate("Wilhelm Dünst may only issue Combat challenges."));
            }

            $defender = $event->theah->getCharacterById($event->defenderId);
            if (!$defender->hasTrait("Villain") && !$defender->hasTrait("Sorcerer") && !$defender->hasTrait("Monster"))
            {
                throw new UserException($event->theah->game->translate("Wilhelm Dünst may only issue challenges to Villains, Sorcerers, or Monsters."));
            }
        }
    }
}