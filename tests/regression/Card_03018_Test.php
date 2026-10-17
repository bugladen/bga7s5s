<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_03018_Test extends TestCase
{
    public function name(): string
    {
        return '_03018 Suffer Not the Wicked';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Finale scheme with the refuse reaction' => function () {
                $scheme = new _03018();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(41, $scheme->Initiative, 'initiative');
                Assert::same(0, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Eisen'), 'Eisen');
                Assert::true($scheme->hasTrait('Heroic'), 'Heroic');
                Assert::true($scheme->hasTrait('Finale'), 'Finale');
                Assert::instanceOf(Reaction_03018::class, $scheme->getReactions()[0], 'reaction');
            },

            'resolve adds Renown to the Bazaar and the Forum and grants the technique to wounded characters' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03018(), Game::LOCATION_PLAYER_HOME, 1);
                $wounded = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                $wounded->Wounds = 2;
                $healthy = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_FORUM, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $plain->Wounds = 2;
                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::same(Game::LOCATION_CITY_BAZAAR, $renown[0]->location, 'bazaar');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[1]->location, 'forum');
                Assert::same(1, $renown[0]->amount, 'one');
                Assert::false($renown[0]->isMove, 'added');
                Assert::true($wounded->getTechniqueByClassId('Technique_03018') !== null, 'wounded leader gained it');
                Assert::same(null, $healthy->getTechniqueByClassId('Technique_03018'), 'unwounded did not');
                // WHY: Character implements IHasTechniques, so a wounded generic is granted too.
                Assert::true($plain->getTechniqueByClassId('Technique_03018') !== null, 'any wounded character');
            },

            // WHY: The wound event grants even before Wounds is incremented, because this
            // handler may run first. A heal uses the post-heal total when the character
            // already handled it, otherwise Wounds minus the event. Dusk strips every copy.
            // The scheme has to be at Home for the wound and heal; resolve is the sync path.
            'wounds grant the technique, a full heal removes it, and dusk strips it' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03018(), Game::LOCATION_PLAYER_HOME, 1);
                $daniella = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                $wounded = new EventCharacterWounded();
                $wounded->theah = $world->theah;
                $wounded->characterId = $daniella->Id;
                $wounded->characterId = $daniella->Id;
                $scheme->handleEvent($wounded);
                Assert::true($daniella->getTechniqueByClassId('Technique_03018') !== null, 'granted at zero wounds');
                $before = count($daniella->getTechniques());
                $scheme->handleEvent($wounded);
                Assert::same($before, count($daniella->getTechniques()), 'idempotent');

                $scheme->Location = Game::LOCATION_CITY_DOCKS;
                $other = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_FORUM, 1);
                $wounded->characterId = $other->Id;
                $scheme->handleEvent($wounded);
                Assert::same(null, $other->getTechniqueByClassId('Technique_03018'), 'scheme must be home');
                $scheme->Location = Game::LOCATION_PLAYER_HOME;

                $daniella->Wounds = 1;
                $healed = new EventCharacterHealed();
                $healed->theah = $world->theah;
                $healed->characterId = $daniella->Id;
                $healed->wounds = 1;
                $healed->characterHandled = false;
                $scheme->handleEvent($healed);
                Assert::same(null, $daniella->getTechniqueByClassId('Technique_03018'), 'heal to zero removes it');

                $wounded->characterId = $daniella->Id;
                $scheme->handleEvent($wounded);
                $daniella->Wounds = 2;
                $healed->wounds = 1;
                $healed->characterHandled = true;
                $scheme->handleEvent($healed);
                Assert::true($daniella->getTechniqueByClassId('Technique_03018') !== null, 'still wounded after a partial heal');

                $end = new EventDuskPhaseEnd();
                $end->theah = $world->theah;
                $scheme->handleEvent($end);
                Assert::same(null, $daniella->getTechniqueByClassId('Technique_03018'), 'dusk');

                $dying = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                $dying->IsDying = true;
                $dying->Wounds = 3;
                $event = new EventResolveScheme();
                $event->theah = $world->theah;
                $event->scheme = $scheme;
                $event->playerId = 1;
                $scheme->handleEvent($event);
                Assert::same(null, $dying->getTechniqueByClassId('Technique_03018'), 'dying is skipped');
                unset($other);
            },
        ];
    }
}
