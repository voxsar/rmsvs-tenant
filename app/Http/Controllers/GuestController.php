<?php

namespace App\Http\Controllers;

use App\Models\Guest;

class GuestController extends Controller
{
    /**
     * Display the specified guest.
     * This method is used for QR code scanning validation.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Guest $guest)
    {
        // Return guest information
        // In a real application, you might want to implement some authentication/authorization here
        return response()->json([
            'guest' => $guest,
            'rooms' => $guest->rooms,
            'checkIns' => $guest->checkIns,
        ]);
    }
}
