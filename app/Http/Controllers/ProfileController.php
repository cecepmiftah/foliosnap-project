<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware(['auth', 'can:update,user'], ['edit', 'update', 'updateAvatar']),
        ];
    }

    public function show(User $user)
    {
        $user->load([
            'portfolios',
            'workExperiences' => function ($query) {
                $query->latest();
            },
            'likedPortfolios.portfolio',
        ]);
        return inertia('Profile/Show', [
            'user' => $user
        ]);
    }

    public function edit(User $user)
    {
        return inertia('Profile/Edit', [
            'user' => $user->load([
                'workExperiences' => function ($query) {
                    $query->latest();
                }
            ])
        ]);
    }

    public function update(User $user, Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'username' => 'required|string|alpha_dash|max:30|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'about' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
        ]);

        $user->fill($validated);

        if ($user->isDirty()) {

            $user->save();
        } else {
            return back()->withErrors([
                'error' => 'No changes were made'
            ]);
        }

        return to_route('user.edit', $user)->with('message', 'Profile updated successfully');
    }

    public function updateWorkExperiences(User $user, Request $request)
    {
        //Update or create work experience
        if ($request->method() === "PATCH") {
            $validated = $request->validate([
                "company" => "required|string|max:100",
                "position" => "required|string|max:100",
                "start_date" => "required|date",
                "end_date" => "nullable|date|after_or_equal:start_date",
                "is_current" => "nullable|boolean",
                "description" => "nullable|string|max:500",
            ]);

            $user->workExperiences()->updateOrCreate(
                [
                    'id' => $request->id,
                ],
                $validated
            );
            return back()->with('message', 'Work experience updated successfully');
        }
        //Delete work experience
        if ($request->method() === "DELETE") {
            $request->validate([
                'id' => 'required|exists:work_experiences,id',
            ]);
            $user->workExperiences()->where('id', $request->id)->delete();
            return back()->with('message', 'Work experience deleted successfully');
        }
    }

    public function updateAvatar(User $user, Request $request)
    {

        if ($request->hasFile('avatar')) {

            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($user->avatar) {
                //Remove /storage/ from url
                $user->avatar = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = Storage::url($avatarPath);
            $user->save();
            return response()->json([
                'avatar' => $user->avatar,
                'message' => 'Avatar updated successfully'
            ]);
        } else {
            return back()->withErrors(['error' => 'Failed to update avatar']);
        }
    }


    public function destroy(User $user)
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        //Delete all portfolios
        foreach ($user->portfolios as $portfolio) {
            if ($portfolio->thumbnail) {
                //Remove /storage/ from url
                $portfolio->thumbnail = str_replace('/storage/', '', $portfolio->thumbnail);
                // Delete the old thumbnail
                Storage::disk('public')->delete($portfolio->thumbnail);
            }
            $portfolio->delete();
        }

        //Delete all work experiences
        foreach ($user->workExperiences as $workExperience) {
            $workExperience->delete();
        }
        //Delete all likes
        foreach ($user->likedPortfolios as $like) {
            $like->delete();
        }

        //Delete all comments
        foreach ($user->comments as $comment) {
            $comment->delete();
        }

        //Delete User
        $user->delete();
        return to_route('home')->with('message', 'Account deleted successfully');
    }
}
