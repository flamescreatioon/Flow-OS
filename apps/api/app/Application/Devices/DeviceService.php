<?php

namespace App\Application\Devices;

use App\Models\Device;
use App\Models\User;
use LogicException;

final class DeviceService
{
    public function register(User $user, array $data): Device
    {
        $device = new Device($data);
        $device->user_id = $user->id;
        $device->last_seen_at = now();
        $device->save();

        return $device;
    }

    public function touch(Device $device): Device
    {
        if ($device->isRevoked()) {
            throw new LogicException('Revoked devices cannot check in.');
        }

        $device->forceFill(['last_seen_at' => now()])->save();

        return $device;
    }

    public function revoke(Device $device): Device
    {
        $device->forceFill(['revoked_at' => now()])->save();

        return $device;
    }
}
