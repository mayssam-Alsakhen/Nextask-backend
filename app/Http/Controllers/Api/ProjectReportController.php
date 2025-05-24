<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use PDF;                // ← DomPDF facade
use App\Models\Project;

class ProjectReportController extends Controller
{
    public function show(string $id)
    {
        $user = Auth::user();

        // Only load if user is a member
        $project = Project::with('tasks.users', 'users')
            ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->find($id);

        if (! $project) {
            return response(['status' => 403, 'message' => 'Unauthorized'], 403);
        }

        // Prepare view data
        $viewData = [
            'project' => [
                'name'        => $project->name,
                'description' => $project->description,
                // Use the raw string value here:
                'due_date'    => $project->due_date,
                'status'      => $project->status,
                'progress'    => $project->progress . '%',
            ],
            'tasks' => $project->tasks->map(fn($t) => [
                'title'       => $t->title,
                'status'      => $t->category,
                // And here as well:
                'due_date'    => $t->due_date,
                'isImportant' => $t->is_important ? 'Yes' : 'No',
                'progress'    => $t->progress . '%',
                'assigned'    => $t->users->isNotEmpty() ? 'Yes' : 'No',
            ]),
        ];

        // Generate and return PDF
        $pdf = PDF::loadView('reports.project', $viewData);
        return $pdf->download("project-{$id}-report.pdf");
    }
}
