<?php

/**
 * @author: pujiermanto@gmail.com
 * */

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorPNG;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use Illuminate\Support\Facades\File;
use App\Models\{User, Roles, Login, History};

class WebFeatureHelpers
{

    protected $data = [];

    public function __construct($data=null)
    {
        $this->data = $data;
    }

    public function formatTextForPenjualan($penjualan, $barangs, $toko)
    {
        // Lebar maksimum untuk kolom kiri dan kanan
        $leftColWidth = 40;
        $rightColWidth = 40;

        // Header informasi pelanggan dan toko
        $header = "";
        $header .= "INVOICE\n";
        $header .= "Kepada\n";
        $header .= str_pad($penjualan->pelanggan_nama . " (" . $penjualan->pelanggan_nama . ")", $leftColWidth);
        $header .= "\n";

        $header .= str_pad("", 73);
        $header .= str_pad($toko->name, 78). "\n";
        $header .= str_pad("", 73);
        $header .= str_pad($toko->address, 40, ' ', STR_PAD_RIGHT) . "\n";
        $header .= str_pad("", 73);
        $header .= str_pad($toko->kota . ", " . $toko->provinsi, 40);
        $header .= str_pad("", 22) . "\n\n";
        $header .= str_pad("", 14);
        $header .= str_pad("Tanggal: " . now()->toDateString(), 78, ' ', STR_PAD_LEFT) . "\n";
        $header .= str_pad("", 22);
        $header .= str_pad("NO INVOICE: " . $penjualan->kode, 78, ' ', STR_PAD_LEFT) . "\n";

        $headers = ["No", "Tanggal Transaksi","Barang", "Harga Satuan", "Qty", "Saldo Piutang", "Total"];
        $data = [];

        $data[] = $headers;

        foreach ($barangs as $key => $barang) {
            $data[] = [
                $key += 1,
                $this->format_tanggal_ascii($barang->tanggal),
                $barang->barang_nama,
                $this->format_uang($barang->harga_toko),
                intval($barang->qty).$barang->satuan,
                $this->format_uang($barang->saldo_piutang),
                $this->format_uang($barang->subtotal)
            ];
        }

        $colWidths = [];
        foreach ($data as $row) {
            foreach ($row as $i => $cell) {
                $colWidths[$i] = max(isset($colWidths[$i]) ? $colWidths[$i] : 0, strlen($cell));
            }
        }

        $line = '+';
        foreach ($colWidths as $width) {
            $line .= str_repeat('-', $width + 2) . '+';
        }

        $output = $header . $line . "\n|";
        foreach ($data[0] as $i => $cell) {
            $output .= ' ' . str_pad($cell, $colWidths[$i]) . ' |';
        }
        $output .= "\n" . $line;

        for ($i = 1; $i < count($data); $i++) {
            $output .= "\n|";
            foreach ($data[$i] as $j => $cell) {
                $output .= ' ' . str_pad($cell, $colWidths[$j]) . ' |';
            }
        }

        $output .= "\n" . $line;

        $output .= "\n";

        $output .= str_pad("Total : ", 86, ' ', STR_PAD_RIGHT) . $this->format_uang($penjualan->jumlah) . "\n";
        $output .= str_pad("Bayar : ", 86, ' ', STR_PAD_RIGHT) . $this->format_uang($penjualan->bayar) . "\n";
        $output .= str_pad("", 86) . "\n"; 
        $output .= str_pad("Kembali : ", 86, ' ', STR_PAD_RIGHT) . $this->format_uang($penjualan->kembali) . "\n";
        $output .= "\n\n" . str_pad("Kasir", 96, ' ', STR_PAD_LEFT) . "\n";
        $output .= "\n\n" . str_pad($penjualan->operator, 96, ' ', STR_PAD_LEFT) . "\n";

        return $output;
    }



