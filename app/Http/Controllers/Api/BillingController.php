<?php

namespace App\Http\Controllers\Api;

use App\Models\Billing;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillingController extends BaseController
{
    use ApiResponses;
    private const PDF_DIRECTORY = 'billings-pdfs';
    public function __construct()
    {
        $this->initializeDirectories();
    }
    public function initializeDirectories(): void
    {
        // Create a directory in public (which is linked to storage/app/public)
        Storage::disk('public')->makeDirectory('billings-pdfs');
    }
    public function download($id)
    {
        try{
            if (Auth::user()->role_id != 4 || Auth::user()->role_id != 2) {
                return $this->error(trans('messages.auth.unauthorized'), null);
            }

            $bill = Billing::where('id', $id)->first();

            if (!$bill) {
                return $this->error(__('messages.billing.pdf.download.failed'), null);
            }

            $path = $bill->pdf_file;

            if (!Storage::disk('public')->exists($path)) {
                return $this->error(__('messages.billing.pdf.download.failed'), $path);
            }

            return Storage::disk('public')->download($path);
        }
        catch (\Exception $e) {
            Log::error("download function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
            $billing = new Billing([
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'month' => $request->month,
                'billing_number' => $request->billing_number,
                'billing_details' => $request->billing_details,
                'somme_all' => $request->somme_all,

            ]);
            $billing->save();
            return $this->success(trans('messages.billing.create.success'), $billing);
        } catch (\Exception $e) {
            Log::error("store() function error-server: $e");
            return $this->error(__('messages.billing.create.failed'), null);
        }
    }
    public function preview(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            $validatedData = $request->validate([
                'date' => 'required|date',
                'month' => 'required|string',
                'billing_number' => 'required|integer',
                'billing_details' => 'required|array',
                'somme_all' => 'required|numeric',
            ]);

            if(!$validatedData){
                return $this->error(trans('messages.billing.preview.validation_failed'), null);
            }

            try{
                $request->validate([
                    'billing_number' => [
                        'required',
                        Rule::unique('billings')->where(function ($query) {
                            $query->where('user_id', auth()->id());
                        })->ignore($userId)
                    ],
                ]);
            }
            catch (\Exception $e) {
                Log::error("preview() function error-server: $e");
                return $this->error(trans('messages.billing.preview.invalid'), null);
            }

            $billing = new Billing($validatedData);

            return $this->success(trans('messages.billing.preview.success'), $billing);

        } catch (ValidationException $e) {
            Log::error("preview() function error-server: $e");
            return $this->error(trans('messages.billing.preview.failed'), $e->getTraceAsString());
        }
    }
    public function storeBillPdf(Request $request, $id): JsonResponse
    {
        try{
            $bill = Billing::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            Log::info("storeBillPdf function error-server: $bill");
            if (!$bill) {
                return $this->error(__('messages.billing.pdf.upload.failed'), null);
            }

            if ($bill->pdf_file != null) {
                return $this->error(__('messages.billing.pdf.upload.failed_pdf'), null);
            }

            // Validate the request to ensure a file was uploaded
            $request->validate([
                'pdf' => 'required|mimes:pdf|max:2048', // 2MB Max
            ]);

            // Generate a custom filename
            $date = date('Y-m-d');
            $user_name = $bill->user->firstname . '-' . $bill->user->lastname;
            $month = $bill->month;
            $string = 'rechnung';
            $number = $bill->billing_number;
            $filename = "{$string}-{$number}-{$month}-{$user_name}-{$date}.pdf";

            // Store the uploaded pdf file with the custom filename and get its path
            $path = $request->file('pdf')->storeAs('billings-pdfs', $filename, 'public');

            // Update the bill 's pdf field with the path of the stored file
            $bill->pdf_file = $path;
            $bill->save();

            return $this->success(trans('messages.billing.pdf.upload.success'), $bill);
        }
        catch (\Exception $e) {
            Log::error("storeBillPdf function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function getBillings()
    {
        try{
            $userId = auth()->id();

            // Get only the Billings that belong to the authenticated user ordered by the latest
            $perpage = 10;
            $billings = Billing::where('user_id', $userId)
                ->latest('created_at')
                ->paginate($perpage); // Paginate the results


            // Transform the data to make it more readable
            $billingsData = $billings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'date' => $billing->date,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'pdf_file' => $billing->pdf_file,
                ];
            });
            if($billingsData->isEmpty()){
                return $this->success(__('messages.billing.fetch.not_found'), null);
            }

            $pagination = [
                'total' => $billings->total(),
                "per_page"=> $perpage,
                'current_page' => $billings->currentPage(),
                'last_page' => $billings->lastPage(),
                'from' => $billings->firstItem(),
                'to' => $billings->lastItem(),
                'first_page_url' => $billings->url(1),
                'last_page_url' => $billings->url($billings->lastPage()),
                'next_page_url' => $billings->nextPageUrl(),
                'prev_page_url' => $billings->previousPageUrl(),
                'path' => $billings->path(),
            ];
            return $this->success(__('messages.billing.fetch.success'), $billingsData,$pagination);
        }
        catch (\Exception $e) {
            Log::error("getAllUserBillings function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function getBillsByMonth(Request $request, $month)
    {
        try{
            // Get only the Bill that belong to the authenticated user and the specified month ordered by the latest
            $perpage = 10;
            $billings = Billing::where('user_id', $request->user()->id)
                ->where('month', $month)
                ->latest('created_at')
                ->select(['id', 'billing_number', 'date', 'month', 'somme_all', 'pdf_file'])
                ->paginate($perpage);


            // Transform the data to make it more readable
            $billingsData = $billings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'date' => $billing->date,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'pdf_file' => $billing->pdf_file,
                ];
            });

            if ($billingsData != null && $billingsData->count() > 0){
                $pagination = [
                    'total' => $billings->total(),
                    "per_page"=> $perpage,
                    'current_page' => $billings->currentPage(),
                    'last_page' => $billings->lastPage(),
                    'from' => $billings->firstItem(),
                    'to' => $billings->lastItem(),
                    'first_page_url' => $billings->url(1),
                    'last_page_url' => $billings->url($billings->lastPage()),
                    'next_page_url' => $billings->nextPageUrl(),
                    'prev_page_url' => $billings->previousPageUrl(),
                    'path' => $billings->path(),
                ];
                return $this->success(__('messages.billing.pdf.month_pagination.success'), $billingsData,$pagination);

            }
            return $this->success(trans('messages.billing.pdf.month_pagination.empty'), null);
        }
        catch (\Exception $e){
            Log::error("getBillsByMonth function error-server: $e");
            return $this->error(trans('messages.billing.pdf.month_pagination.failed'), null);
        }

    }
    public function listOfBillsPdfs(Request $request)
    {
        try{
            $userId = $request->user()->id;

            $bills = Billing::where('user_id', $userId)->whereNotNull('pdf_file')
                ->latest('created_at')
                ->get();

            if ($bills->isEmpty()) {
                return $this->error(trans('messages.billing.pdf.list.error'), null);
            }

            $files = $bills->pluck('pdf_file')->all();

            return $this->success(trans('messages.billing.pdf.list.success'), $files);
        }
        catch (\Exception $e) {
            Log::error("listOfBillsPdfs function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function getNumberOfBills()
    {
        try{
            $userId = Auth::id(); // Get the ID of the authenticated user

            // Get only the Works that belong to the authenticated user
            $bills = Billing::where('user_id', $userId)->get();
            $count = $bills->count();

            return $this->success(trans('messages.billing.count.success'), $count);
        }catch (\Exception $e){
            Log::error("getNumberOfBills function error-server: $e");
            return $this->error(trans('messages.billing.count.failed'), null);
        }
    }
    public function getAdminAllBillings()
    {
        try{

            // Get all the billings ordered by the latest
            $perpage = 10;
            $billings = Billing::latest('created_at')
                ->paginate($perpage); // Paginate the results


            // Transform the data to make it more readable
            $billingsData = $billings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'date' => $billing->date,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'pdf_file' => $billing->pdf_file,
                ];
            });
            if($billingsData->isEmpty()){
                return $this->success(__('messages.billing.fetch.not_found'), null);
            }

            $pagination = [
                'total' => $billings->total(),
                "per_page"=> $perpage,
                'current_page' => $billings->currentPage(),
                'last_page' => $billings->lastPage(),
                'from' => $billings->firstItem(),
                'to' => $billings->lastItem(),
                'first_page_url' => $billings->url(1),
                'last_page_url' => $billings->url($billings->lastPage()),
                'next_page_url' => $billings->nextPageUrl(),
                'prev_page_url' => $billings->previousPageUrl(),
                'path' => $billings->path(),
            ];
            return $this->success(__('messages.billing.fetch.success'), $billingsData,$pagination);
        }
        catch (\Exception $e) {
            Log::error("getAllUserBillings function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function getAdminBillsByMonth(Request $request, $month)
    {
        try{
            // Get all the Bills ordered by Month and the latest
            $perpage = 10;
            $billings = Billing::where('month', $month)
                ->latest('created_at')
                ->select(['id', 'billing_number', 'date', 'month', 'somme_all', 'pdf_file'])
                ->paginate($perpage);


            // Transform the data to make it more readable
            $billingsData = $billings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'date' => $billing->date,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'pdf_file' => $billing->pdf_file,
                ];
            });

            if ($billingsData != null && $billingsData->count() > 0){
                $pagination = [
                    'total' => $billings->total(),
                    "per_page"=> $perpage,
                    'current_page' => $billings->currentPage(),
                    'last_page' => $billings->lastPage(),
                    'from' => $billings->firstItem(),
                    'to' => $billings->lastItem(),
                    'first_page_url' => $billings->url(1),
                    'last_page_url' => $billings->url($billings->lastPage()),
                    'next_page_url' => $billings->nextPageUrl(),
                    'prev_page_url' => $billings->previousPageUrl(),
                    'path' => $billings->path(),
                ];
                return $this->success(__('messages.billing.pdf.month_pagination.success'), $billingsData,$pagination);

            }
            return $this->success(trans('messages.billing.pdf.month_pagination.empty'), null);
        }
        catch (\Exception $e){
            Log::error("getBillsByMonth function error-server: $e");
            return $this->error(trans('messages.billing.pdf.month_pagination.failed'), null);
        }

    }
}
