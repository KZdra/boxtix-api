<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function getLatestEvent(Request $request)
{
    $subquery = DB::table('tickets')
        ->select('event_id', DB::raw('MIN(price) as price'))
        ->groupBy('event_id');

    $query = DB::table('events as e')
        ->join('users as u', 'e.organizer_id', '=', 'u.id')
        ->leftJoinSub($subquery, 't', function ($join) {
            $join->on('e.id', '=', 't.event_id');
        })
        ->select(
            'e.id',
            'e.organizer_id',
            'e.title',
            'e.banner',
            'e.banner_name',
            'e.description',
            'e.start_date',
            'e.location',
            'e.slug',
            'u.name as event_organizer',
            't.price' // harga tiket termurah
        );

    $data = $query->orderBy('e.start_date', 'DESC')->limit(20)->get();

    foreach ($data as $event) {
        if ($event->banner) {
            $event->banner_url = url('storage/event_banners/' . $event->banner_name);
        }
    }

    return $this->successResponse($data);
}

    
}
