<?php

namespace App\Http\Controllers;

use App\Models\Transit;
use App\Models\CheckIn;
use App\Models\Consumable;
use App\Models\CustomRequest;
use App\Models\Guest;
use App\Models\Meal;
use App\Models\MealRecord;
use App\Models\Room;
use App\Models\Scanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScanController extends Controller
{
    /**
     * Display the scan page
     * 
     * @param Request $request
     * @param Scanner $scanner
     * @return \Illuminate\View\View
     */
    public function scanPage(Request $request, Scanner $scanner)
    {
        return view('scanning.scan', compact('scanner'));
    }
    
    /**
     * Process the QR code scan
     * 
     * @param Request $request
     * @param Scanner $scanner
     * @return \Illuminate\Http\Response
     */
    public function processQrScan(Request $request, Scanner $scanner)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'scan_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid data provided'], 400);
        }

        try {
            // Decode the QR content
            $scanData = json_decode($request->scan_data, true);
            
            if (!$scanData || !isset($scanData['guest_id']) || !isset($scanData['room_id'])) {
                return response()->json(['error' => 'Invalid QR code format'], 400);
            }
            
            $guest = Guest::find($scanData['guest_id']);
            $room = Room::find($scanData['room_id']);
            
            if (!$guest || !$room) {
                return response()->json(['error' => 'Guest or room not found'], 404);
            }
            
            // Handle the scan based on scanner type
            switch ($scanner->type) {
                case 'door':
                    return $this->handleDoorScan($guest, $room, $scanner);
                case 'gate':
                    return $this->handleGateScan($guest, $room, $scanner);
                case 'consumable':
                    return $this->handleConsumableScan($guest, $room, $scanner);
				case 'restaurant':
					return $this->handleRestaurantScan($guest, $room, $scanner);
                default:
                    return response()->json(['error' => 'Invalid scanner type'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error processing QR code: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle door scanner type
     */
    private function handleDoorScan($guest, $room, $scanner)
    {
        // Find active check-in for this guest and room
        $checkIn = CheckIn::where('guest_id', $guest->id)
                        ->where('room_id', $room->id)
						//date of departure more than now
						->whereDate('date_of_departure', '>', Carbon::now())
						->orWhereNull('date_of_departure')
                        ->first();
                        
        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'No active check-in found for this guest and room',
                'action' => 'denied'
            ]);
        }
        
        // Log the door access (you may want to create a model for this)
		
		//check if the previous transit is checkin
		$previousTransit = Transit::where('guest_id', $guest->id)
						->where('room_id', $room->id)
						->orderBy('date_of_transit', 'desc')
						->first();
		if ($previousTransit && $previousTransit->transit_type == Transit::TRANSIT_TYPES['CHECKOUT']){
			$transit = new Transit();
			$transit->guest_id = $guest->id;
			$transit->room_id = $room->id;
			$transit->date_of_transit = Carbon::now();
			$transit->transit_type = Transit::TRANSIT_TYPES['CHECKIN'];
			$transit->save();
		}else{
			$transit = new Transit();
			$transit->guest_id = $guest->id;
			$transit->room_id = $room->id;
			$transit->date_of_transit = Carbon::now();
			$transit->transit_type = Transit::TRANSIT_TYPES['CHECKOUT'];
			$transit->save();
		}

        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'You have '.$transit->transit_type.' - Room ' . $room->room_no,
            'guest' => $guest->first_name . ' ' . $guest->last_name,
            'action' => 'door_access_granted'
        ]);
    }
    
    /**
     * Handle gate scanner type (check-in/check-out)
     */
    private function handleGateScan($guest, $room, $scanner)
    {
        // Find if there's an active check-in
        $checkIn = CheckIn::where('guest_id', $guest->id)
                        ->where('room_id', $room->id)
						->whereDate('date_of_departure', '>', Carbon::now())
						->orWhereNull('date_of_departure')
                        ->first();
        
        if ($checkIn) {
            // This is a checkout
            $checkIn->date_of_departure = Carbon::now();
            $checkIn->save();
            
            // Only update room status to available if no more active check-ins exist for this room
            $activeCheckIns = CheckIn::where('room_id', $room->id)
                ->where('id', '!=', $checkIn->id)
                ->whereNull('date_of_departure')
                ->count();
                
            if ($activeCheckIns === 0) {
                $room->status = 'available';
                $room->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Check-out completed for Room ' . $room->room_no,
                'guest' => $guest->first_name . ' ' . $guest->last_name,
                'action' => 'checkout'
            ]);
        } else {
            // No active check-in, let's create one
            $lastCheckIn = CheckIn::where('guest_id', $guest->id)
                                ->where('room_id', $room->id)
                                ->whereNotNull('date_of_departure')
                                ->orderBy('date_of_departure', 'desc')
                                ->first();
            
            if ($lastCheckIn && Carbon::parse($lastCheckIn->date_of_departure)->isToday()) {
                // If checked out today, treat as a re-checkin
                $checkIn = new CheckIn();
                $checkIn->guest_id = $guest->id;
                $checkIn->room_id = $room->id;
                $checkIn->date_of_arrival = Carbon::now();
                $checkIn->save();
                
                // Update room status to occupied
                $room->status = 'occupied';
                $room->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Re-check-in completed for Room ' . $room->room_no,
                    'guest' => $guest->first_name . ' ' . $guest->last_name,
                    'action' => 'checkin'
                ]);
            } else if ($room->status === 'maintenance') {
                return response()->json([
                    'success' => false,
                    'message' => 'Room ' . $room->room_no . ' is under maintenance',
                    'action' => 'denied'
                ]);
            } else {
                // Create a new check-in
                $checkIn = new CheckIn();
                $checkIn->guest_id = $guest->id;
                $checkIn->room_id = $room->id;
                $checkIn->date_of_arrival = Carbon::now();
                $checkIn->save();
                
                // Update room status to occupied
                $room->status = 'occupied';
                $room->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Check-in completed for Room ' . $room->room_no,
                    'guest' => $guest->first_name . ' ' . $guest->last_name,
                    'action' => 'checkin'
                ]);
            }
        }
    }
    
    /**
     * Handle consumable scanner type
     */
    private function handleConsumableScan($guest, $room, $scanner)
    {
        // Find active check-in for this guest and room
        $checkIn = CheckIn::where('guest_id', $guest->id)
                        ->where('room_id', $room->id)
                        ->whereDate('date_of_departure', '>', Carbon::now())
                        ->orWhereNull('date_of_departure')
                        ->first();
                        
        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'No active check-in found for this guest and room',
                'action' => 'denied'
            ]);
        }
        
        // Return JSON response with redirect information
        return response()->json([
            'success' => true,
            'message' => 'Redirecting to consumables page',
            'guest' => $guest->first_name . ' ' . $guest->last_name,
            'action' => 'redirect',
            'redirect_url' => route('consumables.page', ['guest' => $guest->id, 'room' => $room->id])
        ]);
    }
    
    /**
     * Handle restaurant scanner type
     */
    private function handleRestaurantScan($guest, $room, $scanner)
    {
        // Find active check-in for this guest
        $checkIn = CheckIn::where('guest_id', $guest->id)
						->whereDate('date_of_departure', '>', Carbon::now())
						->orWhereNull('date_of_departure')
                        ->first();
                        
        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'No active check-in found for this guest',
                'action' => 'denied'
            ]);
        }
        
        // Determine meal type based on current time
        $currentHour = Carbon::now()->hour;

		$meals = Meal::all();

		foreach ($meals as $meal) {
			
			$mealType = "LUNCH";
			$mealId = 1;
			
			foreach ($meals as $meal) {
				if (in_array(Carbon::now()->format('l'), $meal->week_day)) {
					if (Carbon::now()->between($meal->range_start, $meal->range_end)) {
						$mealType = $meal->meal_type;
						$mealId = $meal->id;
						break;
					}
				}
			}
			
			/*if ($currentHour >= 6 && $currentHour < 11) {
				$mealType = 'BREAKFAST';
			} elseif ($currentHour >= 11 && $currentHour < 16) {
				$mealType = 'LUNCH';
			} elseif ($currentHour >= 16 && $currentHour < 22) {
				$mealType = 'DINNER';
			} else {
				return response()->json([
					'success' => false,
					'message' => 'Restaurant is currently closed',
					'action' => 'denied'
				]);
			}*/
			
			// Check if the guest has already had this meal today
			$existingMeal = MealRecord::where('guest_id', $checkIn->guest_id)
									->where('room_id', $checkIn->room_id)
									->whereDate('date_of_transit', Carbon::today())
									->where('transit_type', $mealType)
									->first();
									
			if ($existingMeal) {
				return response()->json([
					'success' => false,
					'message' => 'You have already had ' . Meal::MEAL_TYPES[$mealType] . ' today',
					'action' => 'denied'
				]);
			}
			
			// Create a new meal record
			$mealRecord = new MealRecord();
			$mealRecord->guest_id = $checkIn->guest_id;
			$mealRecord->room_id = $checkIn->room_id;
			$mealRecord->meal_id = $mealId;
			$mealRecord->date_of_transit = Carbon::now();
			$mealRecord->transit_type = $mealType;
			$mealRecord->save();
			
			return response()->json([
				'success' => true,
				'message' => Meal::MEAL_TYPES[$mealType] . ' recorded successfully',
				'guest' => $guest->first_name . ' ' . $guest->last_name,
				'action' => 'mealed'
			]);
    	}
	
	}
    
    /**
     * Show consumables page for a guest
     */
    public function consumablesPage(Guest $guest, Room $room, Scanner $scanner)
    {
        // Find active check-in for this guest and room
        $checkIn = CheckIn::where('guest_id', $guest->id)
                        ->where('room_id', $room->id)
						->whereDate('date_of_departure', '>', Carbon::now())
						->orWhereNull('date_of_departure')
                        ->first();
                        
        if (!$checkIn) {
            return view('scanning.error', [
                'message' => 'No active check-in found for this guest and room'
            ]);
        }
        
        // Get all available consumables that are marked as visible
        $consumables = Consumable::where('is_visible', true)->get();
        
        // Get current consumables for this check-in
        //$currentConsumables = $checkIn->consumables()->get();
        
        return view('scanning.consumables', compact('guest', 'room', 'checkIn', 'consumables'));
    }
    
    /**
     * Process consumable request
     */
    public function requestConsumable(Request $request, CheckIn $checkIn)
    {
        $validator = Validator::make($request->all(), [
            'consumable_id' => 'required|exists:consumables,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        
        $consumableId = $request->consumable_id;
        $quantity = $request->quantity;

		$customRequest = CustomRequest::updateOrCreate([
			'guest_id' => $checkIn->guest_id,
			'room_id' => $checkIn->room_id,
			'consumable_id' => $consumableId,
		], [
			'quantity' => $quantity,
			'status' => 'pending',
			'request_type' => 'consumable',
		]);
        
        // Check if consumable already exists for this check-in
        //$existingConsumable = $checkIn->consumables()->where('consumable_id', $consumableId)->first();
        
        //if ($existingConsumable) {
            // Update quantity
            //$newQuantity = $existingConsumable->pivot->quantity + $quantity;
            //$checkIn->consumables()->updateExistingPivot($consumableId, ['quantity' => $quantity]);
        //} else {
            // Add new consumable
          //  $checkIn->consumables()->attach($consumableId, ['quantity' => $quantity]);
        //}
        
        return response()->json([
            'success' => true,
            'message' => 'Consumable request submitted successfully'
        ]);
    }
}