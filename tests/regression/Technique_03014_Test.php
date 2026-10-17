<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_03014_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03014';
    }

    public function tests(): array
    {
        return [
            'is a technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_03014(), 'technique');
            },

            // WHY: Eisenfaust can be equipped or sitting in his own dueling line.
            // The opponent's line does not count. He must be the duel actor.
            // Calculate does not recheck the attachment.
            'wounds the adversary when Kaspar is the actor and has an Eisenfaust' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _03014(), Game::LOCATION_CITY_DOCKS, 1);
                $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $kaspar;
                $world->theah->duelOpponent = $adversary;
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Technique_03014 $technique */
                $technique = $kaspar->getTechniques()[0];
                $technique->setOwnerId($kaspar->Id);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no eisenfaust');

                $line = $world->placeCard(new _03019(), Game::LOCATION_DUELING_LINE, 2);
                $line->addTrait($world->game, 'Eisenfaust', true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'opponent line');

                $equipped = $world->placeCard(new _03019(), Game::LOCATION_CITY_DOCKS, 1);
                $equipped->addTrait($world->game, 'Eisenfaust', true);
                $kaspar->Attachments[] = $equipped->Id;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'equipped');

                $world->theah->duelActor = $adversary;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->theah->duelActor = $kaspar;

                $kaspar->Attachments = [];
                $ownLine = $world->placeCard(new _03019(), Game::LOCATION_DUELING_LINE, 1);
                $ownLine->addTrait($world->game, 'Eisenfaust', true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'own dueling line');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($adversary->Id, $wound->characterId, 'adversary');
                Assert::same($kaspar->Id, $wound->sourceId, 'kaspar');
                Assert::same(1, $wound->wounds, 'one');
            },
        ];
    }
}
