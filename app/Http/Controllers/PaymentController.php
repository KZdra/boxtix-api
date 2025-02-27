<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Notification;
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
    public function decresaseStokTicket($tix_id)
    {
        // Ambil stok saat ini
        $ticket = DB::table('tickets')->where('id', $tix_id)->first();

        if ($ticket && $ticket->stock > 0) {
            // Kurangi stok
            DB::table('tickets')
                ->where('id', $tix_id)
                ->update(['stock' => $ticket->stock - 1]);
            return true;
        } else {
            return false;
        }
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
        $ticketCode = "{$prefix}" . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
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
                ->join('events as e', 't.event_id', '=', 'e.id')
                ->join('ticket_categories as tc', 't.category_id', '=', 'tc.id')
                ->select(
                    't.id',
                    't.event_id as event_id',
                    'e.title as event_name',
                    't.category_id as id_category',
                    'tc.category_name as ticket_name',
                    't.ticket_code as ticket_code',
                    't.status',
                    't.price',
                    't.stock',
                )->where('t.event_id', '=', $request->event_id)->where('t.category_id', '=', $request->category_id)->where('t.id', '=', $request->ticket_id)->first();
            $cust_id = DB::table('customers')->insertGetId([
                'customer_first_name' => $request->first_name,
                'customer_last_name' => $request->last_name,
                'customer_phone' => $request->phone,
                'customer_email' => $request->email
            ]);

            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . mt_rand(1000, 9999);
            $genUUID = Str::uuid();
            DB::table('orders')->insert([
                'id' => $genUUID,
                'order_no' => $orderNumber,
                'customer_id' => $cust_id,
                'ticket_id' => $ticket_data->id,
                'total_price' => $ticket_data->price,
                'created_at' => Carbon::now()
            ]);
            DB::commit();
            $params = [
                'transaction_details' => [
                    'order_id' => $genUUID,
                    'gross_amount' => $ticket_data->price,
                ],
                'item_details' => [
                    [
                        'id' => $ticket_data->id,
                        'price' => $ticket_data->price,
                        'quantity' => 1,
                        'name' => $ticket_data->event_name . '-' . $ticket_data->ticket_name,
                    ],
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
            // return $this->errorResponse('Terjadi Kesalahan Server',500);
        }
    }
    public function handleAfterPayment(Request $request)
    {
        $notif = new Notification();
        $serverkey = config('services.midtrans.serverKey');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverkey);
        Log::info($request->all());
        if ($hashed == $request->signature_key) {
            $transaction = $notif->transaction_status;
            $type = $notif->payment_type;
            $order_id = $notif->order_id;
            DB::beginTransaction();
            try {
                DB::table('log_transactions')->insert([
                    'order_id' => $order_id,
                    'status' =>  $transaction,
                    'type' => $type,
                    'payload' => json_encode($request->all()),
                    'created_at' => Carbon::now(),
                ]);
                switch ($transaction) {
                    case 'capture':
                        $required_id = DB::table('orders')->where('id', $order_id)->select('ticket_id', 'customer_id', 'id as order_id')->first();
                        DB::table('orders')->where('id', $order_id)->update([
                            'order_status' => 'paid',
                            'updated_at' => Carbon::now(),
                        ]);
                        $ordtxID = DB::table('ordered_tickets')->insertGetId([
                            'ticket_id' => $required_id->ticket_id,
                            'order_id' => $order_id,
                            'customer_id' => $required_id->customer_id,
                            'ticket_number' => $this->generateTicketNumber((int)$required_id->ticket_id),
                            'status' => 'not_used',
                            'created_at' => Carbon::now(),
                        ]);
                        if ($ordtxID) {
                            $this->decresaseStokTicket($required_id->ticket_id);
                        }
                        break;

                    case 'settlement':
                        $required_id = DB::table('orders')->where('id', $order_id)->select('ticket_id', 'customer_id', 'id as order_id')->first();
                        DB::table('orders')->where('id', $order_id)->update([
                            'order_status' => 'paid',
                            'updated_at' => Carbon::now(),
                        ]);
                        $ordtxID = DB::table('ordered_tickets')->insertGetId([
                            'ticket_id' => $required_id->ticket_id,
                            'order_id' => $order_id,
                            'customer_id' => $required_id->customer_id,
                            'ticket_number' => $this->generateTicketNumber((int)$required_id->ticket_id),
                            'status' => 'not_used',
                            'created_at' => Carbon::now(),
                        ]);
                        if ($ordtxID) {
                            $this->decresaseStokTicket($required_id->ticket_id);
                        }
                        break;

                    case 'pending':
                        DB::table('orders')->where('id', $order_id)->update([
                            'order_status' => 'pending',
                            'updated_at' => Carbon::now(),
                        ]);
                        echo "Waiting customer to finish transaction order_id: " . $order_id . " using " . $type;
                        break;

                    case 'deny':
                        DB::table('orders')->where('id', $order_id)->update([
                            'order_status' => 'failed',
                            'updated_at' => Carbon::now(),
                        ]);
                        $cus_id = DB::table('orders')->where('id', $order_id)->select('customer_id', 'id as order_id')->first();
                        DB::table('customers')->where('id', $cus_id)->delete();
                        break;

                    case 'expire':
                        DB::table('orders')->where('id', $order_id)->update([
                            'status' => 'expired',
                            'updated_at' => Carbon::now(),
                        ]);
                        $cus_id = DB::table('orders')->where('id', $order_id)->select('customer_id', 'id as order_id')->first();
                        DB::table('customers')->where('id', $cus_id)->delete();
                        break;

                    case 'cancel':
                        DB::table('orders')->where('id', $order_id)->update([
                            'status' => 'canceled',
                            'updated_at' => Carbon::now(),
                        ]);
                        $cus_id = DB::table('orders')->where('id', $order_id)->select('customer_id', 'id as order_id')->first();
                        DB::table('customers')->where('id', $cus_id)->delete();
                        break;

                    default:
                        echo "Unknown transaction status.";
                        break;
                }
                DB::commit();
                return $this->successResponse([]);
            } catch (\Exception $th) {
                return $this->errorResponse($th->getMessage());
            }
        }
    }
}
