<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Email;
use App\Models\Matiere;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show()
    {
        return view('home');
    }

    public function verifyPassword(Request $request)
    {
        $password = $request->input('password');

        if ($password === getenv('PASSWORD')) {
            $request->session()->put('authenticated', true);
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function checkAuthentication(Request $request)
    {
        $authenticated = $request->session()->get('authenticated', false);
        return response()->json(['authenticated' => $authenticated]);
    }

    public function getEmails(Request $request)
    {
        if ($request->session()->get('authenticated')) {
            $emails = Email::all();
            return response()->json(['success' => true, 'emails' => $emails]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function addEmail(Request $request)
    {
        if (!$request->session()->get('authenticated')) {
            return response()->json(['success' => false, 'message' => 'Non authentifié']);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:emails,email|max:255',
            'type' => 'required|string|max:255',
        ]);

        $email = Email::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'type' => $request->input('type'),
        ]);

        $emails = Email::all();

        return response()->json(['success' => true, 'emails' => $emails]);
    }

    public function addAssignment(Request $request)
    {
        if (!$request->session()->get('authenticated')) {
            return response()->json(['success' => false, 'message' => 'Non authentifié']);
        }

        $request->validate([
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'matiere_id' => 'required|exists:matieres,id',
        ]);

        $assignment = Assignment::create([
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
            'matiere_id' => $request->input('matiere_id'),
        ]);

        return $this->getAssignments($request);
    }

    public function getAssignments(Request $request)
    {
        if ($request->session()->get('authenticated')) {

            $query = Assignment::with('matiere');

            $assignments = $query->get()->groupBy(function ($assignment) {
                return \Carbon\Carbon::parse($assignment->due_date)->format('oW'); // Année et numéro de semaine
            });

            $assignments = $assignments->sortKeys();

            $html = view('partials.assignments_by_week', compact('assignments'))->render();

            return response()->json(['success' => true, 'html' => $html]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function extendAssignment(Request $request, $id)
    {
        if (!$request->session()->get('authenticated')) {
            return response()->json(['success' => false, 'message' => 'Non authentifié']);
        }

        $assignment = Assignment::find($id);
        $newDueDate = $request->input('new_due_date');

        if ($assignment && $newDueDate) {
            $assignment->due_date = $newDueDate;
            $assignment->save();

            return $this->getAssignments($request);
        }

        return response()->json(['success' => false]);
    }

    public function getMatieres()
    {
        return Matiere::all();
    }

    public function getAvailableWeeks()
    {
        $weeks = [
            '2024-08-26', '2024-09-09', '2024-09-30', '2024-10-21', '2024-11-04',
            '2024-11-25', '2024-12-09', '2025-01-06', '2025-01-27', '2025-02-17',
            '2025-03-10', '2025-03-31', '2025-04-21', '2025-05-05', '2025-06-02',
            '2025-06-16', '2025-06-30'
        ];

        $now = \Carbon\Carbon::now();
        $availableWeeks = [];

        foreach ($weeks as $week) {
            $monday = \Carbon\Carbon::parse($week);
            $friday = $monday->copy()->addDays(4);

            if ($friday->greaterThanOrEqualTo($now)) {
                $formattedWeek = $monday->format('d/m') . ' au ' . $friday->format('d/m');

                $availableWeeks[] = [
                    'date' => $monday->toDateString(),
                    'formatted' => $formattedWeek
                ];
            }
        }

        return response()->json($availableWeeks);
    }
}
