<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponse;

    public function getStats(Request $request)
    {
        $role_id = Auth::user()->role_id;
        try {
            if ($role_id == 1) {
                $customersCount = DB::table('customers')->count();
                $eventsCount = DB::table('events')->count();
                $organizerCount = DB::table('users')->where('role_id', 2)->count();
                // Kontol Kuda
                $response = [
                    "customers_count" => $customersCount,
                    "events_count" => $eventsCount,
                    "organizer_count" => $organizerCount
                ];
                return $this->successResponse($response);
            } elseif ($role_id == 2) {
                $eventsCount = DB::table('events')->count();
                // Kontol Kuda
                $response = [
                    "customers_count" => null,
                    "events_count" => $eventsCount,
                    "organizer_count" => null
                ];
                return $this->successResponse($response);
            } else {
                return $this->successResponse([]);
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
