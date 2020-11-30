<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    //stake class variables
    protected $min_amount = 0.3 ;
    protected $max_amount = 10000;

    //selection class variable
    protected $min_selections = 1;
    protected $max_selections = 20;

    //odds class variables
    protected $min_odds = 1;
    protected $max_odds = 10000;

    //max win amount
    protected $max_win_amount = 20000;

    public function bet(Request $request) {

        $validate = json_decode($this->validateRequest($request));


        if (empty($validate)) {
            // store player details
            $stake = $request->input("stake_amount");
            $selections = json_decode($request->input("selections"));
            $player_id = $request->input("player_id");
            $player =new PlayerController;
            $player->createPlayer($player_id,$stake,$selections);
            \Cache::forget('key');
            return response()->json($validate,201);
        }else {
            \Cache::forget('key');
            return response()->json($validate,401);
        }
    }


    public function validateRequest($request) {
        $response = [];

        try {

            if (\Cache::has('key')) {
                $response["errors"][] = ["code"=>10,"message"=>"Your previous action is not finished yet"];
                return $response;
            }else {
                $token = \Str::random(40);
                \Cache ::put("key", $token , 15);
            }


            //check json request is an empty
            if (empty($request->except('_token'))) {
                $response["errors"][] = ["code"=>0,"message"=>"Unknown error"];
            }else if ($request->has("player_id") && $request->has("selections") && $request->has("selections")) {

                //check input is numeric or not
                if (is_numeric($request->input("stake_amount"))) {
                    $stake = (float) $request->input("stake_amount");
                }else {
                    throw new \Exception("Stake amount is not a number");
                }

                //check input is numeric or not
                if (is_numeric($request->input("player_id"))) {
                    $player_id = (float) $request->input("player_id");
                }else {
                    throw new \Exception("Player Id is not a number");
                }

                $selections = json_decode($request->input("selections"));

                //check stake values
                if ($stake < $this->min_amount) {
                    $response["errors"][] = ["code"=>2,"message"=>"Minimum stake amount is :".__($this->min_amount)];
                }else if ($stake > $this->max_amount) {
                    $response["errors"][] = ["code"=>3,"message"=>"Maximum stake amount is :".__($this->max_amount)];
                }

                //check selections
                if (count($selections) < $this->min_selections) {
                    $response["errors"][] = ["code"=>4,"message"=>"Minimum number of selections is :".__($this->min_selections)];
                }else if (count($selections) > $this->max_selections) {
                    $response["errors"][] = ["code"=>5,"message"=>"Maximum number of selections is :".__($this->max_selections)];
                }

                $max_win_amount = $stake;

                $duplicates = $this->checkDuplicateSelections($selections);
                foreach ($selections as $selection) {
                    $temp= [];

                    if (!is_numeric($selection->odds) || !is_numeric($selection->id)) {
                        $temp[] = ["code"=>0,"message"=>"Unknown error"];
                    }

                    if ($selection->odds < $this->min_odds) {
                        $temp[] = ["code"=>6,"message"=>"Minimum odds are :".__($this->min_odds)];
                    }

                    if ($selection->odds > $this->max_odds) {
                        $temp[] = ["code"=>7,"message"=>"Maximum odds are :".__($this->max_odds)];
                    }

                    if (!empty($duplicates) && (in_array($selection->id,$duplicates))) {
                        $temp[] = ["code"=>8,"message"=>"Duplicate selection found"];
                    }

                    if (!empty($temp)) {
                        $response["selections"][] = ["id"=>$selection->id,"errors"=>$temp];
                    }

                    $max_win_amount *= $selection->odds;
                }

                //check sufficient balance
                $has_balance = $this->checkSufficientBalance($stake,$player_id);
                if (!$has_balance) {
                    $response["errors"][] = ["code"=>11,"message"=>"Insufficient balance"];
                }

                if ($max_win_amount > $this->max_win_amount) {
                    $response["errors"][] = ["code"=>9,"message"=>"Maximum win amount is :".__($this->max_win_amount)];
                }


            }else {
                $response["errors"][] = ["code"=>1,"message"=>"Betslip structure mismatch"];
            }

        }catch (\Exception $e) {
            $response["errors"][] = ["code"=>0,"message"=>"Unknown error"];
        }


        return json_encode($response);
    }

    //get duplicate elements of an array
    public function checkDuplicateSelections($selections) {
        $ids = [];
        foreach ($selections as $selection) {
            $ids[] = $selection->id;
        }
        return array_diff_assoc($ids, array_unique($ids));
    }


    public function checkSufficientBalance($stake,$player_id) {
        $player = Player::find($player_id);

        if ($player == null) {
            $current_balance = 1000.00;
        }else {
            $current_balance = $player->balance;
        }
        $balance = $current_balance - $stake;
        if ($balance > 0) {
            return true;
        }else {
            return false;
        }
    }
}
