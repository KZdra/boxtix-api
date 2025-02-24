<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');
    }
    public function generateTicketNumber($t_id)
    {
        $ticket_id = DB::table('tickets')->where('id', $t_id)->first();

        if (!$ticket_id) {
            return $this->errorResponse("Ticket not found.");
        }

        // Buat kode tiket berdasarkan nama event
        $prefix = $ticket_id->ticket_code;

        // Ambil nomor terakhir dari tiket dengan prefix yang sama
        $latestTicket = DB::table('ordered_tickets')
            ->where('ticket_number', 'like', "{$prefix}-%")
            ->orderBy('ticket_number', 'desc')
            ->first();

        $lastNumber = 0;
        if ($latestTicket) {
            preg_match('/\d+$/', $latestTicket->ticket_number, $matches);
            $lastNumber = $matches ? (int)$matches[0] : 0;
        }

        // Generate kode tiket baru
        $ticketCode = "{$prefix}" . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return $ticketCode;
    }

    public function reqTokenBayar(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|numeric'
        ]);
        try {
            DB::beginTransaction();
            $ticket_data = DB::table('tickets as t')
                ->select(
                    't.id',
                    't.event_id as event_id',
                    // 'e.title as event_name',
                    // 't.category_id as id_category',
                    // 'tc.category_name as ticket_name ',
                    't.ticket_code as ticket_code',
                    't.status',
                    't.price',
                    't.stock',
                    't.created_at',
                    't.edited_at',
                )->where('t.event_id', '=', $request->event_id)->where('t.category_id', '=', $request->category_id)->where('t.id', '=', $request->ticket_id)->first();

            $cust_id = DB::table('customers')->insertGetId([
                'customer_first_name' => $request->first_name,
                'customer_last_name' => $request->last_name,
                'customer_phone' => $request->phone,
                'customer_email' => $request->email
            ]);

            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . mt_rand(1000, 9999);
            DB::table('orders')->insert([
                'id' => Str::uuid(),
                'order_id' => $orderNumber,
                'customer_id' => $cust_id,
                'ticket_id' => $ticket_data->id,
                'total_price' => $ticket_data->price,
                'created_at'=> Carbon::now()
            ]);
            DB::commit();
            $params = [
                'transaction_details' => [
                    'order_id' => $orderNumber,
                    'gross_amount' => $ticket_data->price,
                ],
                'customer_details' => [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ],
            ];

            $snapToken = Snap::getSnapToken($params);
            return $this->successResponse($snapToken);
        } catch (\Exception $e) {
            DB::rollBack();
            //dev
            return $this->errorResponse($e->getMessage(), 500);
            //prod
            // return $this->errorResponse('Terjadi Kesalahan Server',50s0);
        }
    }
}
