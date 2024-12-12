<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Traits\ApiResponses;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WorkController
{
    use ApiResponses;
    public function creatework(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'team' => 'required|string',
            'ort' => 'required|string',
            'vorort' => 'required|boolean',
            'list_of_helpers' => 'required|array',
            'plan' => 'required|string',
            'start_work' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            Log::error('Job creation validation failed: ' . $validator->errors());
            return $this->error(__('messages.work.create.validation_failed'), null);
        }

        // If validation passes, get the validated data
        $validatedData = $validator->validated();

        // Add the creator_id to the validated data
        $validatedData['creator_id'] = auth()->id();

        try {
            // Create the work with the validated data
            $work = Work::create($validatedData);

            return $this->success(trans('messages.work.create.success'), $work);

        } catch (\Exception $e) {
            return $this->error(__('messages.work.create.failed'), null);

        }
    }

    public function getAllWorks(): JsonResponse
    {
        try{
            $userId = auth()->id();

            // Get only the Works that belong to the authenticated user and filtered by status
            $perpage = 10;
            $works = Work::where('creator_id', $userId)
                ->with('ageGroups') // Eager load the ageGroups relationship
                ->orderByRaw("CASE WHEN status = 'standing' THEN 0 ELSE 1 END, updated_at DESC")
                ->paginate($perpage); // Change '10' to however many works per page you want


            // Transform the data to make it more readable
            $worksData = $works->map(function ($work) {
                return [
                    'id' => $work->id,
                    'updated_at' => $work->updated_at->toIso8601String(),
                    'creator_id' => $work->creator_id,
                    'creator_name' => $work->creator ? $work->creator->firstname . ' ' . $work->creator->lastname : null,
                    'date' => $work->date,
                    'status' => $work->status,
                    'team' => $work->team,
                    'ort' => $work->ort,
                    'vorort' => $work->vorort,
                    'list_of_helpers' => $work->list_of_helpers,
                    'plan' => $work->plan,
                    'start_work' => $work->start_work,
                    'reflection' => $work->reflection,
                    'defect' => $work->defect,
                    'parent_contact' => $work->parent_contact,
                    'wellbeing_of_children' => $work->wellbeing_of_children,
                    'notes' => $work->notes,
                    'wishes' => $work->wishes,
                    'pdf_file' => $work->pdf_file,
                    'end_work' => $work->end_work,
                    'kids_data' => $work->ageGroups->map(function ($ageGroup) use ($work) {
                        return [
                            'work_id' => $work->id,
                            'age_range' => $ageGroup->age_range,
                            'age_group_id' => $ageGroup->id,
                            'boys' => $ageGroup->pivot->boys,
                            'girls' => $ageGroup->pivot->girls,
                        ];
                    })->values()->all(),
                ];
            });

            $pagination = [
                'total' => $works->total(),
                "per_page"=> $perpage,
                'current_page' => $works->currentPage(),
                'last_page' => $works->lastPage(),
                'from' => $works->firstItem(),
                'to' => $works->lastItem(),
                'first_page_url' => $works->url(1),
                'last_page_url' => $works->url($works->lastPage()),
                'next_page_url' => $works->nextPageUrl(),
                'prev_page_url' => $works->previousPageUrl(),
                'path' => $works->path(),
            ];

            return $this->success(trans('messages.work.fetch.success'), $worksData, $pagination);

        } catch (\Exception $e) {
            return $this->error(__('messages.work.fetch.failed'), null);
        }
    }

    public function updateWork(Request $request, $id): JsonResponse
    {
        // Find the work by its id
        $work = Work::find($id);

        // If the work doesn't exist, return an error response
        if (!$work) {
            return $this->error(__('messages.work.update.not_found'), null);
        }

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'start_work' => 'sometimes|required|date_format:H:i',
            'team' => 'sometimes|required|string',
            'ort' => 'sometimes|required|string',
            'vorort' => 'sometimes|required|boolean',
            'list_of_helpers' => 'sometimes|nullable|array',
            'plan' => 'sometimes|required|string',



            'reflection' => 'sometimes|required|string',

            'defect' => 'sometimes|nullable|string',
            'parent_contact' => 'sometimes|nullable|string',

            'wellbeing_of_children' => 'sometimes|nullable|string',

            'notes' => 'sometimes|nullable|string',
            'wishes' => 'sometimes|nullable|string',



            'end_work' => 'sometimes|required|date_format:H:i',

            'kids_data' => 'sometimes|required|array',
            'kids_data.*.age_group_id' => 'required_with:kids_data|integer|exists:age_groups,id',
            'kids_data.*.boys' => 'required_with:kids_data|integer|min:0',
            'kids_data.*.girls' => 'required_with:kids_data|integer|min:0',
            // Add validation rules for other fields in the FullWork model
        ]);

        if ($validator->fails()) {
            return $this->error(__('messages.work.update.validation_failed'), null);
        }

        // If validation passes, get the validated data
        $validatedData = $validator->validated();

        // Update the work with the validated data
        $work->update($validatedData);

        // Update kids data in the pivot table if provided
        if ($request->has('kids_data')) {
            $kidsData = $request->input('kids_data');
            $syncData = [];
            foreach ($kidsData as $kid) {
                $syncData[$kid['age_group_id']] = [
                    'boys' => $kid['boys'],
                    'girls' => $kid['girls'],
                ];
            }
            $work->ageGroups()->sync($syncData);
        }

        $requiredFields = ['plan', 'reflection']; // Add all your required fields here
        $allFieldsFilled = true;

        foreach ($requiredFields as $field) {
            if (empty($work->$field)) {
                $allFieldsFilled = false;
                break;
            }
        }

        // Update status based on field values
        if ($allFieldsFilled) {
            $work->status = 'complete';
        } else {
            $work->status = 'standing';
        }

        $work->save();

        return $this->success(trans('messages.work.update.success'), $work);
    }
    public function storePdf(Request $request, $id): JsonResponse
    {
        // Find the work by its id
        $work = Work::find($id);

        // If the work doesn't exist, return an error response
        if (!$work) {
            return $this->error(__('messages.work.pdf.not_found'), null);
        }

        // Validate the request to ensure a file was uploaded
        $request->validate([
            'pdf' => 'required|mimes:pdf|max:2048', // 2MB Max
        ]);

        // Format the date to 'YYYY-MM-DD'
        $date = \Carbon\Carbon::parse($work->date)->format('Y-m-d');

        $string = 'einsatz';
        $team = $work->team;
        $filename = "{$date}-{$string}-{$team}.pdf";
        $path = $request->file('pdf')->storeAs('pdfs', $filename, 'local');

        // Update the work's pdf field with the path of the stored file
        $work->pdf_file = $path;
        $work->save();

        return $this->success(trans('messages.work.pdf.upload.success'), $work);
    }
    public function getAdminAllWorks(Request $request)
    {
        try{
            // Get filter parameters from the request
            $team = $request->input('team');

            // Get all works that have a pdf_file, status as "complete", and match the specified team
            $query = Work::with('ageGroups')->whereNotNull('pdf_file')->where('status', 'complete');

            if ($team) {
                $query->where('team', $team);
            }

            // Order by created_at in descending order to get the last added works first and paginate the results
            $works = $query->orderBy('created_at', 'desc')->paginate(25);

            // Transform the data to make it more readable
            $worksData = $works->map(function ($work) {
                return [
                    'id' => $work->id,
                    'updated_at' => $work->updated_at->toIso8601String(),
                    'creator_id' => $work->creator_id,
                    'creator_name' => $work->creator ? $work->creator->firstname . ' ' . $work->creator->lastname : null,
                    'date' => $work->date,
                    'status' => $work->status,
                    'team' => $work->team,
                    'ort' => $work->ort,
                    'vorort' => $work->vorort,
                    'list_of_helpers' => $work->list_of_helpers,
                    'plan' => $work->plan,
                    'start_work' => $work->start_work,
                    'reflection' => $work->reflection,
                    'defect' => $work->defect,
                    'parent_contact' => $work->parent_contact,
                    'wellbeing_of_children' => $work->wellbeing_of_children,
                    'notes' => $work->notes,
                    'wishes' => $work->wishes,
                    'pdf_file' => $work->pdf_file,
                    'end_work' => $work->end_work,
                    'kids_data' => $work->ageGroups->map(function ($ageGroup) use ($work) {
                        return [
                            'work_id' => $work->id,
                            'age_range' => $ageGroup->age_range,
                            'age_group_id' => $ageGroup->id,
                            'boys' => $ageGroup->pivot->boys,
                            'girls' => $ageGroup->pivot->girls,
                        ];
                    })->values()->all(),
                ];
            });

            $pagination = [
                'total' => $works->total(),
                'per_page' => $works->perPage(),
                'current_page' => $works->currentPage(),
                'last_page' => $works->lastPage(),
                'from' => $works->firstItem(),
                'to' => $works->lastItem(),
                'first_page_url' => $works->url(1),
                'last_page_url' => $works->url($works->lastPage()),
                'next_page_url' => $works->nextPageUrl(),
                'prev_page_url' => $works->previousPageUrl(),
                'path' => $works->path(),
            ];
            return $this->success(trans('messages.work.fetch.success'), $worksData, $pagination);
        }
        catch (\Exception $e) {
            return $this->error(__('messages.work.fetch.failed'), null);
        }

    }
    public function getWorksByTeam($team)
    {
        try{
            // Assuming there is a 'team' column in the 'works' table
            $works = Work::with('ageGroups')
                ->where('team', $team)
                ->whereNotNull('pdf_file')
                ->where('status', 'complete')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success(trans('messages.work.fetch.by_team.success'), $works);
        }
        catch (\Exception $e) {
            return $this->error(__('messages.work.fetch.by_team.failed'), null);
        }


    }
}
