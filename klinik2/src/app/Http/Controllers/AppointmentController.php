<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Models\Dokter;

class AppointmentController extends Controller
{
    // Menyimpan janji temu dari form frontend
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email',
            'mobile' => 'required|string|max:20',
            'date' => 'required|date',
            'time' => 'required',
            'dokter_id' => 'required|exists:dokters,id',
        ]);

        Appointment::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'date' => $request->date,
            'time' => $request->time,
            'dokter_id' => $request->dokter_id,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Janji temu berhasil dibuat!');
    }

    // Tampilkan list appointment untuk admin
    public function index()
    {
        $appointments = Appointment::latest()->get();
        return view('admin.appointments.index', compact('appointments'));
    }

    // Tampilkan detail appointment
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return view('admin.appointments.show', compact('appointment'));
    }

    // Form pendaftaran appointment
    public function create()
    {
        $dokters = Dokter::all();
        return view('frontend.appointment', compact('dokters'));
    }

    // Tampilkan form input email (frontend)
    public function paymentForm()
    {
        return view('frontend.payment');
    }

    // Cari data appointment berdasarkan email (frontend)
    public function paymentByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $appointment = Appointment::where('email', $request->email)
            ->with(['dokter', 'diagnosa.resep.obats', 'transaction'])
            ->latest()
            ->first();

        if (!$appointment) {
            return redirect()->back()->with('error', 'Data tidak ditemukan untuk email tersebut.');
        }

        $transaction = $appointment->transaction;

        if (!$transaction) {
            $transaction = Transaction::create([
                'appointment_id' => $appointment->id,
                'invoice_code' => 'INV-' . strtoupper(uniqid()),
                'amount' => 0, // Bisa dihitung dari resep dan jasa dokter di view
                'status' => 'unpaid'
            ]);
        }

        return view('frontend.payment', compact('appointment', 'transaction'));
    }

    // Tampilkan halaman pembayaran berdasarkan appointment_id (admin atau frontend)
    public function payment($id)
    {
        $appointment = Appointment::findOrFail($id);
        $transaction = Transaction::where('appointment_id', $id)->first();

        if (!$transaction) {
            $transaction = Transaction::create([
                'appointment_id' => $id,
                'invoice_code' => 'INV-' . strtoupper(uniqid()),
                'amount' => 150000,
                'status' => 'unpaid'
            ]);
        }

        return view('frontend.payment', compact('appointment', 'transaction'));
    }

    // Tandai transaksi sudah dibayar
    public function pay($id)
    {
        $transaction = Transaction::where('appointment_id', $id)->firstOrFail();
        $transaction->status = 'paid';
        $transaction->save();

        return redirect()->route('appointment.payment', $id)->with('success', 'Pembayaran berhasil!');
    }
}
