{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- incangold implementation : © Antonio Soler <morgald.es@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    incangold_incangold.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="playArea">
    <div class="templePanel">
        <div id="info">
            <img class="gameArt" id="gameArt">
            <h3 id="iterationsText"></h3>
            <div>
                <div id="remainingText"></div>
            </div>
        </div>
        
    </div>

   <!-- BEGIN opponent -->
   <div id="playerFields_{PLAYER_ID}" class="whiteblock fields">
      <h2 class="fieldHeader">{PLAYER_NAME}</h2>
		<!-- BEGIN field -->
		<div id="playerFields_{PLAYER_ID}_{FIELD_ID}" class="playerField"></div>
		<!-- END field -->		
	</div>
    <!-- END opponent -->
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${id}"></div>';

*/

var jstpl_cardontable = '<div class="card" id="card_${card_id}" style="position:absolute; height: ${height}px; width: ${width}px; background-position:-${x}px -${y}px;z-index:${z};"></div>';

</script>  

{OVERALL_GAME_FOOTER}
