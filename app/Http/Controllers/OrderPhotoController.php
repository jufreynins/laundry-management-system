<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\UploadOrderPhotoRequest;
use App\Models\Order;
use App\Models\OrderPhoto;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderPhotoController extends Controller
{
    public function store(UploadOrderPhotoRequest $request, Order $order): RedirectResponse
    {
        $file = $request->file('photo');

        $randomName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $diskPath = $file->storeAs("order-photos/{$order->id}", $randomName, 'local');

        $photo = OrderPhoto::create([
            'order_id' => $order->id,
            'disk_path' => $diskPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLogService::record(AuditAction::CREATED, $photo, null, ['order_id' => $order->id], locationId: $order->location_id);

        return back()->with('status', 'Photo uploaded successfully.');
    }

    public function show(Order $order, OrderPhoto $photo): StreamedResponse
    {
        $this->authorize('view', $order);

        abort_unless($photo->order_id === $order->id, 404);
        abort_unless(Storage::disk('local')->exists($photo->disk_path), 404);

        return Storage::disk('local')->response($photo->disk_path, null, [
            'Content-Type' => $photo->mime_type,
        ]);
    }
}