    function convertCurrencyToInteger($currencyString) {
        $cleanedString = preg_replace('/[^0-9]/', '', $currencyString);
        return (int) $cleanedString;
    }

    public function format_tanggal($tanggal)
    {
        $carbonDate = Carbon::parse($tanggal);
        Carbon::setLocale('id');
        // Format ke dalam bahasa Indonesia
        $formatIndonesia = $carbonDate->isoFormat('dddd, D MMMM YYYY');

        echo $formatIndonesia;
    }

    public function format_tanggal_ascii($tanggal)
    {
        $carbonDate = Carbon::parse($tanggal);
        Carbon::setLocale('id');
        // Format ke dalam bahasa Indonesia
        $formatIndonesia = $carbonDate->isoFormat('D MMMM YYYY');

        return $formatIndonesia;
    }

    public function format_tanggal_transaksi($tanggal)
    {
        $carbonDate = Carbon::parse($tanggal);
        Carbon::setLocale('id');
        // Format ke dalam bahasa Indonesia
        $formatIndonesia = $carbonDate->isoFormat('D MMMM YYYY');

        echo $formatIndonesia;
    }

    public function generate_norek($string) 
    {
        $numericString = str_replace(" ", "", $string);

        $dashPositions = [2, 5, 11]; 
        $formattedString = "";

        for ($i = 0; $i < strlen($numericString); $i++) {
            $formattedString .= $numericString[$i];

            if (in_array($i, $dashPositions)) {
                $formattedString .= "-";
            }
        }

        echo $formattedString;
    }

    public function format_date_only($tanggal)
    {
        $carbonDate = Carbon::parse($tanggal);
        Carbon::setLocale('id');
        $formatIndonesia = $carbonDate->isoFormat('D MMMM YYYY');

        echo $formatIndonesia;
    }

    public static function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function get_total_user($role)
    {
        switch ($role):
            case 'ADMIN':
            $total = User::whereNull('deleted_at')
            ->whereRole(2)
            ->get();
            return count($total);
            break;

            case 'KASIR':
            $total = User::whereNull('deleted_at')
            ->whereRole(3)
            ->get();
            return count($total);
            break;

            case 'KASIR_GUDANG':
            $total = User::whereNull('deleted_at')
            ->whereRole(6)
            ->get();
            return count($total);
            break;

            case 'PRODUKSI':
            $total = User::whereNull('deleted_at')
            ->whereRole(5)
            ->get();
            return count($total);
            break;

            case 'GUDANG':
            $total = User::whereNull('deleted_at')
            ->whereRole(4)
            ->get();
            return count($total);
            break;

            default:
            return 0;
        endswitch;
    }

    public function user_online()
    {
        $user_is_online = User::whereIsLogin(1)
        ->get();
        return count($user_is_online);
    }

    public function createThumbnail($path, $width, $height)
    {
        $img = Image::make($path)->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save($path);
    }

    public static function makeAvatar($fontPath, $dest, $char)
    {
        $path = $dest;
        $image = imagecreate(200, 200);
        $red = rand(0, 255);
        $green = rand(0, 255);
        $blue = rand(0, 255);
        imagecolorallocate($image, $red, $green, $blue);
        $textcolor = imagecolorallocate($image, 255, 255, 255);
        imagettftext($image, 50, 0, 50, 125, $textcolor, $fontPath, $char);
        imagepng($image, $path);
        imagedestroy($image);
        return $path;
    }

    public function GatesAccess()
    {
        foreach ($this->data as $data) :
            Gate::define($data, function ($user) {
                $user_id = $user->id;
                $rolesUser = User::whereId($user_id)->with('roles')->get();
                $role = $rolesUser[0]->roles[0]->name;
                return $role === "MASTER" ? true :  false;
            });
        endforeach;
    }

    public function generateBarcode($data)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcodeFileName = $data . '_barcode.png';
        $barcodeDirectory = 'barcodes';

