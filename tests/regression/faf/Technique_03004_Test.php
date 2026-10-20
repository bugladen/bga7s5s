<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_03004_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03004';
    }

    /**
     * @return array{0:_03004,1:Technique_03004,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $elena = $world->placeCharacter(new _03004(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $elena;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        $sorcery = $world->placeCard(new _03009(), Game::LOCATION_DUELING_LINE, 1);
        $world->theah->combatCardsForCurrentRound = [$sorcery];
        /** @var Technique_03004 $technique */
        $technique = $elena->getTechniques()[0];
        return [$elena, $technique, $foe];
    }

    public function tests(): array
    {
        return [
            'is a technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_03004(), 'technique');
            },

            'available when Elena is the actor and any Sorcery combat card is hers' => function () {
                $world = new TestWorld();
                [$elena, $technique] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');

                $world->theah->combatCardsForCurrentRound = [$world->placeCard(new _02061(), Game::LOCATION_DUELING_LINE, 1)];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not Sorcery');

                $world->theah->combatCardsForCurrentRound = [$world->placeCard(new _03009(), Game::LOCATION_DUELING_LINE, 2)];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'opponent Sorcery');

                $world->theah->combatCardsForCurrentRound = [$world->placeCard(new _03009(), Game::LOCATION_HAND, 1)];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'controller match, location ignored');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in a duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                $world->theah->duelActor = $world->theah->duelOpponent;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                unset($elena);
            },

            'calculate adds +1 Parry and wounds the adversary' => function () {
                $world = new TestWorld();
                [$elena, $technique, $foe] = $this->scene($world);
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->actorId = $elena->Id;
                $event->parry = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->parry, 'parry');
                Assert::contains('+1 Parry', $event->explanations[0], 'explanation');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($foe->Id, $wound[0]->characterId, 'adversary');
                Assert::same($elena->Id, $wound[0]->sourceId, 'source');
                Assert::same($technique->Id, $wound[0]->abilityId, 'ability');
                Assert::same(1, $wound[0]->wounds, 'one');
            },

            'a different technique id does nothing' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->parry = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::same(1, $event->parry, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'no wound');
            },
        ];
    }
}
