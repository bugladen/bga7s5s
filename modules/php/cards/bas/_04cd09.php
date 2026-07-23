<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd09;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
class _04cd09 extends CityEventCard implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Knives Out');
        $this->Image = '04cd09.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->CityCardNumber = 9;

        $this->Traits = [
            clienttranslate('Ambush'),
            clienttranslate('Cunning')
        ];
        $this->Text = clienttranslate("<p>Characters at this location cannot refuse challenges.</p>
<p><b>City Action:</b> Engage your performer or discard a card • Move this card to another <b>City</b> location. Unlimited.</p>");

        $this->resetCard();
        
        $this->Actions = [
            new Action_04cd09(),
        ];
    }
    /**
     * WHY character-at-location gate (not a CHALLENGE_TYPE): applies to ANY challenge
     * whose defender is at Knives Out's city location, regardless of how the challenge
     * was issued. Mirrors Daichi `_03050::challengeRefusalBlocked`.
     */
    public static function challengeRefusalBlocked(Theah $theah, Character $defender): bool
    {
        if (! $theah->cardInCity($defender))
        {
            return false;
        }
        foreach ($theah->getCardObjectsAtLocation($defender->Location) as $card)
        {
            if ($card instanceof self)
            {
                return true;
            }
        }
        return false;
    }
}
