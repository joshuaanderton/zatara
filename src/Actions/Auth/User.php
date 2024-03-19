<?php

namespace Zatara\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsAction;

class User
{
    use AsAction;

    public function handle(Request $request)
    {
        return response()->json([
            'check' => (bool) ($user = $request->user()),
            'user' => $user,
            'team' => $team = $user?->currentTeam,
            'permissions' => ! $team ? [] : [
                'canAddTeamMembers' => Gate::check('addTeamMember', $team),
                'canDeleteTeam' => Gate::check('delete', $team),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $team),
                'canUpdateTeam' => Gate::check('update', $team),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $team),
            ],
        ], 200);
    }
}
