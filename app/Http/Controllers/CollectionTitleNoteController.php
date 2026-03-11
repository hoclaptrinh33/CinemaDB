<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionTitleNote;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CollectionTitleNoteController extends Controller
{
    /**
     * PUT /collections/{collection:slug}/titles/{title}/note
     * Upsert the current user's personal note for a title in this collection.
     * Accessible to members (owner or accepted collaborator) only.
     */
    public function upsert(Request $request, Collection $collection, Title $title): RedirectResponse
    {
        $this->authorize('manageItems', $collection);

        $request->validate([
            'note' => ['nullable', 'string', 'max:10000'],
        ]);

        // Update the shared note on the pivot table (visible to all members)
        $collection->titles()->updateExistingPivot($title->title_id, [
            'note' => $request->input('note') ?: null,
        ]);

        return back(303)->with('success', 'Đã lưu ghi chú.');
    }

    /**
     * POST /collections/{collection:slug}/titles/{title}/watch
     * Toggle the current user's "watched" status for a title.
     * Only the user themselves can toggle their own watch status.
     */
    public function toggleWatch(Request $request, Collection $collection, Title $title): RedirectResponse
    {
        $authId = $request->user()->id;

        // Must be a member to have a watch entry
        if ($collection->user_id !== $authId && ! $collection->isCollaborator($authId)) {
            abort(403);
        }

        $note = CollectionTitleNote::firstOrCreate(
            [
                'collection_id' => $collection->collection_id,
                'title_id'      => $title->title_id,
                'user_id'       => $authId,
            ],
            ['note' => null, 'watched_at' => null]
        );

        $note->watched_at = $note->watched_at ? null : Carbon::now();
        $note->save();

        return back(303);
    }
}
