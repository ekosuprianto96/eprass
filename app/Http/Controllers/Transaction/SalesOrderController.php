<?php

namespace App\Http\Controllers\Transaction;

use PDF;
use App\Constants;
use Carbon\Carbon;
use App\Models\Sales;
use App\Models\Inquiry;
use Illuminate\View\View;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use App\Models\InquiryProduct;
use App\Models\SalesOrderProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Events\SalesOrderExcelExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Helper\FilesController;
use App\Http\Controllers\Helper\RedisController;
use App\Http\Requests\Transaction\AddSalesOrderRequest;
use App\Http\Requests\Transaction\EditSalesOrderRequest;

class SalesOrderController extends Controller
{
    protected $fileController, $redisController, $hosting;

    public function __construct()
    {
        $this->middleware('auth');
        $this->fileController = new FilesController();
        $this->redisController = new RedisController();
        
        if(env('HOSTING')) {
            $this->hosting = env('HOSTING');
        } else {
            $this->hosting = false;
        }
    }

    public function index() : View
    {
        return view('transaction.sales-order.index');
    }
    
    public function data(Request $request) : JsonResponse 
    {
        if($request->ajax()) {
            $data = SalesOrder::orderBy('created_at', 'DESC')->get();

            $result = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('id_so', function($q) {
                    return $q->id;
                })
                ->addColumn('id_inquiry', function($q) {
                    return $q->inquiry->id;
                })
                ->addColumn('customer', function($q) {
                    return strtoupper($q->inquiry->visit->customer->name . ' - ' . $q->inquiry->visit->customer->company);
                })
                ->addColumn('due_date', function($q) {
                    $due_date = Carbon::parse($q->inquiry->due_date)->format('d M Y');
                    return $due_date;
                })
                ->addColumn('grade', function($q) {
                    return $q->inquiry->grade . '%';
                })
                ->addColumn('sales', function($q) {
                    return strtoupper($q->inquiry->sales->name);
                })
                ->editColumn('status', function($q) {
                    return strtoupper($q->status);
                })
                ->addColumn('warning', function($q) {
                    $now = Carbon::now();
                    $date = Carbon::parse($q->due_date);
                    $daysDifference = $date->diffInDays($now);
                    if($daysDifference <= 2) {
                        return true;
                    } else {
                        return false;
                    }
                })
                ->make(true);
    
            return $result;
        }    
    }
    
    public function data_grade(Request $request) : JsonResponse 
    {
        if($request->ajax()) {
            $data = SalesOrder::whereHas('inquiry', function($q) use ($request) {
                    $q->whereBetween('grade', [$request->value1, $request->value2]);
                })
                ->orderBy('created_at', 'DESC')->get();

            $result = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('id_so', function($q) {
                    return $q->id;
                })
                ->addColumn('id_inquiry', function($q) {
                    return $q->inquiry->id;
                })
                ->addColumn('customer', function($q) {
                    return strtoupper($q->inquiry->visit->customer->name . ' - ' . $q->inquiry->visit->customer->company);
                })
                ->addColumn('due_date', function($q) {
                    $due_date = Carbon::parse($q->inquiry->due_date)->format('d M Y');
                    return $due_date;
                })
                ->addColumn('grade', function($q) {
                    return $q->inquiry->grade . '%';
                })
                ->addColumn('sales', function($q) {
                    return strtoupper($q->inquiry->sales->name);
                })
                ->editColumn('status', function($q) {
                    return strtoupper($q->status);
                })
                ->addColumn('warning', function($q) {
                    $now = Carbon::now();
                    $date = Carbon::parse($q->due_date);
                    $daysDifference = $date->diffInDays($now);
                    if($daysDifference <= 2) {
                        return true;
                    } else {
                        return false;
                    }
                })
                ->make(true);
    
            return $result;
        }
    }
    
