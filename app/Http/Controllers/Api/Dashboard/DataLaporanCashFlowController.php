<?php

namespace App\Http\Controllers\Api\Dashboard;

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
use App\Models\{Pembelian};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;
use Auth;
use PDF;


class DataLaporanCashFlowController extends Controller
{
    public function all(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

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

            $cashFlowPerDate = $incomePerDate->merge($expensePerDate);

            $cashFlowPerDate = $cashFlowPerDate->sortBy('tanggal')->values();

            // Pagination
            $perPage = 10;
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $cashFlowPerDatePaginated = $cashFlowPerDate->slice($offset, $perPage)->values();

            $totalItems = $cashFlowPerDate->count();
            $offset = min($offset, $totalItems);
            $lastPage = ceil($totalItems / $perPage);

            $links = [];
            $prevPage = max($page - 1, 1);
            $prevUrl = $prevPage == $page ? null : url()->current() . '?page=' . $prevPage;
            $links[] = [
                'url' => $prevUrl,
                'label' => "&laquo; Previous",
                'active' => false,
            ];

            for ($i = 1; $i <= $lastPage; $i++) {
                $isActive = $i == $page;
                $url = $isActive ? null : url()->current() . '?page=' . $i;
                $links[] = [
                    'url' => $url,
                    'label' => $i,
                    'active' => $isActive,
                ];
            }

            $nextPage = min($page + 1, $lastPage);
            $nextUrl = $nextPage > $page ? url()->current() . '?page=' . $nextPage : null; 

            $links[] = [
                'url' => $nextUrl,
                'label' => "Next &raquo;",
                'active' => false,
            ];

            $meta = [
                'current_page' => $page,
                'from' => $offset + 1,
                'last_page' => $lastPage,
                'links' => $links,
                'path' => url()->current(),
                'per_page' => $perPage,
                'to' => min($totalItems, $offset + $perPage),
                'total' => $totalItems,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Laporan cash flow berhasil disusun',
                'data' => $cashFlowPerDatePaginated,
                'meta' => $meta
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
