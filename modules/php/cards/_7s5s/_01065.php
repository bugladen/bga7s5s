<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;

class _01065 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Henri Michelet");
        $this->Image = "01065.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 67;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("Maestro Machinist");
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Musketeer"),
            clienttranslate("Montaigne"),
        ];

        $this->Text = clienttranslate("<p>Your other Musketeers at Henri's location cannot be moved by an opponent's abilities.</p><p>Reaction: When Henri issues a challenge, engage his equipped Weapon • Target character cannot intervene.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01065(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCardMoved)
        {
            $card = $event->theah->getCardById($event->cardId);
            if ($card instanceof Character && 
                $card->Id != $this->Id &&
                $event->fromLocation == $this->Location && 
                $card->ControllerId == $this->ControllerId && 
                $card->hasTrait("Musketeer") &&
                $event->initiatingPlayerId != $this->ControllerId)
            {
                throw new \BgaUserException($event->theah->game->translate("Henri Michelet: other Musketeers at his location cannot be moved by opponent abilities."));
            }           

        }
    }

}