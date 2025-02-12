<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    //Ticket Kategori Section
    public function TICKETCATEGORIES($size = 10, $page = 1, $search = '', $event_id = '')
    {
        $query = DB::table('ticket_categories as tc')
            ->join('events as e', 'tc.event_id', '=', 'e.id')
            ->select(
                'tc.id',
                'tc.category_name',
                'tc.created_at',
                'tc.updated_at',
                'e.id as event_id',
                'e.title as event_name'
            );

        if (!empty($event_id)) {
            $query->where('tc.event_id', '=', $event_id);
        }
        if (!empty($search)) {
            $query->where('tc.category_name', 'LIKE', "%$search%");
        }

        $data = $query->orderBy('e.created_at', 'DESC')->paginate($size, ['*'], 'page', $page);
        return $data;
    }
    public function getTicketCategories(Request $request)
    {
        try {
            $data = $this->TICKETCATEGORIES(
                $request->size ?? 10,
                $request->page ?? 1,
                $request->search ?? ''
            );
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function getTicketCategoryById($ticket_category_id)
    {
        try {
            $query = DB::table('ticket_categories as tc')
                ->join('events as e', 'tc.event_id', '=', 'e.id')
                ->select('tc.id',
                'tc.category_name',
                'tc.created_at',
                'tc.updated_at',
                'e.id as event_id',
                'e.title as event_name'
                )->where('tc.id', '=', $ticket_category_id)->first();
            return $this->successResponse($query);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function getTicketCategoryByEvents(Request $request)
    {
        try {
            $data = $this->TICKETCATEGORIES(
                $request->size ?? 10,
                $request->page ?? 1,
                $request->search ?? '',
                $request->event_id ?? ''
            );
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function addTicketCategories(Request $request)
    {
        $request->validate([
            "event_id" => "required|integer",
            "category_name" => "required|string",
        ]);
        try {
            DB::table("ticket_categories")->insert([
                "event_id" => $request->event_id,
                "category_name" => $request->category_name,
                "created_at" => Carbon::now(),
            ]);
            return $this->successResponse([], 'inserted', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function editTicketCategory(Request $request, $id)
    {
        $request->validate([
            "event_id" => "required|integer",
            "category_name" => "required|string",
        ]);
        try {
            $isExist = DB::table("ticket_categories")->where("id",'=', $id)->exists();
            if ($isExist){
                DB::table("ticket_categories")->where("id", '=', $id)->update([
                    "event_id" => $request->event_id,
                    "category_name" => $request->category_name,
                    "updated_at" => Carbon::now(),
                ]);
                return $this->successResponse([], 'Edited', 201);
            }
            return $this->errorResponse('NotFound OR gagal',404 );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function deleteTicketCategory(Request $request, $id)
    {
        try {
            DB::table("ticket_categories")->where("id", '=', $id)->delete();
            return $this->successResponse([], 'Deleted', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    // End Ticket Kategories Section
}