    public function data_status(Request $request) : JsonResponse 
    {
        if($request->ajax()) {
            $data = SalesOrder::where('status', $request->value)
                ->orderBy('created_at', 'DESC')->get();

            $result = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('id_so', function($q) {
                    return $q->id;
                })
                ->addColumn('id_inquiry', function($q) {
                    return $q->inquiry->id;
                })
                ->addColumn('customer', function($q) {
                    return strtoupper($q->inquiry->visit->customer->name . ' - ' . $q->inquiry->visit->customer->company);
                })
                ->addColumn('due_date', function($q) {
                    $due_date = Carbon::parse($q->inquiry->due_date)->format('d M Y');
                    return $due_date;
                })
                ->addColumn('grade', function($q) {
                    return $q->inquiry->grade . '%';
                })
                ->addColumn('sales', function($q) {
                    return strtoupper($q->inquiry->sales->name);
                })
                ->editColumn('status', function($q) {
                    return strtoupper($q->status);
                })
                ->addColumn('warning', function($q) {
                    $now = Carbon::now();
                    $date = Carbon::parse($q->due_date);
                    $daysDifference = $date->diffInDays($now);
                    if($daysDifference <= 2) {
                        return true;
                    } else {
                        return false;
                    }
                })
                ->make(true);
    
            return $result;
        }
    }
    
    public function data_customer(Request $request) : JsonResponse 
    {
        if($request->ajax()) {
            $data = SalesOrder::whereHas('inquiry.visit.customer', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->value . '%');
                })
                ->orderBy('created_at', 'DESC')->get();

            $result = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('id_so', function($q) {
                    return $q->id;
                })
                ->addColumn('id_inquiry', function($q) {
                    return $q->inquiry->id;
                })
                ->addColumn('customer', function($q) {
                    return strtoupper($q->inquiry->visit->customer->name . ' - ' . $q->inquiry->visit->customer->company);
                })
                ->addColumn('due_date', function($q) {
                    $due_date = Carbon::parse($q->inquiry->due_date)->format('d M Y');
                    return $due_date;
                })
                ->addColumn('grade', function($q) {
                    return $q->inquiry->grade . '%';
                })
                ->addColumn('sales', function($q) {
                    return strtoupper($q->inquiry->sales->name);
                })
                ->editColumn('status', function($q) {
                    return strtoupper($q->status);
                })
                ->addColumn('warning', function($q) {
                    $now = Carbon::now();
                    $date = Carbon::parse($q->due_date);
                    $daysDifference = $date->diffInDays($now);
                    if($daysDifference <= 2) {
                        return true;
                    } else {
                        return false;
                    }
                })
                ->make(true);
    
