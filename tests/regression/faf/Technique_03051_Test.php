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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03051_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03051';
    }

    /**
     * @return array{0:TestWorld,1:Technique,2:GenericCharacter,3:_03064}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $world->game->globals->set(Game::IN_DUEL, true);
        $yep = $world->placeCharacter(new _03051(), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $harpoon = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 1);
        $harpoon->AttachedToId = $yep->Id;
        $yep->Attachments[] = $harpoon->Id;
        $recruited = new EventCharacterRecruited();
        $recruited->characterId = $ally->Id;
        $world->fireOn($yep, $recruited);
        $world->theah->duelActor = $ally;
        $world->theah->duelOpponent = $foe;
        $technique = $ally->getTechniqueByClassId('Technique_03051');
        return [$world, $technique, $ally, $harpoon];
    }

    public function tests(): array
    {
        return [
            // WHY: Source techniques gate on Yepikhodov being the actor, so availability
            // lists techniques on his unengaged attachments without calling theirs.
            // Engaged, fake, and a Yepikhodov who is not here hide the copy.
            'copies a technique on yepikhodovs unengaged attachment' => function () {
                [$world, $technique, $ally, $harpoon] = $this->scene();
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                $harpoon->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
                $harpoon->Engaged = false;
                $harpoon->FakeAttachment = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'fake');
                $harpoon->FakeAttachment = false;

                $world->theah->duelActor = $world->theah->duelOpponent;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
                $world->theah->duelActor = $ally;

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                Assert::same('03051', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');

                $source = $harpoon->getTechniques()[0];
                $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_03051, 'x', [$source->Id]);
                Assert::same($harpoon->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');
                Assert::same($ally->Id . '_copy_Technique_03064', $world->game->globals->get(Game::CHOSEN_TECHNIQUE), 'copy id');
                Assert::false((bool) $world->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'not main');
                Assert::same(['cardChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');
            },

            'a technique that is not on the attachment throws before the engage' => function () {
                [$world, $technique] = $this->scene();
                $threw = false;
                try {
                    $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_03051, 'x', ['missing']);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
            },
        ];
    }
}
