{OVERALL_GAME_HEADER}

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&display=swap" rel="stylesheet">

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    seventhseacityoffivesails_seventhseacityoffivesails.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<script type="text/javascript">

var jstpl_home=`
<div id="\${id}" class="home-container home-\${faction}">
    <div class="home-panel">
        <div class="crew-cap">\${crewcap}</div>
        <div class="panache">\${panache}</div>
        <div id="\${id}-locker"></div>
        <div class="seal seal-\${faction}"></div>
    </div>
    <div id="\${id}-home-anchor" class="home-endcap"></div>
</div>
`;

var jstpl_card_leader=`
<div id="\${id}">
<div class="card card-leader home-\${faction}" style="--card_image:url('\${image}')">
    <div class="character-resolve">7</div>
    <div class="character-stat character-combat">
        <div class="character-combat-value">9</div>
        <div class="character-combat-image"></div>
    </div>
    <div class="character-stat character-finesse">
        <div class="character-finesse-value">9</div>
        <div class="character-finesse-image"></div>
    </div>
    <div class="character-stat character-influence">
        <div class="character-influence-value">9</div>
        <div class="character-influence-image"></div>
    </div>
</div>
`;

</script>  

<!-- Begin City -->
<div id="city">
    <div id="city-ul-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
    <div id="day-indicator">1</div>
    <div id="city-ur-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
    <div id="city-oles-inn">
        <div id="oles-inn-reknown" class="city-reknown">0</div>
        <div id="oles-inn-image" class="city-image"style="--city-image:url(img/cards/7s5s/004.jpg)"></div>
        <div id="oles-inn-endcap" class="city-endcap"></div>
    </div>
    <div id="city-docks">
        <div id="dock-reknown" class="city-reknown">0</div>
        <div id="dock-image" class="city-image" style="--city-image:url(img/cards/7s5s/003.jpg)"></div>
        <div id="dock-endcap" class="city-endcap"></div>
    </div>
    <div id="city-forum">
        <div id="forum-reknown" class="city-reknown">0</div>
        <div id="forum-image" class="city-image" style="--city-image:url(img/cards/7s5s/001.jpg)"></div>
        <div id="forum-endcap" class="city-endcap"></div>
    </div>
    <div id="city-bazaar">
        <div id="bazaar-reknown" class="city-reknown">0</div>
        <div id="bazaar-image" class="city-image" style="--city-image:url(img/cards/7s5s/002.jpg)"></div>
        <div id="bazaar-endcap" class="city-endcap"></div>
    </div>
    <div id="city-governors-garden">
        <div id="governors-garden-reknown" class="city-reknown">0</div>
        <div id="governors-garden-image" class="city-image" style="--city-image:url(img/cards/7s5s/005.jpg)"></div>
        <div id="governors-garden-endcap" class="city-endcap"></div>
    </div>
    <div id="city-ll-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
    <div id="city-lr-tower">
        <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg">
            <rect width="30" height="30" class="city-tower" />
        </svg>
    </div>
</div>
<!-- End  City -->

<div id="home_anchor"></div>

{OVERALL_GAME_FOOTER}
