<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadNoteController extends Controller
{
    /**
     * Store a newly created note for a lead.
     */
    public function store(Request $request, Lead $lead)
    {
        // Authorization check
        if (Auth::user()->role !== 'admin' && $lead->campaign->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this lead.');
        }

        $request->validate([
            'note' => 'required|string',
        ]);

        $lead->addNote($request->note, Auth::id());

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Note added successfully!');
    }
}
