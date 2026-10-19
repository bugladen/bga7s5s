<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04021a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_04021a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04021a';
    }

    public function tests(): array
    {
        return [
            // WHY: Copy is duel-only and only while Aimée is the actor. The list is other
            // controlled Musketeers at her location. Her own copy technique and temporary
            // copies are skipped. Attachment techniques are not on that list.
            'offered only to copy another musketeer technique while she is acting' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $aimee = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_04021a $technique */
                $technique = $aimee->getTechniques()[0];
                $ally = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no actor');
                $world->theah->duelActor = $ally;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'ally is acting');
                $world->theah->duelActor = $aimee;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'her turn, ally has +1 Thrust');

                $ally->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'ally left');
                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $plain = $world->placeCharacter(new GenericCharacter('Guard', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                $ally->ControllerId = 2;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'opposing musketeer and a musketeer with no technique');
                unset($plain);

                $bow = $world->placeCard(new _04026(), Game::LOCATION_CITY_DOCKS, 1);
                $bow->AttachedToId = $aimee->Id;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'attachment technique does not count');
            },

            // WHY: Resolve only opens the picker. The chosen id must be on the list.
            // The copy is temporary, owned by the actor, and resolved immediately.
            'resolve opens the picker, and the copy resolves without re-engaging' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $aimee = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_04021a $technique */
                $technique = $aimee->getTechniques()[0];
                $source = $ally->getTechniques()[1];
                $world->theah->duelActor = $aimee;
                $world->theah->duelOpponent = $ally;

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::same('04021', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $threw = false;
                try {
                    $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_04021, '', [99999]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'unknown technique');

                $world->theah->takeQueuedEvents();
                $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_04021, '', [$source->Id]);
                Assert::same('cardChosen', $world->game->gamestate->transitions[0] ?? null, 'chosen');
                Assert::false((bool) $world->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'not the main technique');
                $copyId = (string) $world->game->globals->get(Game::CHOSEN_TECHNIQUE);
                Assert::true(str_contains($copyId, '_copy_'), 'copy id');
                Assert::count(1, $world->theah->queuedOfType(EventTechniqueActivated::class), 'activated');
                Assert::count(1, $world->theah->queuedOfType(EventResolveTechnique::class), 'resolved');
                Assert::count(1, $world->theah->queuedOfType(EventDuelCalculateTechniqueValues::class), 'values');
                $found = false;
                foreach ($aimee->getTechniques() as $owned) {
                    if ($owned->Id === $copyId) {
                        $found = $owned->IsTemporaryCopy && $owned->Used === false;
                    }
                }
                Assert::true($found, 'temporary unused copy on Aimée');
            },
        ];
    }
}
