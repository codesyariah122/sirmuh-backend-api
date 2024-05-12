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
use App\Models\{Pembelian, Toko, Hutang, Penjualan, Piutang};
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
                'supplier.nama as nama_supplier',
                'supplier.alamat as alamat_supplier'
            )
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->groupBy(
                'pembelian.id',
                'pembelian.tanggal',
                'pembelian.kode',
                'pembelian.supplier',
                'pembelian.operator',
                'pembelian.jumlah',
                'pembelian.bayar',
                'pembelian.diskon',
                'pembelian.tax',
                'pembelian.lunas',
                'pembelian.visa',
                'supplier.nama',
                'supplier.alamat'
            )
            ->orderByDesc('pembelian.tanggal');

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

    public function laporan_penjualan_periode(Request $request, $id_perusahaan)
    {
        try {
            $keywords = $request->keywords;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Penjualan::query()
            ->select(
                'penjualan.id',
                'penjualan.tanggal', 'penjualan.kode', 'penjualan.pelanggan', 'penjualan.operator','penjualan.jumlah','penjualan.bayar','penjualan.diskon','penjualan.tax','penjualan.lunas','penjualan.visa',
                // DB::raw('GROUP_CONCAT(itempenjualan.qty) as qty'),
                // DB::raw('GROUP_CONCAT(itempenjualan.subtotal) as subtotal'),
                // DB::raw('GROUP_CONCAT(itempenjualan.harga) as harga'),
                'pelanggan.nama as nama_pelanggan',
                'pelanggan.alamat as alamat_pelanggan'
            )
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->groupBy(
                'penjualan.id',
                'penjualan.tanggal',
                'penjualan.kode',
                'penjualan.pelanggan',
                'penjualan.operator',
                'penjualan.jumlah',
                'penjualan.bayar',
                'penjualan.diskon',
                'penjualan.tax',
                'penjualan.lunas',
                'penjualan.visa',
                'pelanggan.nama',
                'pelanggan.alamat'
            )
            ->orderByDesc('penjualan.tanggal');

            if ($startDate || $endDate) {
                $periode = [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
                $query->whereBetween('penjualan.tanggal', [$startDate, $endDate]);
            }

            $penjualans = $query
            ->orderByDesc('penjualan.tanggal')
            ->get();

            $pdf = PDF::loadView('laporan.laporan-penjualan-periode.download', compact('penjualans','perusahaan', 'periode', 'helpers'));

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

    public function laporan_cash_flow(Request $request, $id_perusahaan)
    {
        try {
            $keywords = $request->keywords;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $penjualanData = DB::table('penjualan')
            ->select('penjualan.kode', 'penjualan.tanggal', DB::raw('NULL as kd_biaya'), 'pelanggan.nama as pelanggan', 'penjualan.jumlah', DB::raw('"Penjualan" as jenis_data'))
            ->join('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->whereBetween('penjualan.tanggal', [$startDate, $endDate])
            ->get();

            $pembelianData = DB::table('pembelian')
            ->select('pembelian.kode', 'pembelian.tanggal', DB::raw('NULL as kd_biaya'), 'supplier.nama as supplier', 'pembelian.jumlah', DB::raw('"Pembelian" as jenis_data'))
            ->join('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->whereBetween('pembelian.tanggal', [$startDate, $endDate])
            ->get();

            $piutangData = DB::table('piutang')
            ->select('kode', 'tanggal', DB::raw('NULL as kd_biaya'), 'pelanggan', 'jumlah', DB::raw('"Piutang" as jenis_data'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

            $hutangData = DB::table('hutang')
            ->select('kode', 'tanggal', DB::raw('NULL as kd_biaya'), 'supplier', 'jumlah', DB::raw('"Hutang" as jenis_data'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

            $pemasukanData = DB::table('pemasukan')
            ->select('pemasukan.kode', 'pemasukan.tanggal', 'pemasukan.kd_biaya', DB::raw('NULL as pelanggan'), 'jenis_pemasukan.nama as nama_biaya', 'pemasukan.jumlah', DB::raw('"Pemasukan" as jenis_data'))
            ->join('jenis_pemasukan', 'pemasukan.kd_biaya', '=', 'jenis_pemasukan.kode')
            ->whereBetween('pemasukan.tanggal', [$startDate, $endDate])
            ->get();

            $pengeluaranData = DB::table('pengeluaran')
            ->select('pengeluaran.kode', 'pengeluaran.tanggal', 'pengeluaran.kd_biaya', DB::raw('NULL as supplier'), 'biaya.nama as nama_biaya', 'pengeluaran.jumlah', DB::raw('"Pengeluaran" as jenis_data'))
            ->join('biaya', 'pengeluaran.kd_biaya', '=', 'biaya.kode')
            ->whereBetween('pengeluaran.tanggal', [$startDate, $endDate])
            ->get();

            $cashFlowData = $penjualanData->concat($pembelianData)
            ->concat($piutangData)
            ->concat($hutangData)
            ->concat($pemasukanData)
            ->concat($pengeluaranData);

            $incomeData = collect([]);
            $expenseData = collect([]);

            foreach ($cashFlowData as $data) {
                if ($data->jenis_data === 'Penjualan' || $data->jenis_data === 'Piutang' || $data->jenis_data === 'Pemasukan') {
                    $incomeData->push($data);
                } elseif ($data->jenis_data === 'Pembelian' || $data->jenis_data === 'Hutang' || $data->jenis_data === 'Pengeluaran') {
                    $expenseData->push($data);
                }
            }

            $incomePerDate = $incomeData->groupBy('tanggal')->map(function ($group) {
                return [
                    'tanggal' => $group->first()->tanggal,
                    'total_pemasukan' => $group->sum('jumlah'),
                    'data' => $group->first->kode
                ];
            });

            $expensePerDate = $expenseData->groupBy('tanggal')->map(function ($group) {
                return [
                    'tanggal' => $group->first()->tanggal,
                    'total_pengeluaran' => $group->sum('jumlah'),
                    'data' => $group->first->kode
                ];
            });

            $cashFlows = $incomePerDate->merge($expensePerDate);

            $cashFlows = $cashFlows->sortBy('tanggal')->values();

            if ($startDate || $endDate) {
                $periode = [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
            }

            $pdf = PDF::loadView('laporan.laporan-cash-flow.download', compact('cashFlows','perusahaan', 'periode', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');
            return $pdf->stream("laporan-cash-flow-{$startDate}/{$endDate}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
