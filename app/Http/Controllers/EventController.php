<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    use ApiResponse;
    // Crud Heula
    public function EVENT($size = 10, $page = 1, $search = '', $eo_id = '')
    {
        $query = DB::table('events as e')
            ->join('users as u', 'e.organizer_id', '=', 'u.id')
            ->select(
                'e.id',
                'e.organizer_id',
                'e.title',
                'e.banner',
                'e.banner_name',
                'e.description',
                'e.start_date',
                'e.location',
                'e.created_at',
                'e.updated_at',
                'u.name as event_organizer'
            );
        if (!empty($eo_id)) {
            $query->where('e.organizer_id', '=', $eo_id);
        }
        if (!empty($search)) {
            $query->where('e.title', 'LIKE', "%$search%");
        }

        $data = $query->orderBy('e.start_date', 'DESC')->paginate($size, ['*'], 'page', $page);
        return $data;
    }

    public function getEvents(Request $request)
    {
        try {
            $data = $this->EVENT(
                $request->size ?? 10,
                $request->page ?? 1,
                $request->search ?? ''
            );
            foreach ($data as $event) {
                if ($event->banner) {
                    $event->banner_url = url('storage/event_banners/' . $event->banner_name);
                }
            }
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getEventsByEO(Request $request)
    {
        try {
            $data = $this->EVENT(
                $request->size ?? 10,
                $request->page ?? 1,
                $request->search ?? '',
                Auth::id()

            );
            foreach ($data as $event) {
                if ($event->banner) {
                    $event->banner_url = url('storage/event_banners/' . $event->banner_name);
                }
            }
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function getEventById($id)
    {
        try {
            $data = DB::table('events as e')
                ->join('users as u', 'e.organizer_id', '=', 'u.id')
                ->select(
                    'e.id',
                    'e.organizer_id',
                    'e.title',
                    'e.banner',
                    'e.banner_name',
                    'e.description',
                    'e.start_date',
                    'e.location',
                    'e.created_at',
                    'e.updated_at',
                    'u.name as event_organizer'
                )->where('e.id', '=', $id)->first();
                if ($data) {
                    if ($data->banner) {
                        $data->banner_url = url('storage/event_banners/' . $data->banner_name);
                    }
                    return $this->successResponse($data);
                } else{
                    return $this->errorResponse('Not Found',404);
                }
           
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function createEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'bannner' => 'nullable|file',
            'description' => 'required|string',
            'location' => 'required|string',
            'start_date' => 'required'
        ]);


        if ($request->hasFile('banner')) {
            $banner = $request->file('banner')->store('event_banners', 'public');
            $file_name = basename($banner);
        } else {
            $file_name = null;
        }

        try {
            DB::table('events')->insert([
                'organizer_id' => Auth::id(),
                'title' => $request->title,
                'banner_name' => $file_name,
                'banner' => $banner,
                'description' => $request->description,
                'location' => $request->location,
                // 'start_date' => $request->start_date,
                'start_date' =>  Carbon::now(),
                'created_at' => Carbon::now(),
            ]);
            return $this->successResponse([], 'Success', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function editEvent(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'banner' => 'nullable|file',
            'description' => 'required|string',
            'location' => 'required|string',
            'start_date' => 'required|date'
        ]);

        try {
            $event = DB::table('events')->where('id', $id)->first();
            if (!$event) {
                return $this->errorResponse('Event not found', 404);
            }

            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'start_date' => $request->start_date,
                'updated_at' => Carbon::now(),
            ];

            if ($request->hasFile('banner')) {
                // Delete old banner if it exists
                if ($event->banner) {
                    Storage::disk('public')->delete($event->banner);
                }

                // Upload new banner
                $banner = $request->file('banner')->store('event_banners', 'public');
                $updateData['banner_name'] = basename($banner);
                $updateData['banner'] = $banner;
            }

            DB::table('events')->where('id', $id)->update($updateData);

            return $this->successResponse([], 'Event updated successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function deleteEvent($id)
    {
        try {
            // Fetch the event details
            $event = DB::table('events')->where('id', $id)->first();

            // Check if event exists
            if (!$event) {
                return $this->errorResponse('Event not found', 404);
            }

            // Delete banner file if it exists
            if ($event->banner) {
                Storage::disk('public')->delete($event->banner);
            }

            // Delete the event from database
            DB::table('events')->where('id', $id)->delete();

            return $this->successResponse([], 'Event deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