            return $result;
        }
    }
    
    public function data_sales(Request $request) : JsonResponse 
    {
        if($request->ajax()) {
            $data = SalesOrder::whereHas('inquiry.sales', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->value . '%');
                })
                ->orderBy('created_at', 'DESC')->get();

            $result = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('id_so', function($q) {
                    return $q->id;
                })
                ->addColumn('id_inquiry', function($q) {
                    return $q->inquiry->id;
                })
                ->addColumn('customer', function($q) {
                    return strtoupper($q->inquiry->visit->customer->name . ' - ' . $q->inquiry->visit->customer->company);
                })
                ->addColumn('due_date', function($q) {
                    $due_date = Carbon::parse($q->inquiry->due_date)->format('d M Y');
                    return $due_date;
                })
                ->addColumn('grade', function($q) {
                    return $q->inquiry->grade . '%';
                })
                ->addColumn('sales', function($q) {
                    return strtoupper($q->inquiry->sales->name);
                })
                ->editColumn('status', function($q) {
                    return strtoupper($q->status);
                })
                ->addColumn('warning', function($q) {
                    $now = Carbon::now();
                    $date = Carbon::parse($q->due_date);
                    $daysDifference = $date->diffInDays($now);
                    if($daysDifference <= 2) {
                        return true;
                    } else {
                        return false;
                    }
                })
                ->make(true);
    
            return $result;
        }
    }
    
    public function add($id = null) : View
    {
        $redis = Redis::keys('*');
        foreach($redis as $item) {
            if(str_contains($item, 'so_excel_')) {
                if(str_contains($item, auth()->user()->uuid)) {
                    $item = explode('so_excel_', $item);
                    $item = 'so_excel_' . $item[1];
                    Redis::del($item);
                }
            }
        }

        if($id) {
            $inquiry = Inquiry::where('uuid', $id)->first();

            return view('transaction.sales-order.add', compact('inquiry'));
        }
        return view('transaction.sales-order.add');
    }
    
    public function download_template()
    {
        $data = array();

        return Excel::download(new SalesOrderExcelExport($data), 'template_so.xlsx');
    }

    public function generate_id() : JsonResponse
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $code = 'SO';
        $month = (int) date('m');
        $year = date('y');
        
        $last_data = SalesOrder::orderBy('created_at', 'DESC')->withTrashed()->first();
        
        if ($last_data) {
            $last_id = $last_data->id;
            $id = explode("/", $last_id);
            $number = (int) $id[0];
            $number++;
        } else {
            $number = 1;
        }

        $generate_id = sprintf("%04s", $number) . "/" . $code . "/" . $romans[$month - 1] . "/" . $year;
        
        return response()->json($generate_id);
    }
    
    public function inquiries() : JsonResponse
    {
        $inquiries = Inquiry::whereNotIn('id', SalesOrder::select('inquiry_id')->get())
            ->get();
        
        $result = array();

        foreach ($inquiries as $item) {
            $result[] = array(
                'id' => $item->id,
                'uuid' => $item->uuid,
            );
        }

        return response()->json($result);
    }

    public function inquiry_detail($id) : JsonResponse
    {
        $redis = Redis::keys('*');
        foreach($redis as $item) {
            if(str_contains($item, auth()->user()->uuid)) {
                if($this->hosting == false) {
                    $item = explode('database_', $item);
                    $item = $item[1];
                }
                Redis::del($item);
            }
        }

        $inquiry = Inquiry::where('uuid', $id)->first();

        $result = array(
            'sales' => $inquiry->visit->sales->name,
            'customer' => $inquiry->visit->customer->name,
            'company' => $inquiry->visit->customer->company,
            'phone' => $inquiry->visit->customer->phone,
            'email' => $inquiry->visit->customer->email,
            'company_phone' => $inquiry->visit->customer->company_phone,
            'subject' => $inquiry->subject,
            'due_date' => Carbon::parse($inquiry->due_date)->format('d M Y'),
            'grade' => $inquiry->grade,
            'note' => $inquiry->description,
            'uuid' => $inquiry->visit->uuid
        );

        $so = $inquiry->visit->uuid;
        $key = 'so_pdf_' . $so . '_' . auth()->user()->uuid;
        $redis = Redis::get($key);
        if($redis) {
            Redis::del($key);
        }
        Redis::set($key, $inquiry->files);

        $so = $inquiry->visit->uuid;
        $key = 'so_product_' . $so . '_' . auth()->user()->uuid;
        $redis = Redis::get($key);
        if($redis) {
            Redis::del($key);
        }
        if(isset($inquiry->products)) {
            $data = array();
            
            foreach($inquiry->products as $item) {
                $data[] = array(
                    $item->item_name,
                    $item->description,
                    $item->size,
                    $item->qty,
                    $item->remark,
                );
            }

            Redis::set($key, json_encode($data));
        }

        return response()->json($result);  
    }
    
    public function get_pdf(Request $request) : JsonResponse
    {
        $so = $request->so;
        $inquiry = Inquiry::where('uuid', $so)->first();
        $key = 'so_pdf_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
        $data = json_decode(Redis::get($key), true);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $data
        ]);
    }
    
    public function upload_pdf(Request $request) : JsonResponse
    {
        try {
            if($request->hasFile('file')) {
                $so = $request->so;
                $inquiry = Inquiry::where('uuid', $so)->first();
                $so = $inquiry->visit->uuid;
                $file = $request->file('file');
                $path = $so;
                $upload = $this->fileController->store_temp($file, $path);
                if($upload->original['status'] == 200) {
                    $key = 'so_pdf_' . $so . '_' . auth()->user()->uuid;
                    $data = $upload->original['data'];
                    $redis = $this->redisController->store($key, $data);
    
                    if($redis->original['status'] == 200) {
                        $data = json_decode(Redis::get($key), true);
    
                        return response()->json([
                            'status' => 200,
                            'message' => 'success',
                            'data' => $data
                        ]);
                    }
                }       
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'error'
            ]);
        }
    }
    
    public function delete_pdf(Request $request) : JsonResponse
    {
        try {
            $so = $request->so;
            $so = str_replace('/', '_', $so);
            $file = $request->file;
            if($request->has('edit')) {
                $path = 'public/so/' . $so . '/' . $file;
            } else {
                $path = 'temp/' . $so . '/' . $file;
            }
            $exist = Storage::exists($path);
            if($exist) {
                $delete = Storage::delete($path);
    
                if($delete) {
                    $key = 'so_pdf_' . $so . '_' . auth()->user()->uuid;
                    $redis = $this->redisController->delete_item($key, 'filename', $file);
                    if($redis->original['status'] == 200) {
                        $data = json_decode(Redis::get($key), true);

                        if(sizeof($data) == 0) {
                            Redis::del($key);
                        }
        
                        return response()->json([
                            'status' => 200,
                            'message' => 'success',
                            'data' => $data
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'error'
            ]);
        }
    }
    
    public function get_product(Request $request) : JsonResponse
    {
        $so = $request->so;
        $inquiry = Inquiry::where('uuid', $request->so)->first();
        $so = $inquiry->visit->uuid;
        $so = str_replace('/', '_', $so);
        $key = 'so_product_' . $so . '_' . auth()->user()->uuid;
        $data = json_decode(Redis::get($key), true);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $data
        ]);
    }

    public function upload_excel(Request $request) : JsonResponse
    {
        try {
            if($request->hasFile('file')) {
                $so = $request->so;
                $file = $request->file('file');
                
                $excel = Excel::toArray(null, $file, null, null, null, true, false, false)[0];
                $excel = array_slice($excel, 1);
                $data = array();

                foreach ($excel as $item) {
                    $data[] = array_slice($item, 1);
                }

                $key = 'so_product_' . $so . '_' . auth()->user()->uuid;
                $redis = Redis::get($key);

                if ($redis) {
                    $array = json_decode($redis, true);
                    $new = array();

                    foreach ($data as $item) {
                        $found = false;

                        foreach ($array as &$row) {
                            if ($item[0] == $row[0] && $item[2] == $row[2]) {
                                $row[3] += $item[3];
                                $found = true;
                                break;
                            }
                        }

                        if (!$found) {
                            $new[] = array($item[0], $item[1], $item[2], $item[3], $item[4]);
                        }
                    }

                    $array = array_merge($array, $new);

                    Redis::set($key, json_encode($array));
                } else {
                    Redis::set($key, json_encode($data));
                }

                $data = Redis::get($key);
                $data = json_decode($data, true);
                
                return response()->json([
                    'status' => 200,
                    'message' => 'success',
                    'data' => $data
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'error'
            ]);
        }
    }

    public function store_product(Request $request)
    {
        try {
            $so = $request->so;
            $inquiry = Inquiry::where('uuid', $request->so)->first();
            $so = $inquiry->visit->uuid;
            $key = 'so_product_' . $so . '_' . auth()->user()->uuid;
            $redis = Redis::get($key);
            $data = $request->data;

            if ($redis) {
                $redis = Redis::del($key);
            }

            Redis::set($key, json_encode($data));

            $data = Redis::get($key);
            $data = json_decode($data, true);
            
            return response()->json([
                'status' => 200,
                'message' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'error'
            ]);
        }
    }

    public function store(AddSalesOrderRequest $request) : RedirectResponse
    {
        try {
            DB::beginTransaction();
    
            $so = new SalesOrder();
            $so->id = $request->id;
            $so->inquiry_id = Inquiry::where('uuid', $request->inquiry)->first()->id;
            $so->status = 'On Process';
            // $so->files = ($request->pdf == 'null') ? '' : $request->pdf;
            $so->save();
    
            $inquiry = Inquiry::where('uuid', $request->inquiry)->first();
            $inquiry->files = $request->pdf;
            $inquiry->status = 'SO Ready';
            $inquiry->save();
    
            if ($request->pdf != 'null' && !empty($request->pdf) && $request->pdf != null) {
                
                $inquiry = Inquiry::where('uuid', $request->inquiry)->first();
                $inquiry->files = $request->pdf;
                $inquiry->save();

                $files = json_decode($request->pdf, true);
                foreach ($files as $item) {
                    $sourcePath = storage_path('app/temp/' . $inquiry->visit->uuid . '/' . $item['filename']);
                    $destinationPath = storage_path('app/public/inquiry/' . $inquiry->visit->uuid . '/' . $item['filename']);
                    
                    if (!Storage::exists('public/inquiry/' . $inquiry->visit->uuid)) {
                        Storage::makeDirectory('public/inquiry/' . $inquiry->visit->uuid);
                    }
            
                    if (file_exists($sourcePath)) {
                        rename($sourcePath, $destinationPath);
                    }
                }
    
                Storage::deleteDirectory('temp/' . $inquiry->visit->uuid);
            }

            $key = 'so_product_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
            $redis = Redis::get($key);

            if($redis) {
                $data = json_decode($redis, true);

                foreach($data as $key => $value) {
                    if(isset($inquiry->products[$key])) {
                        $product_id = $inquiry->products[$key]->id;
                        $product = InquiryProduct::find($product_id);
                        $product->item_name = $value[0];
                        $product->description = $value[1];
                        $product->size = $value[2];
                        $product->qty = $value[3];
                        $product->remark = $value[4];
                        $product->save();
                    } else {
                        $product = new InquiryProduct();
                        $product->inquiry_id = $inquiry->id;
                        $product->item_name = $value[0];
                        $product->description = $value[1];
                        $product->size = $value[2];
                        $product->qty = $value[3];
                        $product->remark = $value[4];
                        $product->save();
                    }
                }
            }
            
            $key = 'so_product_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
            Redis::del($key);
            
            $key = 'so_pdf_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
            Redis::del($key);
            
            DB::commit();
    
            return redirect()->route('transaction.sales-order')->with('success', Constants::STORE_DATA_SUCCESS_MSG);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', Constants::ERROR_MSG);
        }
    }

    public function view($id) : View
    {
        $so = SalesOrder::where('uuid', $id)->first();

        return view('transaction.sales-order.view', compact('so'));
    }

    public function download_product_list_excel($id)
    {
        $data = array();

        $inquiry = Inquiry::where('uuid', $id)->first();
        if($inquiry->products) {
            $no = 1;
            foreach($inquiry->products as $item) {
                $data[] = array(
                    $no++, 
                    $item->item_name, 
                    $item->description, 
                    $item->size, 
                    $item->qty, 
                    $item->remark, 
                );
            }
        }

        $name = str_replace('/', '_', $inquiry->id);

        return Excel::download(new SalesOrderExcelExport($data), 'product_list'. $name .'.xlsx');
    }

    public function download_product_list_pdf($id)
    {
        $data = array();

        $inquiry = Inquiry::where('uuid', $id)->first();
        if($inquiry->products) {
            $no = 1;
            foreach($inquiry->products as $item) {
                $data[] = array(
                    $no++, 
                    $item->item_name, 
                    $item->description, 
                    $item->size, 
                    $item->qty, 
                    $item->remark, 
                );
            }
        }

        $name = str_replace('/', '_', $inquiry->id);

        // Generate a PDF using dompdf
        $pdf = PDF::loadView('pdf.product_list', compact('data'))->setPaper('a4', 'landscape'); // Create a view for your PDF content

        // You can also customize the PDF options, such as filename and paper size
        return $pdf->download('product_list_' . $name . '.pdf');
    }

    public function edit($id) : View
    {
        $so = SalesOrder::where('uuid', $id)->first();

        return view('transaction.sales-order.edit', compact('so'));
    }

    public function store_edit(EditSalesOrderRequest $request) : RedirectResponse
    {
        try {
            DB::beginTransaction();
    
            $so = SalesOrder::where('uuid', $request->uuid)->first();
            $so->inquiry_id = Inquiry::where('uuid', $request->inquiry)->first()->id;
            $so->status = 'On Process';
            // $so->files = ($request->pdf == 'null') ? '' : $request->pdf;
            $so->save();
    
            $inquiry = Inquiry::where('uuid', $request->inquiry)->first();
            $inquiry->status = 'SO Ready';
            $inquiry->save();
    
            if ($request->pdf != 'null' && !empty($request->pdf) && $request->pdf != null) {
                
                $inquiry = Inquiry::where('uuid', $request->inquiry)->first();
                $inquiry->files = $request->pdf;
                $inquiry->save();

                $files = json_decode($request->pdf, true);
                foreach ($files as $item) {
                    $sourcePath = storage_path('app/temp/' . $inquiry->visit->uuid . '/' . $item['filename']);
                    $destinationPath = storage_path('app/public/inquiry/' . $inquiry->visit->uuid . '/' . $item['filename']);
                    
                    if (!Storage::exists('public/inquiry/' . $inquiry->visit->uuid)) {
                        Storage::makeDirectory('public/inquiry/' . $inquiry->visit->uuid);
                    }
            
                    if (file_exists($sourcePath)) {
                        rename($sourcePath, $destinationPath);
                    }
                }
    
                Storage::deleteDirectory('temp/' . $inquiry->visit->uuid);
            }

            $key = 'so_product_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
            $redis = Redis::get($key);

            if($redis) {
                $data = json_decode($redis, true);

                foreach($data as $key => $value) {
                    if(isset($inquiry->products[$key])) {
                        $product_id = $inquiry->products[$key]->id;
                        $product = InquiryProduct::find($product_id);
                        $product->item_name = $value[0];
                        $product->description = $value[1];
                        $product->size = $value[2];
                        $product->qty = $value[3];
                        $product->remark = $value[4];
                        $product->save();
                    } else {
                        $product = new InquiryProduct();
                        $product->inquiry_id = $inquiry->id;
                        $product->item_name = $value[0];
                        $product->description = $value[1];
                        $product->size = $value[2];
                        $product->qty = $value[3];
                        $product->remark = $value[4];
                        $product->save();
                    }
                }
            }
            
            $key = 'so_product_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
            Redis::del($key);
            
            $key = 'so_pdf_' . $inquiry->visit->uuid . '_' . auth()->user()->uuid;
            Redis::del($key);
            
            DB::commit();
    
            return redirect()->route('transaction.sales-order')->with('success', Constants::STORE_DATA_SUCCESS_MSG);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', Constants::ERROR_MSG);
        }
    }
    
    public function delete($id)
    {
        try {
            $so = SalesOrder::where('uuid', $id)->first();

            DB::beginTransaction();

            $inquiry = Inquiry::find($so->inquiry_id);
            $inquiry->status = 'Loading';
            $inquiry->save();

            foreach($so->products as $item) {
                $item->delete();
            }

            $so->delete();

            DB::commit();
    
            return redirect()->back()->with('delete', Constants::STORE_DATA_DELETE_MSG);

        } catch(\Exception $e) {
            return redirect()->back()->with('error', Constants::ERROR_MSG);
        }
    }
}
