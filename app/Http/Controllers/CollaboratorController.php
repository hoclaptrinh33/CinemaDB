<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionCollaborator;
use App\Models\User;
use App\Notifications\CollectionInvited;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    /** POST /collections/{collection:slug}/collaborators — owner invites by username */
    public function store(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('invite', $collection);

        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        $invitee = User::where('username', $validated['username'])->firstOrFail();

        if ($invitee->id === $collection->user_id) {
            return back()->withErrors(['username' => 'The owner cannot be added as a collaborator.']);
        }

        if ($collection->collaborators()->where('user_id', $invitee->id)->exists()) {
            return back()->withErrors(['username' => 'This user already has a pending or active invitation.']);
        }

        CollectionCollaborator::create([
            'collection_id' => $collection->collection_id,
            'user_id'       => $invitee->id,
            'invited_by'    => $request->user()->id,
            'accepted_at'   => null,
        ]);

        $invitee->notify(new CollectionInvited($collection, $request->user()));

        return back()->with('success', 'Invitation sent.');
    }

    /** POST /collections/{collection:slug}/collaborators/accept — invitee accepts */
    public function accept(Request $request, Collection $collection): RedirectResponse
    {
        $collaborator = CollectionCollaborator::where('collection_id', $collection->collection_id)
            ->where('user_id', $request->user()->id)
            ->whereNull('accepted_at')
            ->firstOrFail();

        $collaborator->update(['accepted_at' => now()]);

        return back()->with('success', 'You have joined the collection.');
    }

    /**
     * DELETE /collections/{collection:slug}/collaborators/{user}
     * Owner removes anyone; collaborator removes themselves (leave).
     */
    public function destroy(Request $request, Collection $collection, User $user): RedirectResponse
    {
        $authId = $request->user()->id;

        $isOwner = $authId === $collection->user_id;
        $isSelf  = $authId === $user->id;

        if (! $isOwner && ! $isSelf) {
            abort(403);
        }

        CollectionCollaborator::where('collection_id', $collection->collection_id)
            ->where('user_id', $user->id)
            ->delete();

        if ($isSelf && ! $isOwner) {
            return redirect()->route('collections.show', $collection->slug)
                ->with('success', 'You have left the collection.');
        }

        return back()->with('success', 'Collaborator removed.');
    }
}
