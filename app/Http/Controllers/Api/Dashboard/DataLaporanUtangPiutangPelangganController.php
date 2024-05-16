<?php

namespace App\Http\Controllers\Api\Dashboard;

use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\{Pelanggan, Hutang, ItemHutang, Piutang, ItemPiutang};
use App\Models\User;
use App\Http\Resources\ResponseDataCollect;

class DataLaporanUtangPiutangPelangganController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function hitungJatuhTempo($tanggal, $tenggatWaktu) {
        if (!is_null($tanggal)) {
            $tanggal = Carbon::parse($tanggal);
            return $tanggal->addDays($tenggatWaktu)->format('Y-m-d');
        } else {
            return null;
        }
    }

    public function laporanHutangPiutang()
    {
        $tenggatWaktu = 7;
        $supplierList = [];
        $owner = User::where('role', 1)->first();

        // $hutangs = Hutang::where('operator', strtoupper($owner->name))
        // // ->whereIn('supplier', $supplierList)
        // ->whereMonth('tanggal', '>=', 7)
        // ->whereMonth('tanggal', '<=', 10)
        // ->whereYear('tanggal', '>=', 2023)
        // ->orderBy('tanggal', 'DESC')
        // ->join('supplier', 'supplier.kode', '=', 'hutang.supplier')
        // ->select("hutang.kode as hutang_kode", "tanggal", "supplier", "supplier.nama as supplier_nama", "jumlah", "kode_kas")
        // ->limit(10)
        // ->paginate(10);

        // $piutangs = Piutang::where('operator', strtoupper($owner->name))
        // ->whereMonth('tanggal', '>=', 1)
        // ->whereYear('tanggal', '>=', 2024) 
        // ->orderBy('tanggal', 'DESC')
        // ->join('pelanggan', 'pelanggan.kode', '=', 'piutang.pelanggan')
        // ->select('piutang.kode as piutang_kode', 'tanggal', 'pelanggan', 'pelanggan.nama as pelanggan_nama', 'jumlah', 'kode_kas')
        // ->limit(10)
        // ->paginate(10);

        $hutangs = Cache::remember('hutangs_data', now()->addMinutes(10), function () use ($owner, $supplierList, $tenggatWaktu) {
            return Hutang::where('operator', strtoupper($owner->name))
            ->whereIn('supplier', $supplierList)
            // ->where('jumlah', '>', 0)
            ->whereMonth('tanggal', '>=', 7)
            ->whereMonth('tanggal', '<=', 10)
            ->whereYear('tanggal', '>=', 2023)
            ->orderBy('tanggal', 'DESC')
            ->join('supplier', 'supplier.kode', '=', 'hutang.supplier')
            ->select("hutang.kode as hutang_kode", "tanggal", "supplier", "supplier.nama as supplier_nama", "jumlah", "kode_kas")
            ->limit(10)
            ->get();
        });

        $piutangs = Cache::remember('piutangs_data', now()->addMinutes(10), function () use ($owner, $tenggatWaktu) {
            return Piutang::where('operator', strtoupper($owner->name))
            // ->where('jumlah', '>', 0)
            ->whereMonth('tanggal', '>=', 1)
            ->whereYear('tanggal', '>=', 2024)
            ->orderBy('tanggal', 'DESC')
            ->join('pelanggan', 'pelanggan.kode', '=', 'piutang.pelanggan')
            ->select('piutang.kode as piutang_kode', 'tanggal', 'pelanggan', 'pelanggan.nama as pelanggan_nama', 'jumlah', 'kode_kas')
            ->limit(10)
            ->get();
        });

        $groupedHutangs = [];
        $groupedPiutangs = [];

        foreach ($hutangs as $hutang) {
            $jatuhTempo = $this->hitungJatuhTempo($hutang->tanggal, $tenggatWaktu);

            if (!is_null($jatuhTempo)) {
                $groupedHutangs[$jatuhTempo][] = [
                    'jenis' => 'HUTANG JATUH TEMPO',
                    'keterangan' => $hutang->supplier."($hutang->supplier_nama)",
                    'tanggal' => $hutang->tanggal,
                    'pihaklain' => $hutang->supplier,
                    'nama' => $hutang->supplier_nama,
                    'jumlah' => $hutang->jumlah,
                    'kode_kas' => $hutang->kode_kas,
                    'jatuh_tempo' => $jatuhTempo,
                ];
            }
        }

        foreach ($piutangs as $piutang) {
            $jatuhTempo = $this->hitungJatuhTempo($piutang->tanggal, $tenggatWaktu);

            if (!is_null($jatuhTempo)) {
                $groupedPiutangs[$jatuhTempo][] = [
                    'jenis' => "PIUTANG JATUH TEMPO",
                    'keterangan' => $piutang->pelanggan . "($piutang->pelanggan_nama)",
                    'tanggal' => $piutang->tanggal,
                    'pihaklain' => $piutang->pelanggan,
                    'nama' => $piutang->pelanggan_nama,
                    'jumlah' => $piutang->jumlah,
                    'kode_kas' => $piutang->kode_kas,
                    'jatuh_tempo' => $jatuhTempo,
                ];
            }
        }

        $result = array_merge($groupedHutangs, $groupedPiutangs);
        uasort($result, function ($a, $b) {
            $jenisA = reset($a)['jenis'];
            $jenisB = reset($b)['jenis'];

            if ($jenisA == $jenisB) {
                return 0;
            }

            return ($jenisA == 'HUTANG JATUH TEMPO') ? -1 : 1;
        });

        $supplierCount = count(array_unique($hutangs->pluck('supplier')->toArray()));
        $pelangganCount = count(array_unique($piutangs->pluck('pelanggan')->toArray()));

        // $result = $groupedHutangs;

        return response()->json([
            'success' => true,
            'message' => "List hutang piutang jatuh tempo",
            'total' => [
                'supplier' => $supplierCount,
                'pelanggan' => $pelangganCount
            ],
            'data' => [
                'hutangs' => $groupedHutangs,
                'piutangs' => $groupedPiutangs
            ]
        ]);
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
