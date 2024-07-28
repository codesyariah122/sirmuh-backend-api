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
use App\Models\{Barang, Pembelian, Kas, Toko, Hutang, Penjualan, Piutang, Supplier, Pelanggan};
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

    // Laporan piutang
    public function laporan_piutang_by_pelanggan(Request $request, $id_perusahaan)
    {
        try {
            $pelanggan = $request->pelanggan;
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Piutang::select('piutang.id','piutang.kode', 'piutang.tanggal', 'piutang.jumlah', 'piutang.operator', 'itempiutang.jumlah_piutang', 'itempiutang.return','itempiutang.jumlah as piutang_jumlah', 'penjualan.id as id_penjualan', 'penjualan.kode as kode_penjualan','penjualan.tanggal as tanggal_penjualan', 'penjualan.jt as jatuh_tempo', 'penjualan.lunas', 'penjualan.visa', 'pelanggan.kode as kode_pelanggan', 'pelanggan.nama as nama_pelanggan')
            ->leftJoin('itempiutang', 'piutang.kode', '=', 'itempiutang.kode_piutang')
            ->leftJoin('pelanggan', 'piutang.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('penjualan', 'piutang.kd_jual', 'penjualan.kode');

            $dataPelanggan = Pelanggan::whereKode($pelanggan)->first();

            if($pelanggan) {
                $query->where('piutang.pelanggan', '=', $pelanggan);
            }

            $piutangs = $query
            ->orderByDesc('piutang.tanggal')
            ->get();

            $pdf = PDF::loadView('laporan.piutang.pelanggan.download', compact('piutangs','perusahaan', 'dataPelanggan', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');

            return $pdf->stream("laporan-piutang-pelanggan-{$pelanggan}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function laporan_piutang_by_date(Request $request, $id_perusahaan)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $pelanggan = $request->pelanggan;
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Piutang::select('piutang.id','piutang.kode', 'piutang.tanggal', 'piutang.jumlah', 'piutang.operator', 'itempiutang.jumlah_piutang', 'itempiutang.return','itempiutang.jumlah as piutang_jumlah', 'penjualan.id as id_penjualan', 'penjualan.kode as kode_penjualan','penjualan.tanggal as tanggal_penjualan', 'penjualan.jt as jatuh_tempo', 'penjualan.lunas', 'penjualan.visa', 'pelanggan.kode as kode_pelanggan', 'pelanggan.nama as nama_pelanggan')
            ->leftJoin('itempiutang', 'piutang.kode', '=', 'itempiutang.kode_piutang')
            ->leftJoin('pelanggan', 'piutang.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('penjualan', 'piutang.kd_jual', 'penjualan.kode');

            if ($startDate || $endDate) {
                $periode = [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
                $query->whereBetween('piutang.tanggal', [$startDate, $endDate]);
            }

            if($pelanggan) {
                $query->where('piutang.pelanggan', '=', $pelanggan);
            }

            $piutangs = $query
            ->orderByDesc('piutang.tanggal')
            ->get();

            $pdf = PDF::loadView('laporan.piutang.download', compact('piutangs','perusahaan', 'periode', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');

            return $pdf->stream("laporan-piutang-{$startDate}/{$endDate}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    // End of laporan piutang

    // Laporan hutang
    public function laporan_bayar_hutang_by_supplier(Request $request, $id_perusahaan)
    {
        try {
            $supplier = $request->supplier;
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Hutang::select('hutang.id', 'hutang.kode', 'hutang.kd_beli', 'hutang.tanggal', 'hutang.supplier', 'hutang.jumlah', 'hutang.bayar', 'hutang.operator', 'pembelian.id as id_pembelian', 'pembelian.kode as kode_pembelian', 'pembelian.tanggal as tanggal_pembelian', 'pembelian.jt as jatuh_tempo', 'pembelian.kode as kode_pembelian', 'pembelian.lunas', 'pembelian.visa', 'itemhutang.kode as kode_item_hutang', 'itemhutang.kode_hutang','itemhutang.jumlah_hutang as jumlah_hutang', 'supplier.nama as nama_supplier')
            ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode_hutang')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('pembelian', 'hutang.kd_beli', 'pembelian.kode');

            if($supplier) {
                $query->where('hutang.supplier', '=', $supplier);
            }

            $hutangs = $query
            ->orderByDesc('hutang.tanggal')
            ->get();

            $pdf = PDF::loadView('laporan.hutang.supplier.download', compact('hutangs','perusahaan', 'supplier', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');

            return $pdf->stream("laporan-hutang-supplier-{$supplier}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function laporan_bayar_hutang_by_date(Request $request, $id_perusahaan)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $supplier = $request->query('supplier');
            $helpers = $this->helpers;

            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Hutang::select('hutang.id', 'hutang.kode', 'hutang.kd_beli', 'hutang.tanggal', 'hutang.supplier', 'hutang.jumlah', 'hutang.bayar', 'hutang.operator', 'pembelian.id as id_pembelian', 'pembelian.kode as kode_pembelian', 'pembelian.tanggal as tanggal_pembelian', 'pembelian.jt as jatuh_tempo', 'pembelian.kode as kode_pembelian', 'pembelian.lunas', 'pembelian.visa', 'itemhutang.kode as kode_item_hutang', 'itemhutang.kode_hutang','itemhutang.jumlah_hutang as jumlah_hutang', 'supplier.nama as nama_supplier')
            ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode_hutang')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('pembelian', 'hutang.kd_beli', 'pembelian.kode');

            if ($startDate || $endDate) {
                $periode = [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ];
                $query->whereBetween('hutang.tanggal', [$startDate, $endDate]);
            }

            if($supplier) {
                $query->where('hutang.supplier', '=', $supplier);
            }

            $hutangs = $query
            ->orderByDesc('hutang.tanggal')
            ->get();

            $pdf = PDF::loadView('laporan.hutang.download', compact('hutangs','perusahaan', 'periode', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');

            return $pdf->stream("laporan-hutang-{$startDate}/{$endDate}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    // End of laporang hutang

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

            return $pdf->stream("laporan-laporan-pembelian-periode-{$startDate}/{$endDate}.pdf");

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
            $helpers = $this->helpers;

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


    // Laporan kas
    public function laporan_kas_all(Request $request, $id_perusahaan)
    {
        try {
            $param = $request->param;
            $helpers = $this->helpers;
            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Kas::whereNull('deleted_at')
            ->select('kas.id', 'kas.kode', 'kas.nama', 'kas.saldo as saldo_kas', 'kas_awal.saldo as saldo_awal')
            ->leftJoin('kas_awal', 'kas.kode', '=', 'kas_awal.kode_kas')
            ->orderBy('kas.saldo', 'DESC');

            $cashes = $query->get();
            $pdf = PDF::loadView('laporan.kas.download', compact('cashes','perusahaan', 'param', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');
            return $pdf->stream("laporan-kas-{$param}.pdf");

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
    // End of laporan kas

    // Laporan Barang & Stok Barang
    public function laporan_barang_all(Request $request, $id_perusahaan)
    {
        try {
            $param = $request->param;
            $helpers = $this->helpers;
            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Barang::join('supplier', 'barang.supplier', '=', 'supplier.kode')
            ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.toko', 'barang.last_qty', 'barang.hpp','barang.harga_toko', 'barang.harga_partai', 'barang.toko', 'barang.satuan', 'barang.kategori', 'barang.supplier', 'barang.kode_barcode', 'barang.ada_expired_date', 'barang.expired','barang.tgl_terakhir','supplier.kode as kode_supplier','supplier.nama as supplier_nama', 'supplier.alamat as supplier_alamat');

            $barangs = $query->orderByDesc('barang.id')
            ->get();

            $pdf = PDF::loadView('laporan.barang.download', compact('barangs','perusahaan', 'param', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');
            return $pdf->stream("laporan-barang-{$param}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function laporan_barang_by_keywords(Request $request, $id_perusahaan)
    {
        try {
            $keywords = $request->keywords;
            $helpers = $this->helpers;
            $perusahaan = Toko::with('setup_perusahaan')
            ->findOrFail($id_perusahaan);

            $query = Barang::join('supplier', 'barang.supplier', '=', 'supplier.kode')
            ->select('barang.id', 'barang.kode', 'barang.nama', 'barang.toko', 'barang.last_qty', 'barang.hpp','barang.harga_toko', 'barang.harga_partai', 'barang.toko', 'barang.satuan', 'barang.kategori', 'barang.supplier', 'barang.kode_barcode', 'barang.ada_expired_date', 'barang.expired','barang.tgl_terakhir','supplier.kode as kode_supplier','supplier.nama as supplier_nama', 'supplier.alamat as supplier_alamat')
            ->when($keywords, function ($query) use ($keywords) {
                return $query->where(function ($query) use ($keywords) {
                    $query->where('barang.nama', 'like', '%' . $keywords . '%')
                    ->orWhere('barang.kode', 'like', '%' . $keywords . '%');
                });
            });

            $barangs = $query->orderByDesc('barang.id')
            ->get();

            $pdf = PDF::loadView('laporan.barang.keywords.download', compact('barangs','perusahaan', 'keywords', 'helpers'));

            $pdf->setPaper(0, 0, 800, 800, 'landscape');
            return $pdf->stream("laporan-barang-keywords-{$keywords}.pdf");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    // End of laporan barang
}
