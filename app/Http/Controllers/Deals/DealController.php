<?php

namespace App\Http\Controllers\Deals;

class DealController extends Controller
{
    public function checkExistActiveDealPrice(Request $request)
    {
		//recibimos dos variables id_deal y start_date y comprobamos si se reciben correctamente.
		if(is_null($request->post('id_deal')) || is_null($request->post('start_date'))){
			//Error. Devolveremos code = -1. He escogido -1 para diferenciar este error del del resto de code.
			 return collect([ "code" => - 1])->toJson();
			 exit;
		}else{
			$id = $request->post('id_deal');
			$s_date = $request->post('start_date');
		}
		
        $prices = Deal::find($id)->prices()->get();//Recibimos el precio
        $newStartDate = Carbon::parse($s_date)->format('Y-m-d');//Recibimos la fecha

		if($prices->count() < 1 ) {// comprobamos si hay resultados. 
            return collect([ "code" => 1 ])->toJson();
        }else { //Si existe precio, mandamos precio y fecha al metodo para comprobar si la fecha es <, > o = al guardado en la tabla
            return $this->checkIfTheDateIslowerOrLaterOrEqualToTheRecordInTheTable($newStartDate, $prices->first())->toJson();
        }
		
		//Buscamos el Price segun start_date en la tabla arm_deals_prices para un e id_deal.
        $newDateBetweenSomePrice = Price::whereRaw('"'.Carbon::parse($s_date)->format('Y-m-d').'" BETWEEN arm_deals_prices.start_date AND arm_deals_prices.end_date')->where('id_deal', $id);
		
		//Si no hay resultados para Price
        if( $newDateBetweenSomePrice->count() < 1 ) {
            $activePrices = Deal::find($id)->prices->where('end_date', '0000-00-00');
            return $this->checkIfTheDateIslowerOrLaterOrEqualToTheRecordInTheTable($newStartDate, $activePrices->first())->toJson();
        }else{//Si hay resultados para Price
            $nextPriceToNew = Price::where('id_deal', $id)->where('start_date', '>=', $newStartDate)->orderBy('start_date', 'ASC')->get()->first();
            $newDateBetweenSomePrice = $newDateBetweenSomePrice->first();

            if ($newStartDate == $newDateBetweenSomePrice->start_date) {
                return collect(["code" => 0 ])->toJson();
            }

            return collect([
                    "code" => 4,
                    "old_end_date" => Carbon::parse($s_date)->subHours(24)->format('d-m-Y'),
                    "end_date" => Carbon::parse($nextPriceToNew->start_date)->subHours(24)->format('d-m-Y'),
                    "id_update_to_price_x" => $newDateBetweenSomePrice->id_deal_price,
                    "id_update_to_price_y" => $nextPriceToNew->id_deal_price
                ])->toJson();
        }

        return collect([ "code" => 5 ])->toJson();
    }

    private function checkIfTheDateIslowerOrLaterOrEqualToTheRecordInTheTable($newStartDate, $price)
    {
        if( !is_null($price)) {
            if ( $newStartDate < $price->start_date ) {
                return collect([
                        "code" => 2,
                        "new_end_date" => Carbon::parse($price->start_date)->subHours(24)->format('d-m-Y'),
                        "id_update_to_price_x" => $price->id_deal_price
                    ]);
            }else if ( $newStartDate > $price->start_date ) {
                return collect([
                    "code" => 3,
                    "old_end_date" => Carbon::parse($newStartDate)->subHours(24)->format('d-m-Y'),
                    "id_update_to_price_x" => $price->id_deal_price
                ]);
            } else {
                return collect(["code" => 0 ]);
            }
        } else{
            return collect([ "code" => 1 ]);
        }
    }
}
