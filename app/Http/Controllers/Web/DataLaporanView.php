<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Exports\CampaignDataExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\ContextData;
use App\Models\{Pembelian, Toko, Hutang};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;
use Auth;
use PDF;

class DataLaporanView extends Controller
{

    private $helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }
    // public function laporan_pembelian_periode(Request $request, $id_perusahaan)
    // {
    //     $perusahaan = Toko::with('setup_perusahaan')
    //     ->findOrFail($id_perusahaan);

    //     $currentPage = $request->query('page', 1);

    //     // Set $limit to 10 to ensure only 10 data is fetched
    //     $limit = 10;

    //     $query = Pembelian::query()
    //     ->select(
    //         'pembelian.id',
    //         'pembelian.tanggal', 'pembelian.kode', 'pembelian.supplier', 'pembelian.operator', 'pembelian.jumlah', 'pembelian.bayar', 'pembelian.diskon', 'pembelian.tax',
    //         'itempembelian.qty', 'itempembelian.subtotal', 'itempembelian.harga_setelah_diskon',
    //         'supplier.nama as nama_supplier',
    //         'supplier.alamat as alamat_supplier',
    //         'barang.nama as nama_barang',
    //         'barang.satuan as satuan_barang'
    //     )
    //     ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
    //     ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
    //     ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
    //     ->orderByDesc('pembelian.tanggal');

    //     $totalItems = $query->count();
    //     $lastPage = ceil($totalItems / $limit);

    //     // Calculate the offset to retrieve the correct data for the current page
    //     $offset = ($currentPage - 1) * $limit;

    //     $pembelians = $query
    //     ->skip($offset)
    //     ->take($limit)
    //     ->orderByDesc('pembelian.id')
    //     ->get();

    //     $pdf = PDF::loadView('laporan.laporan-pembelian-periode.download', compact('pembelians', 'perusahaan'));

    //     $pdf->setPaper(0, 0, 609, 440, 'portrait');

    //     return $pdf->stream('filename.pdf');
    // }

    public function laporan_pembelian_periode(Request $request, $id_perusahaan)
    {
        try {
            $keywords = $request->keywords;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Pembelian::query()
            ->select(
                'pembelian.id',
                'pembelian.tanggal', 'pembelian.kode', 'pembelian.supplier', 'pembelian.operator','pembelian.jumlah','pembelian.bayar','pembelian.diskon','pembelian.tax','pembelian.lunas','pembelian.visa',
                'itempembelian.qty','itempembelian.subtotal', 'itempembelian.harga_setelah_diskon',
                'supplier.nama as nama_supplier',
                'supplier.alamat as alamat_supplier',
                'barang.nama as nama_barang',
                'barang.satuan as satuan_barang'
            )
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->orderByDesc('pembelian.tanggal');
            // ->limit(10);

            if ($startDate || $endDate) {
                $periode = [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
                $query->whereBetween('pembelian.tanggal', [$startDate, $endDate]);
            }

            $pembelians = $query
            ->orderByDesc('pembelian.tanggal')
            ->get();

            $pdf = PDF::loadView('laporan.laporan-pembelian-periode.download', compact('pembelians','perusahaan', 'periode', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');

            return $pdf->stream("laporan-periode-{$startDate}/{$endDate}.pdf");

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function laporan_hutang(Request $request, $id_perusahaan)
    {
        try {
            $keywords = $request->keywords;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Hutang::query()
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->limit(10);
            // ->limit(10);

            if ($startDate || $endDate) {
                $periode = [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
                $query->whereBetween('pembelian.tanggal', [$startDate, $endDate]);
            }

            $hutangs = $query
            ->orderByDesc('pembelian.tanggal')
            ->get();

            // echo "<pre>";
            // var_dump($hutangs); die;
            // echo "</pre>";

            $pdf = PDF::loadView('laporan.laporan-hutang.download', compact('hutangs','perusahaan', 'periode'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');

            return $pdf->stream("laporan-hutang-{$startDate}/{$endDate}.pdf");

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
