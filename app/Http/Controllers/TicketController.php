<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{

    public function generateTicketPrefix($event_id)
    {
        // Ambil nama event berdasarkan event_id
        $event = DB::table('events')->where('id', $event_id)->first();

        if (!$event) {
            return $this->errorResponse("Event not found.");
        }

        // Buat kode tiket berdasarkan nama event
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $event->title), 0, 3));

        // Generate kode tiket baru
        $ticketprefix = "{$prefix}-";
        return $ticketprefix;
    }
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

        $data = $query->orderBy('tc.created_at', 'DESC')->paginate($size, ['*'], 'page', $page);
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
                ->select(
                    'tc.id',
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
            $isExist = DB::table("ticket_categories")->where("id", '=', $id)->exists();
            if ($isExist) {
                DB::table("ticket_categories")->where("id", '=', $id)->update([
                    "event_id" => $request->event_id,
                    "category_name" => $request->category_name,
                    "updated_at" => Carbon::now(),
                ]);
                return $this->successResponse([], 'Edited', 201);
            }
            return $this->errorResponse('NotFound OR gagal', 404);
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

    //Ticket Section
    public function TICKET($size = 10, $page = 1, $search = '', $event_id = '', $cat_id = '')
    {
        $query = DB::table('tickets as t')
            ->join('events as e', 't.event_id', '=', 'e.id')
            ->join('ticket_categories as tc', 't.category_id', '=', 'tc.id')
            ->select(
                't.id',
                't.event_id as event_id',
                'e.title as event_name',
                't.category_id as id_category',
                'tc.category_name as ticket_name ',
                't.ticket_code as ticket_code',
                't.status',
                't.price',
                't.stock',
                't.created_at',
                't.updated_at',
            );
        if (!empty($event_id)) {
            $query->where('t.event_id', '=', $event_id);
        }
        if (!empty($cat_id)) {
            $query->where('t.category_id', '=', $cat_id);
        }
        if (!empty($search)) {
            $query->where('tc.category_name', 'LIKE', "%$search%")
                ->orWhere('e.title', 'LIKE', "%$search%");
        }

        $data = $query->orderBy('t.created_at', 'DESC')->paginate($size, ['*'], 'page', $page);
        return $data;
    }
    public function getTicketsByEventId(Request $request)
    {
        try {
            $data = $this->TICKET(
                $request->size ?? 10,
                $request->page ?? 1,
                $request->search ?? '',
                $request->event_id ?? '',
                $request->category_id ?? ''
            );
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function createTicket(Request $request)
    {
        try {
            $request->validate([
                'event_id' => 'required|integer',
                'category_id' => 'required|integer',
                'status' => 'required|string',
                'price' => 'required',
                'stock' => 'required',
            ]);
            // Get 3char for prefix Ticket Code
           $ticket_code= $this->generateTicketPrefix((int)$request->event_id);
            DB::table('tickets')->insert([
                'ticket_code' => $ticket_code,
                'event_id' => $request->event_id,
                'category_id' => $request->category_id,
                'status' => $request->status,
                'price' => $request->price,
                'stock' => $request->stock,
                'created_at' => Carbon::now(),
            ]);
            return $this->successResponse([]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateTicket(Request $request, $id)
    {
        try {
            $request->validate([
                'event_id' => 'required|integer',
                'category_id' => 'required|integer',
                'status' => 'required|string',
                'price' => 'required',
                'stock' => 'required',
            ]);

            DB::table('tickets')->where('id', '=', $id)->update([
                'event_id' => $request->event_id,
                'category_id' => $request->category_id,
                'status' => $request->status,
                'price' => $request->price,
                'stock' => $request->stock,
                'updated_at' => Carbon::now(),
            ]);
            return $this->successResponse([]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function deleteTicket($id){
        try {
            DB::table('tickets')->where('id', '=', $id)->delete();
            // TODO- Delete From ORdered Tickets
            return $this->successResponse([]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    // End Of Section Ticket
}
