<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Hutang;

class ProcessLargeRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $keywords = NULL;

    public function __construct($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Your processing logic here
        // For example:
        // Hutang::where('keywords', $this->keywords)->get();
        $query = Hutang::select('hutang.id','hutang.kode', 'hutang.tanggal','hutang.supplier','hutang.jumlah','hutang.bayar', 'hutang.operator', 'pembelian.id as id_pembelian', 'pembelian.kode as kode_pembelian','pembelian.tanggal as tanggal_pembelian', 'pembelian.jt as jatuh_tempo', 'pembelian.lunas', 'pembelian.visa', 'itemhutang.jumlah_hutang as jumlah_hutang', 'supplier.nama as nama_supplier')
            ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode_hutang')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('pembelian', 'hutang.kode', 'pembelian.kode')
            ->where('pembelian.jt', '>', 0);

            if ($this->keywords) {
                $query->where('hutang.kode', 'like', '%' . $this->keywords . '%');
            }

            $query->orderByDesc('hutang.id');

            $hutangs = $query->get();
    }
}
