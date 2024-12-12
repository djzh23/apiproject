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
    public function initializeDirectories()
    {
        // Create a directory in storage/app
        Storage::makeDirectory('app/bills-directory');

        // Create a directory in public (which is linked to storage/app/public)
        Storage::disk('public')->makeDirectory('bills-public-directory');
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
            return $this->error(trans('messages.billing.preview.failed'), null);
        }
    }
    public function storeBillPdf(Request $request, $id)
    {
        // Find the bill by its id
        $bill = Billing::find($id);

        // If the bill doesn't exist, return an error response
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

        $type = $bill->isvorort ? '' : 'ausflug';
        $filename = "{$date}-{$month}-{$type}-{$string}.pdf";

        // Store the uploaded pdf file with the custom filename and get its path
        $path = $request->file('pdf')->storeAs('billings-pdfs', $filename, 'public');

        // Update the bill 's pdf field with the path of the stored file
        $bill->pdf_file = $path;
        $bill->save();

        return $this->success(trans('messages.billing.pdf.upload.success'), $bill);

    }
    public function getAllUserBillings()
    {
        // Get the ID of the authenticated user
        $userId = auth()->id();

        // Get only the Works that belong to the authenticated user
        $perpage = 10;
        $billings = Billing::where('user_id', $userId)
            ->paginate($perpage); // Change '10' to however many works per page you want


        // Transform the data to make it more readable
        $billingsData = $billings->map(function ($billing) {
            return [
                'id' => $billing->id,
                'data' => [
                    'billing_number' => $billing->billing_number,
                    'date' => $billing->date,
                    'team' => $billing->team,
                    'month' => $billing->month,
                    'somme_all' => $billing->somme_all,
                    'isvorort' => $billing->isvorort,
                    'pdf_file' => $billing->pdf_file,
                    ]
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
    public function getBillsByMonth(Request $request, $month)
    {
        try{
            // Get only the Bill that belong to the authenticated user
            $perpage = 10;
            $billings = Billing::where('user_id', $request->user()->id)
                ->where('month', $month)
                ->select(['id', 'billing_number', 'date', 'month', 'team', 'somme_all', 'isvorort', 'pdf_file'])
                ->paginate($perpage);


            // Transform the data to make it more readable
            $billingsData = $billings->map(function ($billing) {
                return [
                    'id' => $billing->id,
                    'data' => [
                        'billing_number' => $billing->billing_number,
                        'date' => $billing->date,
                        'team' => $billing->team,
                        'month' => $billing->month,
                        'somme_all' => $billing->somme_all,
                        'isvorort' => $billing->isvorort,
                        'pdf_file' => $billing->pdf_file,
                    ]
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
            return $this->error(trans('messages.billing.pdf.month_pagination.failed'), null);
        }

    }
    public function download($filename)
    {
        $path = 'billings-pdfs/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            return $this->success(trans('messages.billing.pdf.download.failed'), null);
        }

        return Storage::disk('public')->download($path);
    }
    public function listOfBillsPdfs(Request $request)
    {
        $userId = $request->user()->id;

        $bills = Billing::where('user_id', $userId)->whereNotNull('pdf_file')->get();


        if ($bills->isEmpty()) {
            return $this->error(trans('messages.billing.pdf.list.error'), null);
        }

        $files = $bills->pluck('pdf_file')->all();

        return $this->success(trans('messages.billing.pdf.list.success'), $files);
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
//            return $this->error($e ->getMessage(), null);
            return $this->error(trans('messages.billing.count.failed'), null);
        }

    }

}
