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
        <div id="temple">
		<table id="templetable" class="templeclass"  >
			<tbody>
				<tr>
					<td id="templecard5" colspan="2" > </td>					
				</tr>
				<tr>
					<td id="templecard3"> </td>
					<td id="templecard4"> </td>
				</tr>
				<tr>
					<td id="templecard1"> </td>
					<td id="templecard2"> </td>
				</tr> </tbody>
		</table>
		</div>
    </div>
    &nbsp;
   <!-- BEGIN camp -->
   <div id="playerCamp_{PLAYER_ID}" class="whiteblock fields">
       <table id="tablecamp_{PLAYER_ID}" width="100%" >
			<tbody>
				<tr>
					<td rowspan="3" > <div id="votecard_{PLAYER_ID}" class="votecard"></div> </td>
					<td ><h2 class="Header" style="color:#{PLAYER_COLOR};"  >{PLAYER_NAME}</h2></td>
				</tr>
				<tr>
					<td > <div id="tent_{PLAYER_ID}" class="tent"></div> </td>
				</tr>
				<tr>
					<td> <div id="gem_icon_{PLAYER_ID}" class="gem"></div></td>
				</tr> 
			</tbody>
		</table> 
	</div>
    <!-- END camp -->
    <br class="clear">
	
	<div id="table_wrap" >
	<h2>{TABLE}</h2> 
	<div id="table" class="whiteblock table"></div>
	</div>
	
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${id}" style="position:absolute; top: ${x}px; left: ${y}px;" ></div>';


*/

var jstpl_gem='<div class="gem" id="gem_${id}" ></div>';

var jstpl_cardontable = '<div class="votecard" id="card_${card_id}" style="position:absolute; height: ${height}px; width: ${width}px; background-position:-${x}px -${y}px;z-index:${z};"></div>';

</script>  

{OVERALL_GAME_FOOTER}
