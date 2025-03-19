<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\{
    Inventory,
    Purchase
};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use App\Jobs\BuyIngredientJob;
use Validator;
class InventoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $take=$request->input('take',10);
            $ingredient_like=$request->input('ingredient_like',null);
            $query=Inventory::query();
            if($ingredient_like){
                $query->where("name","LIKE","%$ingredient_like%");
            }
            $query->orderBy("ingredient","ASC");
            $query=$query->paginate($take);
            return $this->pagination($query);
        } catch (Exception $e) {
            \Log::error([
                "message"=>$e->getMessage(),
                "line"=>$e->getLine(),
            ]);
            return $this->error('Ocurrió un error al intentar obtener el listado', 500);
        }
    }

    public function show($ingredient)
    {
        try {
            $query=Inventory::whereIngredient($ingredient)->first();
            if(!$query){
                return $this->error('No se encontró el registro', 404);
            }
            return $this->success($query);
        } catch (Exception $e) {
            \Log::error([
                "message"=>$e->getMessage(),
                "line"=>$e->getLine(),
            ]);
            return $this->error('Ocurrió un error al intentar obtener el registro', 500);
        }
    }

    public function request(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'ingredients' => 'required|array',
                'ingredients.*.name' => 'required|string|exists:inventories,ingredient',
                'ingredients.*.quantity' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return $this->error('Datos inválidos', 400,$validator->errors());
            }
            $processedIngredients=[];
            foreach($request->ingredients as $ingredient){
                $ingredientName=$ingredient['name'];
                $requestedQuantity=$ingredient['quantity'];
                $inventoryItem = Inventory::whereIngredient($ingredientName)->first();
                if($inventoryItem){
                    if($inventoryItem->quantity < $requestedQuantity){
                        BuyIngredientJob::dispatch($ingredientName);
                        break;
                    }
                    $processedIngredients[]=[
                        "ingredientModel"=>$inventoryItem,
                        "quantity"=>$requestedQuantity,
                    ];
                }else{
                    throw new Exception("No se encontró el ingrediente $ingredientName en el inventario",404);
                }
            }//foreach
            if(count($processedIngredients)==count($request->ingredients)){
                foreach($processedIngredients as $inventoryItem){
                    $inventoryItem['ingredientModel']->decrement('quantity', $inventoryItem['quantity']);
                }//foreach
                return $this->success(['message'=>'Ingredientes solicitados procesados']);
            }else{
                return $this->error('El inventario no dispone actualmente de todos los ingredientes', 400);
            }
        }catch(Exception $e){
            \Log::error([
                "message"=>$e->getMessage(),
                "file"=>$e->getFile(),
                "line"=>$e->getLine(),
            ]);
            return $this->error('Ocurrió un error al intentar procesar la solicitud', 500);
        }
    }
}