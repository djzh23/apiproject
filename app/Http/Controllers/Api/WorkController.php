<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Traits\ApiResponses;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use function Symfony\Component\Translation\t;

class WorkController
{
    use ApiResponses;

    private const PDF_DIRECTORY = 'works-pdfs';
    public function __construct()
    {
        $this->initializeDirectories();
    }
    public function initializeDirectories(): void
    {
        Storage::disk('public')->makeDirectory(self::PDF_DIRECTORY);
    }
    public function creatework(Request $request): JsonResponse
    {
        try{
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
                Log::error('Unique Work creation validation failed: ' . $validator->errors());
                return $this->error(__('messages.work.create.validation_failed'), null);
            }

            try{
                $request->validate([
                    'date' => [
                        'required',
                        'date_format:d-m-Y,Y-m-d',
                        Rule::unique('works')
                            ->where(function ($query) use ($request) {
                                return $query->where('team', $request->team);
                            })
                    ],
                ]);
            }
            catch (\Exception $e) {
                Log::error("preview() function error-server: $e");
                return $this->error(trans('messages.work.create.unique_work_validation_failed'), null);
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
        catch (\Exception $e) {
            Log::error("creatework() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function getAllWorks(): JsonResponse
    {
        try {
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
                "per_page" => $perpage,
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
            Log::error("getAllWorks() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function updateWork(Request $request, $id): JsonResponse
    {
        try{
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
        catch (\Exception $e) {
            Log::error("updateWork() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function storePdf(Request $request, $id): JsonResponse
    {
        try{
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
            $date = Carbon::parse($work->date)->format('Y-m-d');


            $team = $work->team;

            $string = 'einsatz';
            $filename = "{$date}-{$string}-{$team}.pdf";
            $path = $request->file('pdf')->storeAs(self::PDF_DIRECTORY, $filename, 'public');

            // Update the work's pdf field with the path of the stored file
            $work->pdf_file = $path;
            $work->save();

            return $this->success(trans('messages.work.pdf.upload.success'), $work);
        }
        catch (\Exception $e) {
            Log::error("storePdf() function error-server: $e");
            return $this->error(__('messages.work.pdf.upload.failed'), null);
        }

    }
    public function getAdminAllWorks(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            // Get filter parameters from the request
            $team = $request->input('team');

            // Get all works that have a pdf_file, status as "complete", and match the specified team
            $query = Work::with('ageGroups')->whereNotNull('pdf_file')->where('status', 'complete');

            if ($team) {
                $query->where('team', $team);
            }

            // Order by created_at in descending order to get the last added works first and paginate the results
            $works = $query->where('creator_id', '!=', $userId)
                ->orderBy('created_at', 'desc')->paginate(25);

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
        } catch (\Exception $e) {
            Log::error("getAdminAllWorks() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function getWorksByTeam(Request $request, $team): JsonResponse
    {
        try {
            // Get only the Bill that belong to the authenticated user
            $perpage = 10;
            $works = Work::where('team', $team)
                ->whereNot('creator_id', auth()->id())
                ->where('status', 'complete')
                ->whereNotNull('pdf_file')
                ->orderBy('created_at', 'desc')
                ->select(['id', 'updated_at', 'creator_id', 'date', 'status', 'team', 'ort', 'vorort', 'list_of_helpers', 'plan', 'start_work', 'reflection', 'defect', 'parent_contact', 'wellbeing_of_children', 'notes', 'wishes', 'pdf_file', 'end_work'])
                ->paginate($perpage);

            // Transform the data to make it more readable
            $worksData = $works->map(function ($works) {
                return [
                    'id' => $works->id,
                    'date' => $works->date,
                    'team' => $works->team,
                    'creator_name' => $works->creator ? $works->creator->firstname . ' ' . $works->creator->lastname : null,
                    'pdf_file' => $works->pdf_file,
                ];
            });

            if ($worksData != null && $worksData->count() > 0) {
                $pagination = [
                    'total' => $works->total(),
                    "per_page" => $perpage,
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
                return $this->success(trans('messages.work.fetch.by_team.success'), $worksData, $pagination);
            } else {
                return $this->success(trans('messages.work.fetch.by_team.empty'), null);
            }

        } catch (\Exception $e) {
            Log::error("getWorksByTeam() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function GetNumberOfWorks(): JsonResponse
    {
        try {
            $userId = Auth::id();
            // Get only the Works that belong to the authenticated user
            $works = Work::where('creator_id', $userId)->get();

            return $this->success(trans('messages.work.count.all.success'), $works->count());
        } catch (\Exception $e) {
            Log::error("GetNumberOfWorks() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
    public function GetNumberOfStandingWorks(): JsonResponse
    {
        try {
            $userId = Auth::id(); // Get the ID of the authenticated user

            // Get only the Works that belong to the authenticated user
            $works = Work::where('creator_id', $userId)->where('status', 'standing')->get();

            return $this->success(trans('messages.work.count.standing.success'), $works->count());
        } catch (\Exception $e) {
            Log::error("GetNumberOfStandingWorks() function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function download($id)
    {
        try {
            $work = Work::find($id);

            if (!$work) {
                return $this->error(__('messages.work.pdf.download.failed'), null);
            }
            $path = $work->pdf_file;

            if (!Storage::disk('public')->exists($path)) {
                return $this->error(trans('messages.work.pdf.download.failed'), $path);
            }

            return Storage::disk('public')->download($path);
        } catch (\Exception $e) {
            Log::error("Downloading Work PDF error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }

    }
}
