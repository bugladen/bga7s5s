<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01178 extends CityCharacter  
{
    public bool $AbilityUsed;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Carmella Vanessa Slavaggi');
        $this->Image = '01178.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 178;

        $this->Title = clienttranslate('Lady V');

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->WealthCost = 5;        
        $this->CityCardNumber = 2;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Duelist'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>Once per Day, Carmella may issue a challenge or intervene in one even while engaged.</p>");

        $this->resetCard();

        $this->AbilityUsed = false;
    }

    public function canChallenge(Theah $theah): bool
    {
        if (!parent::canChallenge($theah))
            return false;

        return ! $this->Engaged || ! $this->AbilityUsed;
    }

    public function canIntervene(): bool
    {
        if (!parent::canIntervene())
            return false;

        return ! $this->Engaged || ! $this->AbilityUsed;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id && $this->Engaged)
        {
            $this->setAbilityUsed($event->theah->game);
        }

        if ($event instanceof EventChallengeIssued && $event->challengerId == $this->Id && $this->Engaged)
        {
            $this->setAbilityUsed($event->theah->game);
        }

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->clearAbilityUsed($event->theah->game);
        }
    }

    private function setAbilityUsed(Game $game): void
    {
        $this->AbilityUsed = true;
        $this->addCondition(Game::CARMELLA_ABILITY_USED);
        $this->IsUpdated = true;
        $game->notify->all("carmellaAbilityUsed", '', [
            "cardId" => $this->Id,
        ]);
    }

    private function clearAbilityUsed(Game $game): void
    {
        $this->AbilityUsed = false;
        $this->removeCondition(Game::CARMELLA_ABILITY_USED);
        $this->IsUpdated = true;
        $game->notify->all("carmellaAbilityRemoved", '', [
            "cardId" => $this->Id,
        ]);
    }
}