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
        // Create a directory in storage/app
//        Storage::makeDirectory('app/bills-directory');

        // Create a directory in public (which is linked to storage/app/public)
        Storage::disk('public')->makeDirectory('billings-pdfs');
    }
    public function download_($filename)
    {
        try{
            $path = self::PDF_DIRECTORY ."/". $filename;

            if (!Storage::disk('public')->exists($path)) {
                return $this->error(trans('messages.billing.pdf.download.failed'), $path);
            }

            return Storage::disk('public')->download($path);
        }
        catch (\Exception $e) {
            Log::error("download function error-server: $e");
            return $this->error(__('messages.server_error'), null);
        }
    }
    public function download($id)
    {
        try{
//            $bill = Billing::find($id);
            $bill = Billing::where('billing_number', $id)->first();

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
                'team' => $request->team,
                'somme_all' => $request->somme_all,
                'isvorort' => $request->isvorort,

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
            $validatedData = $request->validate([
                'date' => 'required|date',
                'month' => 'required|string',
                'billing_number' => 'required|integer',
                'billing_details' => 'required|array',
                'team' => 'required|string',
                'somme_all' => 'required|numeric',
                'isvorort' => 'required|boolean',
            ]);

            if(!$validatedData){
                return $this->error(trans('messages.billing.preview.validation_failed'), null);
            }

            $billingNumberExists = Billing::where('billing_number', $validatedData['billing_number'])->exists();

            if ($billingNumberExists) {
                return $this->error(trans('messages.billing.preview.invalid'), null);
            }

            $billing = new Billing($validatedData);

            return $this->success(trans('messages.billing.preview.success'), $billing);

        } catch (ValidationException $e) {
            Log::error("preview() function error-server: $e");
            return $this->error(trans('messages.billing.preview.failed'), null);
        }
    }
    public function storeBillPdf(Request $request, $id): JsonResponse
    {
        try{
            $bill = Billing::find($id);

            if (!$bill) {
                return $this->error(__('messages.billing.pdf.upload.failed'), null);
            }

            // Validate the request to ensure a file was uploaded
            $request->validate([
                'pdf' => 'required|mimes:pdf|max:2048', // 2MB Max
            ]);

            // Generate a custom filename
            $date = date('Y-m-d'); // You can replace this with the actual date from the bill
            $month = $bill->month; // Replace this with the actual month from the bill
            $string = 'rechnung';
            $number = $bill->billing_number;
            $type = $bill->isvorort ? '' : 'ausflug';
            $filename = "{$date}-{$month}-{$type}-{$string}-{$number}.pdf";

//            $encryptedFilename = Crypt::encryptString($filename);
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
    public function getAllUserBillings()
    {
        try{
            $userId = auth()->id();

            // Get only the Works that belong to the authenticated user ordered by the latest
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
                    'team' => $billing->team,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'isvorort' => $billing->isvorort,
                    'pdf_file' => $billing->pdf_file,
                ];
            });
            if($billingsData->isEmpty()){
                return $this->error(__('messages.billing.fetch.not_found'), null);
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
                ->select(['id', 'billing_number', 'date', 'month', 'team', 'somme_all', 'isvorort', 'pdf_file'])
                ->paginate($perpage);


            // Transform the data to make it more readable
            $billingsData = $billings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'date' => $billing->date,
                    'team' => $billing->team,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'isvorort' => $billing->isvorort,
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

}
