<?php

namespace App\Models;

use App\Enums\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_accountable',
        'inventory_name',
        'inventory_specification',
        'inventory_brand',
        'inventory_status',
        'location',
        'item_code',
        'qr_code_path',
        'barcode_path',
    ];

    protected $casts = [
        'location' => InventoryLocation::class,
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($inventory) {
            if (empty($inventory->item_code)) {
                $inventory->item_code = self::generateUniqueItemCode(
                    $inventory->inventory_name,
                    $inventory->inventory_accountable
                );
            }
        });
    }

    /**
     * Generate a unique item code for a given inventory name and accountable person.
     * This method uses a transaction and pessimistic locking to prevent race conditions.
     *
     * @param string $inventoryName
     * @param string $inventoryAccountable
     * @return string
     */
    public static function generateUniqueItemCode(string $inventoryName, string $inventoryAccountable): string
    {
        $codeBody = self::generateItemCodeBody($inventoryName, $inventoryAccountable);
        $fullPrefix = 'IT-' . $codeBody;
        $maxTries = 100; // a safe limit to prevent infinite loops

        for ($i = 0; $i < $maxTries; $i++) {
            $randomNumber = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $itemCode = $fullPrefix . $randomNumber;
            if (!self::where('item_code', $itemCode)->exists()) {
                return $itemCode;
            }
        }

        // Fallback for the very unlikely case of not finding a unique code
        return $fullPrefix . uniqid();
    }

    /**
     * Generate the body of the item code from the inventory name and accountable person.
     * e.g., "Laptop Computer" for "John Doe" becomes "JDLC"
     *
     * @param string $inventoryName
     * @param string $inventoryAccountable
     * @return string
     */
    public static function generateItemCodeBody(string $inventoryName, string $inventoryAccountable): string
    {
        $accountableInitials = self::getInitials($inventoryAccountable);
        $nameInitials = self::getInitials($inventoryName);

        return $accountableInitials . $nameInitials;
    }

    /**
     * Get initials from a string.
     *
     * @param string $str
     * @param int $wordLimit
     * @return string
     */
    private static function getInitials(string $str, int $wordLimit = 2): string
    {
        $words = preg_split('/[\s,-]+/', $str);
        $initials = '';
        $wordCount = 0;
        foreach ($words as $word) {
            if (!empty($word) && $wordCount < $wordLimit) {
                $initials .= strtoupper(substr($word, 0, 1));
                $wordCount++;
            }
        }

        if (empty($initials)) {
            $initials = strtoupper(substr(str_replace(' ', '', $str), 0, $wordLimit));
        }

        return $initials;
    }

    /**
     * Generate QR and barcode images for the inventory item_code and persist their storage paths.
     */
    public function ensureCodeImages(): void
    {
        if (empty($this->item_code)) {
            return;
        }

        $disk = Storage::disk('public');
        $baseDir = 'inventory-codes';
        $dirty = false;
        $monthYear = now()->format('Ym');
        $labelDate = strtoupper(now()->format('F Y'));

        // Always regenerate each call to guarantee label presence and month/year
        $targetQr = $baseDir . '/' . $this->item_code . '_' . $monthYear . '_qr.png';
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($this->item_code)
            ->encoding(new Encoding('UTF-8'))
            ->size(400)
            ->margin(2)
            ->build();

        $qrLabeled = self::addLabelToImage($result->getString(), $labelDate, (string) $this->inventory_accountable);
        $disk->put($targetQr, $qrLabeled);
        $this->qr_code_path = $targetQr;
        $dirty = true;

        // Barcode
        $targetBarcode = $baseDir . '/' . $this->item_code . '_' . $monthYear . '_barcode.png';
        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($this->item_code, $generator::TYPE_CODE_128, 2, 80);

        $barcodeLabeled = self::addLabelToImage($barcodeData, $labelDate, (string) $this->inventory_accountable);
        $disk->put($targetBarcode, $barcodeLabeled);
        $this->barcode_path = $targetBarcode;
        $dirty = true;

        if ($dirty) {
            $this->saveQuietly();
        }
    }

    /**
     * Add a header (accountable) above and month/year label beneath a PNG binary using GD built-in fonts.
     */
    private static function addLabelToImage(string $imageBinary, string $label, string $header = ''): string
    {
        try {
            $src = @imagecreatefromstring($imageBinary);
            if (! $src) {
                return $imageBinary;
            }

            $width = imagesx($src);
            $height = imagesy($src);
            $padding = 24;
            $labelGap = 12;
            $labelText = strtoupper($label);
            $headerText = trim($header);
            $fontPath = self::getPreferredFontPath();
            $headerFontSize = 22;
            $labelFontSize = 24;

            $hasTtf = $fontPath && function_exists('imagettfbbox');

            if ($hasTtf) {
                $labelBox = imagettfbbox($labelFontSize, 0, $fontPath, $labelText) ?: [0, 0, 0, 0, 0, 0, 0, 0];
                $headerBox = $headerText !== '' ? (imagettfbbox($headerFontSize, 0, $fontPath, $headerText) ?: [0, 0, 0, 0, 0, 0, 0, 0]) : [0, 0, 0, 0, 0, 0, 0, 0];
                $labelWidth = abs($labelBox[4] - $labelBox[0]);
                $labelHeight = abs($labelBox[5] - $labelBox[1]);
                $headerWidth = $headerText !== '' ? abs($headerBox[4] - $headerBox[0]) : 0;
                $headerHeight = $headerText !== '' ? abs($headerBox[5] - $headerBox[1]) + 8 : 0;
            } else {
                $font = 5; // built-in
                $labelWidth = imagefontwidth($font) * strlen($labelText);
                $labelHeight = imagefontheight($font) + 12;
                $headerWidth = $headerText !== '' ? imagefontwidth($font) * strlen($headerText) : 0;
                $headerHeight = $headerText !== '' ? imagefontheight($font) + 12 : 0;
            }

            $newWidth = max($width + ($padding * 2), $labelWidth + ($padding * 2), $headerWidth + ($padding * 2));
            $newHeight = $height + $labelHeight + $labelGap + $headerHeight + ($padding * 2);

            $dst = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($dst, 255, 255, 255);
            $black = imagecolorallocate($dst, 0, 0, 0);
            imagefill($dst, 0, 0, $white);

            $offsetX = (int) max(0, ($newWidth - $width) / 2);
            $offsetY = $headerHeight + $padding / 2;
            imagecopy($dst, $src, $offsetX, $offsetY, 0, 0, $width, $height);

            // Header (accountable) at top
            if ($headerHeight > 0) {
                $headerX = (int) max($padding / 2, ($newWidth - $headerWidth) / 2);
                $headerY = (int) ($padding / 2 + $headerHeight / 2);
                if ($hasTtf) {
                    imagettftext($dst, $headerFontSize, 0, $headerX, $headerY, $black, $fontPath, $headerText);
                } else {
                    imagestring($dst, 5, $headerX, max(0, $headerY - imagefontheight(5)), $headerText, $black);
                }
            }

            // Footer label (month/year)
            $textX = (int) max($padding / 2, ($newWidth - $labelWidth) / 2);
            $textY = (int) ($offsetY + $height + $labelGap + $labelHeight);
            if ($hasTtf) {
                imagettftext($dst, $labelFontSize, 0, $textX, $textY, $black, $fontPath, $labelText);
            } else {
                imagestring($dst, 5, $textX, $textY - imagefontheight(5), $labelText, $black);
            }

            ob_start();
            imagepng($dst);
            $output = ob_get_clean();

            imagedestroy($src);
            imagedestroy($dst);

            return $output ?: $imageBinary;
        } catch (\Throwable $e) {
            return $imageBinary;
        }
    }

    /**
     * Attempt to find an available TTF font (Arial/Calibri) on the host.
     */
    private static function getPreferredFontPath(): ?string
    {
        $candidates = [
            'C:\Windows\Fonts\arial.ttf',
            'C:\Windows\Fonts\calibri.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Calibri.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
