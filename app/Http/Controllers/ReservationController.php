<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function dashboard()
    {
        return view('dashboard');
    }

    public function detail($id)
    {
        $event = Event::findOrFail($id);
        $reservedPeople = DB::table('reservations')
        ->select('event_id', DB::raw('sum(number_of_people) as number_of_people'))
        ->whereNull('canceled_date')
        ->groupBy('event_id')
        ->having('event_id', $event->id )
        ->first();

        if(!is_null($reservedPeople)) {
            $resevablePeople = $event->max_people - $reservedPeople->number_of_people;
        } else {
            $resevablePeople = $event->max_people;
        }

        return view('event-detail', compact('event', 'resevablePeople'));
    }

    public function reserve(Request $request)
    {
        $event = Event::findOrFail($request->id);   // フォームに入力した値を受け取れる（input type="hidden"） イベントid
        $reservedPeople = DB::table('reservations')
        ->select('event_id', DB::raw('sum(number_of_people) as number_of_people'))
        ->whereNull('canceled_date')
        ->groupBy('event_id')
        ->having('event_id', $request->id ) // フォームに入力した値を受け取れる（input type="hidden"） イベントid
        ->first();

        // $reservedPeopleが空（予約がない） or  最大定員 >= 予約された人数 + 入力された人数
        if(is_null($reservedPeople) || $event->max_people >= $reservedPeople->number_of_people + $request->reserved_people) {
            // 上の条件を満たせば、予約可能
            Reservation::create([
                'user_id' => Auth::id(),
                'event_id' => $request->id,
                'number_of_people' => $request->reserved_people,
            ]);
            // fillableをチェック

            session()->flash('status', '登録OKです');
            return to_route('dashboard');
        } else {
            session()->flash('status', 'この人数は予約できません。');
            return view('dashboard');
        }
    }
}