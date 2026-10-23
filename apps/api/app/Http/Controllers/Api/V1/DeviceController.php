<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Devices\DeviceService;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use LogicException;

class DeviceController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(1, min(100, $perPage));

        return DeviceResource::collection(
            $request->user()->devices()->orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    public function store(Request $request, DeviceService $service)
    {
        $data = $request->validate([
            'platform' => ['required', 'string', 'max:50'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        return (new DeviceResource($service->register($request->user(), $data)))->response()->setStatusCode(201);
    }

    public function revoke(Request $request, Device $device, DeviceService $service)
    {
        abort_unless($device->user_id === $request->user()->id, 404);

        try {
            return new DeviceResource($service->revoke($device));
        } catch (LogicException $e) {
            abort(422, $e->getMessage());
        }
    }
}
