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
use App\Models\{Pembelian, Toko};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;
use Auth;
use PDF;

class DataLaporanView extends Controller
{
    public function laporan_pembelian_periode($id_perusahaan, $limit)
    {
        $perusahaan = Toko::with('setup_perusahaan')
        ->findOrFail($id_perusahaan);

        $query = Pembelian::query()
            ->select(
                'pembelian.id',
                'pembelian.tanggal', 'pembelian.kode', 'pembelian.supplier', 'pembelian.operator','pembelian.jumlah','pembelian.bayar','pembelian.diskon','pembelian.tax',
                'itempembelian.qty','itempembelian.subtotal', 'itempembelian.harga_setelah_diskon',
                'supplier.nama as nama_supplier',
                'supplier.alamat as alamat_supplier',
                'barang.nama as nama_barang',
                'barang.satuan as satuan_barang'
            )
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->orderByDesc('pembelian.tanggal')
            ->limit($limit);

            $pembelians = $query
                ->orderByDesc('pembelian.id')
                ->get();

                $pdf = PDF::loadView('laporan.laporan-pembelian-periode.download', compact('pembelians', 'perusahaan'));

                $pdf->setPaper(0, 0, 609, 440, 'portrait');

                return $pdf->stream('filename.pdf');
    }
}
