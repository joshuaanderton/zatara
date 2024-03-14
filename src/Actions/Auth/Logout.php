<?php

namespace Zatara\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class Logout
{
    use AsAction;

    public function handle(Request $request)
    {
        Auth::logout();

        return response()->json([], 200);
    }
}
