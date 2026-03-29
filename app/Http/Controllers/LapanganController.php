<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lapangan;

class LapanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lapangan::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('nomor_lapangan', 'like', '%' . $request->search . '%');
        }

        $lapangan = $query->orderBy('nomor_lapangan')->get();
        return view('admin.lapangan.index', compact('lapangan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.lapangan.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nomor_lapangan' => 'required|string|max:255|unique:lapangans,nomor_lapangan',
            'harga_per_jam'  => 'required|integer|min:1',
            'deskripsi'      => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'         => 'required|in:tersedia,perbaikan',
        ]);

        $namaGambar = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $namaGambar = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/lapangan'), $namaGambar);
        }

        Lapangan::create([
            'nomor_lapangan' => $request->nomor_lapangan,
            'harga_per_jam'  => $request->harga_per_jam,
            'deskripsi'      => $request->deskripsi,
            'gambar'         => $namaGambar,
            'status'         => $request->status,
        ]);

        return redirect()->route('admin.lapangan.index')->with('success', 'Lapangan berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $lapangan = Lapangan::find($id);
        return view('admin.lapangan.edit', compact('lapangan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nomor_lapangan' => 'required|string|max:255|unique:lapangans,nomor_lapangan,'.$id.',lapangan_id',
            'harga_per_jam'  => 'required|integer|min:1',
            'deskripsi'      => 'nullable|string',
            'gambar'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status'         => 'required|in:tersedia,perbaikan',
        ]);

        $lapangan = Lapangan::findOrFail($id);

        $namaGambar = $lapangan->gambar;
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($namaGambar && file_exists(public_path('images/lapangan/' . $namaGambar))) {
                unlink(public_path('images/lapangan/' . $namaGambar));
            }
            $file = $request->file('gambar');
            $namaGambar = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/lapangan'), $namaGambar);
        }

        $lapangan->update([
            'nomor_lapangan' => $request->nomor_lapangan,
            'harga_per_jam'  => $request->harga_per_jam,
            'deskripsi'      => $request->deskripsi,
            'gambar'         => $namaGambar,
            'status'         => $request->status,
        ]);

        return redirect()->route('admin.lapangan.index')->with('success', 'Lapangan berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Lapangan::destroy($id);
        return redirect()->route('admin.lapangan.index')->with('success', 'Lapangan berhasil dihapus!');
    }
}
