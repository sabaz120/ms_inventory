<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\IngredientService;
class BuyIngredientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $ingredient;

    public function __construct($ingredient)
    {
        $this->ingredient=$ingredient;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ingredientService= new IngredientService();
        $result=$ingredientService->buyIngredients($this->ingredient);
        // \Log::info([
        //     "ingredient"=>$this->ingredient,
        //     "result"=>$result
        // ]);
        if(!$result->status){
            //Re-enqueue the job
            self::dispatch($this->ingredient)->delay(now()->addSeconds(10));
        }
    }
}
