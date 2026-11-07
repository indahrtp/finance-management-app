<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    private const WITH = ['coa.category', 'wallet'];    

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(self::WITH)->orderByDesc('date')->orderByDesc('id');

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('coa_id')) {
            $query->where('coa_id', $request->coa_id);
        }
        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn ($q) => $q->where('description', 'ilike', $s)->orWhere('reference_no', 'ilike', $s));
        }

        return $query->paginate($request->integer('per_page', 15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['status'] = TransactionStatus::Draft; // selalu draft, naik status lewat aksi Posting

        return response()->json(Transaction::create($data)->load(self::WITH), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        return $transaction->load(self::WITH);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, Transaction $transaction)
    {
        $this->ensureNotVoid($transaction);

        // Status tidak ikut diubah di sini, hanya lewat aksi post dan void
        $transaction->update($request->validated());

        return $transaction->load(self::WITH);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            abort(409, 'Hanya transaksi draft yang dapat dihapus. Transaksi yang sudah diposting harus dibatalkan (void).');
        }

        $this->removeFile($transaction->attachment_path);
        $transaction->delete();

        return response()->noContent();
    }

    // draft -> posted
    public function markPosted(Transaction $transaction)
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            abort(409, 'Hanya transaksi draft yang dapat diposting.');
        }

        $transaction->update(['status' => TransactionStatus::Posted]);

        return $transaction->load(self::WITH);
    }

    // posted -> void
    public function markVoid(Transaction $transaction)
    {
        if ($transaction->status !== TransactionStatus::Posted) {
            abort(409, 'Hanya transaksi yang sudah diposting yang dapat dibatalkan.');
        }

        $transaction->update(['status' => TransactionStatus::Void]);

        return $transaction->load(self::WITH);
    }

    public function uploadAttachment(Request $request, Transaction $transaction)
    {
        $this->ensureNotVoid($transaction);

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // KB
        ]);

        $file = $request->file('file');
        $newPath = $file->store('transaction-attachments'); // disk privat, nama acak
        $oldPath = $transaction->attachment_path;

        $transaction->update([
            'attachment_path' => $newPath,
            'attachment_name' => Str::limit($file->getClientOriginalName(), 200, ''),
        ]);

        $this->removeFile($oldPath);

        return $transaction->load(self::WITH);
    }

    public function downloadAttachment(Transaction $transaction)
    {
        abort_unless($transaction->attachment_path && Storage::exists($transaction->attachment_path), 404, 'Lampiran tidak ditemukan.');

        return Storage::download($transaction->attachment_path, $transaction->attachment_name);
    }

    public function deleteAttachment(Transaction $transaction)
    {
        $this->ensureNotVoid($transaction);

        $this->removeFile($transaction->attachment_path);
        $transaction->update(['attachment_path' => null, 'attachment_name' => null]);

        return $transaction->load(self::WITH);
    }

    private function ensureNotVoid(Transaction $transaction): void
    {
        abort_if($transaction->status === TransactionStatus::Void, 409, 'Transaksi void tidak dapat diubah.');
    }

    private function removeFile(?string $path): void
    {
        if ($path) {
            Storage::delete($path);
        }
    }
}
