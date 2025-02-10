<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    use ApiResponse;
    // Crud Heula
    public function createEvent(Request $request)
    {
        
        $request->validate([
            'title' => 'required|string',
            'bannner' => 'nullable|file',
            'description' => 'required|string',
            'location' => 'required|string',
            'start_date' => 'required'
        ]);
       
        return response()->json(['status'=> 1,'message'=>'data_get','data'=>$request->all()],200);

        // try {
        //     DB::table('events')->insert([
        //         'organizer_id' => 1,
        //         'title' => $request->title,
        //         'banner' => $request->banner,
        //         'description' => $request->description,
        //         'location' => $request->location,
        //         // 'start_date' => $request->start_date,
        //         'start_date' =>  Carbon::now(),
        //         'created_at' => Carbon::now(),
        //     ]);
        //     return $this->successResponse([], 'Success', 201);
        // } catch (\Exception $e) {
        //     return $this->errorResponse($e->getMessage());
        // }
    }
    // public function getEvents(Request $request){
    //     try {
    //         DB::table('events')->where(
    //     } catch (\Throwable $th) {
    //         //throw $th;
    //     }
    // }
}
