@extends('layouts.app')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            {{-- Header --}}
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">Cek dan Bayar Tagihan</h2>
                <p class="text-muted">Masukkan email Anda untuk melihat detail tagihan kunjungan</p>
            </div>

            {{-- Flash Message --}}
            @if(session('error'))
                <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
            @elseif(session('success'))
                <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
            @endif

            {{-- Langkah 1: Form Email --}}
            @if(!isset($appointment))
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary"><i class="bi bi-envelope"></i> Masukkan Email</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('appointment.payment.email') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                <input type="email" name="email" class="form-control" placeholder="contoh@gmail.com" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Lihat Tagihan
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Langkah 2: Detail Tagihan --}}
                @php
                    $obats = $appointment->diagnosa->resep->obats ?? collect();
                    $hargaObat = $obats->sum('harga');
                    $hargaJasa = $appointment->dokter->harga_jasa ?? 0;
                    $total = $hargaObat + $hargaJasa;
                @endphp

                <div class="card shadow-sm bg-light">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary"><i class="bi bi-receipt"></i> Detail Tagihan</h5>
                    </div>
                    <div class="card-body">

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Nama Pasien</span>
                                <strong>{{ $appointment->nama }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Jasa Dokter</span>
                                <span>Rp {{ number_format($hargaJasa, 0, ',', '.') }}</span>
                            </li>
                            <li class="list-group-item">
                                <span>Obat:</span><br>
                                @forelse($obats as $obat)
                                    <span class="badge bg-info text-dark me-1 mt-1">{{ $obat->nama }}</span>
                                @empty
                                    <em>Tidak ada</em>
                                @endforelse
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Harga Obat</span>
                                <span>Rp {{ number_format($hargaObat, 0, ',', '.') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Tagihan</span>
                                <strong class="text-danger">Rp {{ number_format($total, 0, ',', '.') }}</strong>
                            </li>
                            <li class="list-group-item">
                                <label>Status Pembayaran</label>
                                <div class="progress mt-1" style="height: 25px;">
                                    <div class="progress-bar bg-{{ $transaction->status === 'paid' ? 'success' : 'danger' }}"
                                         role="progressbar"
                                         style="width: {{ $transaction->status === 'paid' ? '100%' : '50%' }}">
                                        {{ $transaction->status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                                    </div>
                                </div>
                            </li>
                        </ul>

                        {{-- Langkah 3: Pembayaran --}}
                        @if($transaction->status !== 'paid')
                            <form action="{{ route('appointment.pay', $appointment->id) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                                @csrf

                                {{-- Metode Pembayaran --}}
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Metode Pembayaran</label>
                                    <select name="payment_method" id="payment_method" class="form-select" required>
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="BCA">Transfer BCA</option>
                                        <option value="MANDIRI">Transfer Mandiri</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="CASH">Cash di Kasir</option>
                                    </select>
                                </div>

                                {{-- Upload Bukti Pembayaran (hanya jika non-CASH) --}}
                                <div class="mb-3" id="upload-section" style="display: none;">
                                    <label for="bukti_pembayaran" class="form-label">Upload Bukti Pembayaran <small class="text-muted">(Opsional)</small></label>
                                    <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*">
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-credit-card"></i> Bayar Sekarang
                                </button>
                            </form>

                            {{-- Script: Toggle upload input --}}
                            <script>
                                document.addEventListener("DOMContentLoaded", function () {
                                    const select = document.getElementById('payment_method');
                                    const uploadSection = document.getElementById('upload-section');

                                    select.addEventListener('change', function () {
                                        if (this.value === 'CASH' || this.value === '') {
                                            uploadSection.style.display = 'none';
                                        } else {
                                            uploadSection.style.display = 'block';
                                        }
                                    });
                                });
                            </script>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
