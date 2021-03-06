<?php

namespace App\Http\Controllers\API;

use Validator;
use App\Http\Controllers\Controller;
use App\Product;
use App\Purchase;
use App\Card;
use Illuminate\Http\Request;

class PurchasesController extends Controller
{
    public function all()
    {
        $purchases = Purchase::all();

        $results = [];
        foreach ($purchases as $purchase)
        {
            $items = [];

            foreach ($purchase->products as $product)
            {
                $items[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $product->pivot->quantity
                ];
            }

            $results[] = [
                'id' => $purchase->id,
                'user' => null,
                'reduction' => 0,
                'final_price' => $purchase->final_price,
                'date' => $purchase->created_at->toDateTimeString(),
                'products' => $items,
            ];
        }

        return $results;
    }

    public function purchaseCard(Request $request){
        $validator = Validator::make($request->all(), [
            'card' => 'required',
            'totalPrice' => 'required',
        ]);

        if($validator->fails())
            return response(['success' => false, 'errors' => $validator->errors()], 500);

        $cardString = $request->get('card');
        $totalPrice = $request->get('totalPrice');
        $cardDecode = json_decode($cardString);

        $card = Card::findOrFail($cardDecode->id);

        if($card->coin < $totalPrice)
            return response(['success' => false, 'errors' => 'Pas assez de coin sur la carte. Actuellement sur la carte : ' . strval($card->coin)]);

	    $card->coin -= $totalPrice;
	    $card->save();
        return $this->purchase($request);  

    }
    public function purchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required',
        ]);

        if ($validator->fails())
            return response(['success' => false, 'errors' => $validator->errors()], 500);

        $items = $request->get('items', []);

        $purchase = new Purchase;
        $purchase->reduction = 0;
        $purchase->final_price = 0;
        $purchase->save();

        foreach ($items as $_item)
        {
            $item = json_decode($_item);

            if (!isset($item->id, $item->quantity))
                abort(500);

            $product = Product::findOrFail($item->id);

            if ($item->quantity != 0)
            {
                $purchase->products()->attach($product->id, [
                    'quantity' => $item->quantity
                ]);

                $purchase->final_price += $item->quantity * $product->price;
            }
        }

        $purchase->save();

        return ['success' => true];
    }
}
