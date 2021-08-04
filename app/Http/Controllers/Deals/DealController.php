<?php

namespace App\Http\Controllers\Deals;

class DealController extends Controller
{
    public function checkExistActiveDealPrice(Request $request)
    {
        $prices = Deal::find($request->post('id_deal'))->prices()->get();
        $newStartDate = Carbon::parse($request->post('start_date'))->format('Y-m-d');

        if( $prices->count() == 0 ) {
            return collect([ "code" => 1 ])->toJson();
        }

        if( $prices->count() == 1 ) {
            return $this->checkIfTheDateIslowerOrLaterOrEqualToTheRecordInTheTable($newStartDate, $prices->first())->toJson();
        }

        $newDateBetweenSomePrice = Price::whereRaw('"'.Carbon::parse($request->post('start_date'))->format('Y-m-d').'" BETWEEN arm_deals_prices.start_date AND arm_deals_prices.end_date')->where('id_deal', $request->post('id_deal'));

        if( $newDateBetweenSomePrice->count() == 0 ) {
            $activePrices = Deal::find($request->post('id_deal'))->prices->where('end_date', '0000-00-00');
            return $this->checkIfTheDateIslowerOrLaterOrEqualToTheRecordInTheTable($newStartDate, $activePrices->first())->toJson();
        }

        if( $newDateBetweenSomePrice->count() == 1 ) {
            $nextPriceToNew = Price::where('id_deal', $request->post('id_deal'))->where('start_date', '>=', $newStartDate)->orderBy('start_date', 'ASC')->get()->first();
            $newDateBetweenSomePrice = $newDateBetweenSomePrice->first();

            if ($newStartDate == $newDateBetweenSomePrice->start_date) {
                return collect([
                    "code" => 0
                ])->toJson();
            }

            return collect([
                    "code" => 4,
                    "old_end_date" => Carbon::parse($request->post('start_date'))->subHours(24)->format('d-m-Y'),
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
                return collect([
                    "code" => 0
                ]);
            }
        } else{
            return collect([ "code" => 1 ]);
        }
    }
}
