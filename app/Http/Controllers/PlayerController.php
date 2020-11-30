<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\BetSelection;
use App\Models\BalanceTransaction;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createPlayer($player_id, $stake, $selections)
    {

        //add bet details
        $this->addBetDetails($stake, $selections);

        $player = Player::where('player_id', $player_id)->first();
        if ($player != null) {
            $this->updatePlayer($player_id, $stake);
        } else {
            Player::create(["player_id"=> $player_id]);

            //add bet transaction
            BalanceTransaction::create([
                "player_id" => $player_id,
                "amount"=> $stake,
                "amount_before" => 1000.0
            ]);

            //update Player amount
            $pl = Player::find($player_id);
            $pl->balance = 1000.0 - $stake;
            $pl->save();
        }
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\Response
     */
    public function updatePlayer($player_id, $stake)
    {
        $player = Player::find($player_id);

        $current_balance = $player->balance;

        $player->balance = $current_balance - $stake;
        $player->save();

        //add bet transaction
        BalanceTransaction::create([
            "player_id" => $player_id,
            "amount"=> $stake,
            "amount_before" => $current_balance
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Player  $player
     * @return \Illuminate\Http\Response
     */
    public function addBetDetails($stake, $selections)
    {
        //Add bet details
        $bet = Bet::create(["stake_amount"=>$stake]);

        //add odds
        foreach ($selections as $selection) {
            BetSelection::create([
                "bet_id"=>$bet->id,
                "selection_id" => $selection->id,
                "odds"=>$selection->odds
            ]);
        }
    }
}