        if (!Storage::disk('public')->exists($barcodeDirectory)) {
            Storage::disk('public')->makeDirectory($barcodeDirectory, 0777, true, true);
        }

        $appUrl = env("APP_URL");
        $url = url($appUrl . "/detail/?type=barang&query={$data}");

        $binaryBarcode = $generator->getBarcode($url, $generator::TYPE_CODE_128);
        Storage::disk('public')->put("{$barcodeDirectory}/{$barcodeFileName}", $binaryBarcode);

        return $binaryBarcode;
    }


    public function generateQrCode($data)
    {
        $qr = new DNS2D;
        $appdUrl = env("APP_URL");
        $url = url($appdUrl . "/detail/?type=barang&query={$data}");

        $base64QrCode = $qr->getBarcodePNG($url, "QRCODE", 12, 12);

        $binaryQrCode = base64_decode($base64QrCode);

        $fileName = $data . '.png';

        $qrCodeDirectory = 'qrcodes';

        if (!Storage::disk('public')->exists($qrCodeDirectory)) {
            Storage::disk('public')->makeDirectory($qrCodeDirectory, 0777, true, true);
        }

        Storage::disk('public')->put("{$qrCodeDirectory}/{$fileName}", $binaryQrCode);

        $filePath = storage_path("app/public/{$qrCodeDirectory}/{$fileName}");

        return $filePath;
    }

    public function format_uang($angka) {
        $floatValue = floatval($angka);
        return number_format($floatValue, 0, ',', '.');
    }

    public function terbilang($angka)
    {
        $angka = abs(floatval($angka));
        $baca  = array('', 'satu', 'dua ', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas');
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = $baca[$angka];
        } elseif ($angka < 20) {
            $terbilang = $this->terbilang($angka - 10) . ' belas';
        } elseif ($angka < 100) {
            $terbilang = $this->terbilang(floor($angka / 10)) . ' puluh ' . $this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = ' seratus' . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = $this->terbilang(floor($angka / 100)) . ' ratus ' . $this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = ' seribu' . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = $this->terbilang(floor($angka / 1000)) . ' ribu ' . $this->terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = $this->terbilang(floor($angka / 1000000)) . ' juta ' . $this->terbilang($angka % 1000000);
        }


        return $terbilang;
    }

    public function tambah_nol_didepan($value, $threshold = null)
    {
        return sprintf("%0". $threshold . "s", $value);
    }


    public function tanggal_indonesia($tgl, $tampil_hari = true)
    {
        $nama_hari  = array(
            'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'
        );
        $nama_bulan = array(1 =>
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        );

        $tahun   = substr($tgl, 0, 4);
        $bulan   = $nama_bulan[(int) substr($tgl, 5, 2)];
        $tanggal = substr($tgl, 8, 2);
        $text    = '';

        if ($tampil_hari) {
            $urutan_hari = date('w', mktime(0,0,0, substr($tgl, 5, 2), $tanggal, $tahun));
            $hari        = $nama_hari[$urutan_hari];
            $text       .= "$hari, $tanggal $bulan $tahun";
        } else {
            $text       .= "$tanggal $bulan $tahun";
        }

        return $text; 
    }

    public function generateAcronym($inputString)
    {
        $cleanedString = preg_replace('/[^a-zA-Z0-9\s]/', '', $inputString);

        $words = explode(' ', $cleanedString);
        $acronym = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $acronym .= strtoupper(substr($word, 0, 1));
            }
        }

        $acronym .= rand(10, 99);

        return $acronym;
    }

    public function createHistory($data)
    {
        try {
            $currentDate = now('Asia/Jakarta')->toDateTimeString();

            $newHistory = new History;
            $newHistory->tanggal = $currentDate;
            $newHistory->user = $data['user'];
            $newHistory->keterangan = $data['keterangan'];
            $newHistory->routes = $data['routes'];
            $newHistory->route_name = $data['route_name'];
            $newHistory->save();

            return $newHistory;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
