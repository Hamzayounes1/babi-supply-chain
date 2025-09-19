<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shipment; // assume exists
use App\Models\Incident;
use App\Models\Report;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShipmentTrackingController extends Controller
{
    // GET /api/shipments (optionally filter by warehouse, status)
    public function index(Request $request)
    {
        $query = Shipment::with(['items','warehouse','createdBy'])->orderBy('created_at','desc');

        if ($request->filled('warehouse_id')) $query->where('warehouse_id', $request->get('warehouse_id'));
        if ($request->filled('status')) $query->where('status', $request->get('status'));
        if ($request->filled('shipment_id')) $query->where('id', $request->get('shipment_id'));

        // logistics coordinators should see everything; if you want to limit later, add filter
        $list = $query->get();
        return response()->json(['status'=>'ok','data'=>$list], 200);
    }

    // GET /api/shipments/{id}
    public function show($id)
    {
        $shipment = Shipment::with(['items','warehouse','incidents','reports','createdBy'])->find($id);
        if (!$shipment) return response()->json(['status'=>'error','message'=>'Not found'],404);
        return response()->json(['status'=>'ok','data'=>$shipment],200);
    }

    // POST /api/shipments/{id}/status
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        // Role check: allow logistics_coordinator, administrator, supply_chain_manager
        $role = $user->role ?? '';
        if (!in_array($role, ['logistics','administrator','supply_chain_manager'])) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied. Coordinator only.'],403);
        }

        $v = Validator::make($request->all(), [
            'status' => 'required|string',
            'note' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['status'=>'validation_error','errors'=>$v->errors()],422);

        $shipment = Shipment::find($id);
        if (!$shipment) return response()->json(['status'=>'error','message'=>'Shipment not found'],404);

        $old = $shipment->status;
        $shipment->status = $request->get('status');
        $shipment->save();

        // optional: create incident if note with severity = low
        if ($request->filled('note')) {
            Incident::create([
                'shipment_id' => $shipment->id,
                'type' => 'Status update note',
                'message' => $request->get('note'),
                'severity' => 'low',
                'reported_by' => $user->id,
            ]);
        }

        return response()->json(['status'=>'ok','data'=>$shipment,'old_status'=>$old],200);
    }

    // POST /api/shipments/{id}/incidents
    public function addIncident(Request $request, $id)
    {
        $user = $request->user();
        $role = $user->role ?? '';
        if (!in_array($role, ['logistics','warehouse_manager','administrator','supply_chain_manager','buyer'])) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied.'],403);
        }

        $v = Validator::make($request->all(), [
            'type' => 'nullable|string',
            'message' => 'required|string',
            'severity' => 'nullable|in:low,medium,high',
            'metadata' => 'nullable|array',
        ]);
        if ($v->fails()) return response()->json(['status'=>'validation_error','errors'=>$v->errors()],422);

        $shipment = Shipment::find($id);
        if (!$shipment) return response()->json(['status'=>'error','message'=>'Shipment not found'],404);

        $inc = Incident::create([
            'shipment_id' => $shipment->id,
            'type' => $request->get('type'),
            'message' => $request->get('message'),
            'severity' => $request->get('severity','medium'),
            'reported_by' => $user->id,
            'metadata' => $request->get('metadata') ?? null,
        ]);

        // Optionally: trigger push/notification logic here (out of scope)
        return response()->json(['status'=>'ok','data'=>$inc],201);
    }

    // GET /api/shipments/{id}/incidents
    public function listIncidents($id)
    {
        $incidents = Incident::where('shipment_id',$id)->orderBy('created_at','desc')->get();
        return response()->json(['status'=>'ok','data'=>$incidents],200);
    }

    // POST /api/shipments/{id}/reports create and share simple report
    public function createReport(Request $request, $id)
    {
        $user = $request->user();
        $role = $user->role ?? '';
        if (!in_array($role, ['logistics','administrator','supply_chain_manager'])) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied. Coordinator only.'],403);
        }

        $v = Validator::make($request->all(), [
            'title' => 'required|string',
            'payload' => 'nullable|array',
            'shared_with' => 'nullable|array',
        ]);
        if ($v->fails()) return response()->json(['status'=>'validation_error','errors'=>$v->errors()],422);

        $shipment = Shipment::find($id);
        if (!$shipment) return response()->json(['status'=>'error','message'=>'Shipment not found'],404);

        $r = Report::create([
            'shipment_id' => $shipment->id,
            'title' => $request->get('title'),
            'payload' => $request->get('payload') ?? null,
            'created_by' => $user->id,
            'shared_with' => $request->get('shared_with') ?? null,
        ]);

        // Respond with created report (sharing (email) logic would be implemented separately)
        return response()->json(['status'=>'ok','data'=>$r],201);
    }

    // optional: GET /api/incidents (for coordinator to see all incidents)
    public function incidentsIndex(Request $request)
    {
        $user = $request->user();
        $role = $user->role ?? '';
        if (!in_array($role, ['logistics','administrator','supply_chain_manager'])) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied. Coordinator only.'],403);
        }
        $q = Incident::with(['shipment','reporter'])->orderBy('created_at','desc');
        if ($request->filled('severity')) $q->where('severity',$request->get('severity'));
        $data = $q->get();
        return response()->json(['status'=>'ok','data'=>$data],200);
    }
}
