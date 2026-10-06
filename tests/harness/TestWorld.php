<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class TestWorld
{
    public Game $game;
    public TestTheah $theah;
    private int $nextId = 1000;

    public function __construct()
    {
        $this->game = new Game();
        $this->theah = new TestTheah($this->game);
        $this->game->theah = $this->theah;
    }

    public function nextId(): int
    {
        return ++$this->nextId;
    }

    public function placeCard(Card $card, string $location, int $controllerId, ?int $id = null): Card
    {
        $card->setId($id ?? $this->nextId());
        $card->ControllerId = $controllerId;
        $card->OwnerId = $controllerId;
        $card->Location = $location;
        $this->theah->addCardToWorld($card);
        $this->game->registerDbCard($card);
        return $card;
    }

    public function placeCharacter(Character $character, string $location, int $controllerId, ?int $id = null): Character
    {
        /** @var Character $placed */
        $placed = $this->placeCard($character, $location, $controllerId, $id);
        return $placed;
    }

    public function fire(Event $event): void
    {
        $event->theah = $this->theah;
        foreach ($this->getCardsSnapshot() as $card) {
            $card->handleEvent($event);
        }
    }

    public function fireOn(Card $card, Event $event): void
    {
        $event->theah = $this->theah;
        $card->handleEvent($event);
    }

    /** @return list<Card> */
    private function getCardsSnapshot(): array
    {
        $cards = [];
        $prop = new \ReflectionProperty(\Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah::class, 'cards');
        $prop->setAccessible(true);
        /** @var array<int, Card> $map */
        $map = $prop->getValue($this->theah);
        foreach ($map as $card) {
            $cards[] = $card;
        }
        return $cards;
    }
}
