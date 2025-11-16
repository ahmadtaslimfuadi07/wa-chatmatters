<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\Smstransaction;
use App\Models\Downline;
use App\Traits\Notifications;
use DB;
use Auth;
use Hash;
class CustomerController extends Controller
{
    
    use Notifications;

    private $positions = ['Sales', 'Store Manager', 'Area Manager', 'CSO', 'CEO'];

    public function __construct(){
         $this->middleware('permission:customer'); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customers = User::query();

        if (!empty($request->search)) {
             $customers = $customers->where($request->type,'LIKE','%'.$request->search.'%');  
        }

        $customers = $customers->where('role','user')->with('subscription')->withCount('orders')->latest()->paginate(20);
        $type = $request->type ?? '';

        $totalCustomers= User::where('role','user')->count();
        $totalActiveCustomers= User::where('role','user')->where('status',1)->count();
        $totalSuspendedCustomers= User::where('role','user')->where('status',0)->count();
        $totalExpiredCustomers= User::where('role','user')->where('will_expire','<=',now())->count();


        return view('admin.customers.index',compact('customers','request','type','totalCustomers','totalActiveCustomers','totalSuspendedCustomers','totalExpiredCustomers'));
    }
   

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customer = User::query()->withCount('orders')->withCount('contact')->withCount('device')->withSum('orders','amount')->withCount('smstransaction')->with('subscription')->findorFail($id);
        $superior = Downline::where('downline_user_id', $customer->id)->with(['user' => function($query) { $query->select('id', 'name', 'position'); }])->first()->user ?? null;
        return view('admin.customers.show',compact('customer', 'superior'));
    }
    
    public function create()
    {
        $positions = $this->positions;
        return view('admin.customers.create',compact('positions'));
    }

    public function store(Request $request)
    {
       // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone'    => 'required|numeric|unique:users,phone',
            'position' => 'required|string|max:50',
            'superior' => 'nullable|integer',
            'password' => 'required|string|min:8|confirmed',
            'max_device' => 'required|integer',
            'will_expire' => 'required|date',
            'status' => 'required|boolean',
            'allow_delete_device' => 'required|integer',
            'allow_broadcast' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            // Create a new user using the validated data
            $customer = new User();
            $customer->name = $validatedData['name'];
            $customer->email = $validatedData['email'];
            $customer->phone = $validatedData['phone'];
            $customer->position = $validatedData['position'];
            $customer->password = Hash::make($validatedData['password']); // Hash the password
            $customer->max_device = $validatedData['max_device'];
            $customer->will_expire = $validatedData['will_expire'];
            $customer->status = $validatedData['status'];
            $customer->allow_delete_device = $validatedData['allow_delete_device'];
            $customer->allow_broadcast = $validatedData['allow_broadcast'];

            // Save the user to the database
            $customer->save();

            // Create downline
            if (!empty($validatedData['superior'])) {
                $downline = new Downline();
                $downline->user_id = $validatedData['superior'];
                $downline->downline_user_id = $customer->id;
                $downline->save();
            };

            DB::commit();
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

        // Redirect to a success page or return a response as needed
        return response()->json([
            'message' => __('User created successfully !!'),
            'redirect' => route('admin.customer.index')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $positions = $this->positions;
        $customer = User::query()->where('role','user')->findorFail($id);
        $queryPosition = $this->getQueryPosition($customer->position, $positions);
        $superiors = User::where('position', $queryPosition)->get(['name', 'id']);
        $currentSuperiorId = Downline::where('downline_user_id', $customer->id)->value('user_id');
       
        return view('admin.customers.edit',compact('positions', 'customer', 'superiors', 'currentSuperiorId'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'password' => ['nullable', 'min:8', 'max:100'],
            'name'     => ['required', 'string'],
            'email'    => 'required|email|unique:users,email,'.$id,
            'phone'    => 'required|numeric|unique:users,phone,'.$id,
            'position' => 'required|string|max:50',
            'superior' => 'nullable|integer',
            'max_device' => 'required|integer',
            'will_expire' => 'required|date',
            'allow_delete_device' => 'required|integer',
            'allow_broadcast' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $customer = User::query()->where('role','user')->findorFail($id);
            $customer->name = $request->name;
            $customer->email = $request->email;
            $customer->status = $request->status;
            $customer->phone = $request->phone;
            $customer->address = $request->address;
            $customer->position = $request->position;
            $customer->max_device = $request->max_device;
            $customer->will_expire = $request->will_expire;
            if ($request->password) {
                $customer->password = Hash::make($request->password);
            }
            $customer->allow_delete_device = $request->allow_delete_device;
            $customer->allow_broadcast = $request->allow_broadcast;
            $customer->save();

            // Update downline or create
            if (!empty($request->superior)) {
                $downline = Downline::where('downline_user_id', $customer->id)->first();
                if (!$downline) {
                    $downline = new Downline();
                }
                $downline->user_id = $request->superior;
                $downline->downline_user_id = $customer->id;
                $downline->save();
            };

            DB::commit();
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

        $title = 'Your account information has changed by admin';
        
        $notification['user_id'] = $customer->id;
        $notification['title']   = $title;
        $notification['url'] = '/user/profile';

        $this->createNotification($notification);

        return response()->json([
            'redirect' => route('admin.customer.index'),
            'message'  => __('User Updated successfully.')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::where('role','user')->findorFail($id);
        $user->delete();

        return response()->json([
            'redirect' => route('admin.customer.index'),
            'message'  => __('User deleted successfully.')
        ]);
    }

    public function getSuperiors(Request $request)
    {
        $positions = $this->positions;
        $selectedPosition = $request->input('selected_position');
        $queryPosition = $this->getQueryPosition($selectedPosition, $positions);

        $superiors = User::where('position', $queryPosition)->get(['name', 'id']);

        return response()->json($superiors);
    }

    private function getQueryPosition($selectedPosition, $positions)
    {
        $selectedPositionIndex = array_search($selectedPosition, $positions);
        return $positions[$selectedPositionIndex + 1] ?? null;
    }
}
