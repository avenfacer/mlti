<?php

namespace App\Http\Controllers;

use App\Models\GeneralComplaint;
use App\Models\Room;
use App\Models\User;
use App\Notifications\NewSigapComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class SigapController extends Controller
{
    public function create(Request $request)
    {
        $rooms = Room::orderBy('ruang')->get();
        $categories = GeneralComplaint::CATEGORIES;
        $recent = GeneralComplaint::with('room')
            ->where('reported_by', $request->user()->nip_lama)
            ->latest()
            ->take(3)
            ->get();

        return view('general.sigap', compact('rooms', 'categories', 'recent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'location' => 'nullable|string|max:255',
            'category' => ['required', Rule::in(array_keys(GeneralComplaint::CATEGORIES))],
            'description' => 'required|string|min:5|max:2000',
        ], [], [
            'room_id' => 'ruangan',
            'location' => 'detail lokasi',
            'category' => 'jenis kerusakan',
            'description' => 'deskripsi',
        ]);

        $complaint = GeneralComplaint::create($data + [
            'reported_by' => $request->user()->nip_lama,
            'status' => 'menunggu',
        ]);
        $complaint->load(['reporter', 'room']);

        $umumUsers = User::where('is_umum', 1)->get();
        if ($umumUsers->isNotEmpty()) {
            Notification::send($umumUsers, new NewSigapComplaint($complaint));
        }

        return redirect()->route('general.sigap.history.show', $complaint)
            ->with('success', 'Pengaduan SIGAP berhasil dikirim dengan nomor tiket '.$complaint->ticket_id.'.');
    }

    public function history(Request $request)
    {
        $complaints = GeneralComplaint::with('room')
            ->where('reported_by', $request->user()->nip_lama)
            ->latest()
            ->paginate(10);

        return view('general.sigap-history', compact('complaints'));
    }

    public function show(Request $request, GeneralComplaint $complaint)
    {
        abort_unless((int) $complaint->reported_by === (int) $request->user()->nip_lama, 403);
        $complaint->load(['room', 'handler']);

        return view('general.sigap-show', compact('complaint'));
    }

    public function adminIndex(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(GeneralComplaint::STATUSES))],
            'category' => ['nullable', Rule::in(array_keys(GeneralComplaint::CATEGORIES))],
            'q' => 'nullable|string|max:100',
        ]);

        $complaints = GeneralComplaint::with(['reporter', 'room', 'handler'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('ticket_id', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('reporter', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('room', fn ($q) => $q->where('ruang', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = GeneralComplaint::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('general.admin-complaints', [
            'complaints' => $complaints,
            'counts' => $counts,
            'statuses' => GeneralComplaint::STATUSES,
            'categories' => GeneralComplaint::CATEGORIES,
        ]);
    }

    public function adminShow(GeneralComplaint $complaint)
    {
        $complaint->load(['reporter', 'room', 'handler']);

        return view('general.admin-complaint-show', [
            'complaint' => $complaint,
            'statuses' => GeneralComplaint::STATUSES,
        ]);
    }

    public function adminUpdate(Request $request, GeneralComplaint $complaint)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(GeneralComplaint::STATUSES))],
            'admin_notes' => 'nullable|string|max:2000',
        ], [], [
            'admin_notes' => 'catatan tindak lanjut',
        ]);

        if ($data['status'] === 'ditolak' && blank($data['admin_notes'] ?? null)) {
            return back()->withErrors(['admin_notes' => 'Catatan wajib diisi saat menolak pengaduan.'])->withInput();
        }

        $data['handled_by'] = $request->user()->nip_lama;
        $data['resolved_at'] = in_array($data['status'], ['selesai', 'ditolak'], true)
            ? ($complaint->resolved_at ?? now())
            : null;

        $complaint->update($data);

        return redirect()->route('umum.complaints.show', $complaint)
            ->with('success', 'Tindak lanjut pengaduan SIGAP berhasil disimpan.');
    }
}
