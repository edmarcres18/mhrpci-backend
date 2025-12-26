<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryCodeController extends Controller
{
    /**
    * Get all inventory item codes.
    */
    public function index(): JsonResponse
    {
        $codes = Inventory::whereNotNull('item_code')->pluck('item_code');

        return response()->json(['success' => true, 'data' => $codes]);
    }

    /**
    * Generate QR and barcode images for all inventories and return summary.
    */
    public function generateAll(): JsonResponse
    {
        $items = Inventory::whereNotNull('item_code')->get();
        $generated = 0;
        foreach ($items as $item) {
            $prevQr = $item->qr_code_path;
            $prevBarcode = $item->barcode_path;
            $item->ensureCodeImages();
            if ($item->qr_code_path !== $prevQr || $item->barcode_path !== $prevBarcode) {
                $generated++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $items->count(),
                'updated' => $generated,
            ],
        ]);
    }

    /**
    * Download QR image for an item code as png or jpg.
    */
    public function downloadQr(Request $request, string $itemCode)
    {
        return $this->downloadImage($request, $itemCode, 'qr');
    }

    /**
    * Download barcode image for an item code as png or jpg.
    */
    public function downloadBarcode(Request $request, string $itemCode)
    {
        return $this->downloadImage($request, $itemCode, 'barcode');
    }

    private function downloadImage(Request $request, string $itemCode, string $type)
    {
        $inventory = Inventory::where('item_code', $itemCode)->firstOrFail();
        $inventory->ensureCodeImages();

        $path = $type === 'qr' ? $inventory->qr_code_path : $inventory->barcode_path;
        $disk = Storage::disk('public');
        if (! $path || ! $disk->exists($path)) {
            abort(404, 'Image not found');
        }

        $format = strtolower((string) $request->get('format', 'png'));
        $format = $format === 'jpeg' ? 'jpg' : $format;
        if (! in_array($format, ['png', 'jpg'], true)) {
            $format = 'png';
        }

        $preview = (bool) $request->boolean('preview');
        $fileName = $itemCode . '_' . $type . '.' . $format;
        $fullPath = $disk->path($path);

        if ($format === 'png') {
            $content = $disk->get($path);
            $headers = [
                'Content-Type' => 'image/png',
            ];
            if (! $preview) {
                $headers['Content-Disposition'] = 'attachment; filename="' . $fileName . '"';
            }
            return response($content, 200, $headers);
        }

        // Convert to JPG on the fly using GD
        $image = @imagecreatefromstring($disk->get($path));
        if ($image === false) {
            abort(500, 'Failed to read image');
        }
        ob_start();
        imagejpeg($image, null, 90);
        $data = ob_get_clean();
        imagedestroy($image);

        $headers = [
            'Content-Type' => 'image/jpeg',
        ];
        if (! $preview) {
            $headers['Content-Disposition'] = 'attachment; filename="' . $fileName . '"';
        } else {
            $headers['Content-Disposition'] = 'inline; filename="' . $fileName . '"';
        }

        return response($data, 200, $headers);
    }

    /**
     * Download a PDF of all generated codes (QR or Barcode).
     */
    public function printAll(Request $request)
    {
        $type = $request->get('type', 'qr') === 'barcode' ? 'barcode' : 'qr';
        $size = strtolower((string) $request->get('size', 'letter'));
        $pageSize = in_array($size, ['a4', 'letter'], true) ? $size : 'letter';

        $nowLabel = now()->format('F Y');
        $items = Inventory::whereNotNull('item_code')->orderBy('inventory_accountable')->orderBy('inventory_name')->get();
        $payload = [];
        $publicDisk = Storage::disk('public');
        foreach ($items as $item) {
            $item->ensureCodeImages();
            $path = $type === 'barcode' ? $item->barcode_path : $item->qr_code_path;
            $imageData = null;
            if ($path && $publicDisk->exists($path)) {
                $binary = $publicDisk->get($path);
                $imageData = 'data:image/png;base64,' . base64_encode($binary);
            }
            $payload[] = [
                'inventory_accountable' => $item->inventory_accountable,
                'inventory_name' => $item->inventory_name,
                'item_code' => $item->item_code,
                'image' => $imageData,
            ];
        }

        $pdf = Pdf::loadView('inventories.codes-pdf', [
            'items' => $payload,
            'type' => $type,
            'pageSize' => $pageSize,
            'labelMonthYear' => $nowLabel,
        ])->setPaper($pageSize, 'portrait');

        $ts = now()->format('Ymd_His');
        $filename = $type === 'qr' ? "inventory_qr_codes_{$ts}.pdf" : "inventory_barcode_codes_{$ts}.pdf";

        return $pdf->download($filename);
    }
}
