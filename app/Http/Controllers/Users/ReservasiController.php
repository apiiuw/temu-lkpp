<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ReservasiController extends Controller
{
    public function index()
    {
        $availableTimes = $this->getAvailableTimes();
        $occupiedSlots = collect();

        if (Schema::hasTable('reservations')) {
            $occupiedSlots = Reservation::query()
                ->select('tanggal_jam')
                ->get()
                ->groupBy(fn (Reservation $reservation) => Carbon::parse($reservation->tanggal_jam)->format('Y-m-d'))
                ->map(fn ($reservations) => $reservations
                    ->groupBy(fn (Reservation $reservation) => Carbon::parse($reservation->tanggal_jam)->format('H:i'))
                    ->filter(fn ($group) => $group->count() >= 7)
                    ->keys()
                    ->all()
                );
        }

        return view('roles.users.reservasi.index', [
            'title' => 'Reservasi',
            'availableTimes' => $availableTimes,
            'occupiedSlots' => $occupiedSlots,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_lengkap' => 'required|string|max:50',
            'jabatan' => 'required|string|max:100',
            'asal_pt' => 'required|string|max:100',
            'jenis_layanan' => 'required|in:Konsultasi SPSE / Pengadaan Elektronik,Konsultasi Regulasi Pengadaan,Konsultasi Katalog Elektronik,Konsultasi Pengadaan Langsung',
            'detail_keperluan' => 'required|string',
            'lampiran' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'tanggal_jam' => 'required|date',
        ]);

        $reservationDateTime = Carbon::parse($validatedData['tanggal_jam'])->seconds(0);
        $now = Carbon::now()->seconds(0);

        if ($reservationDateTime->lessThanOrEqualTo($now)) {
            return back()->withErrors([
                'tanggal_jam' => 'Tanggal dan jam reservasi hanya bisa dipilih mulai setelah waktu saat ini.',
            ])->withInput();
        }

        if (! $this->isAllowedReservationDay($reservationDateTime)) {
            return back()->withErrors([
                'tanggal_jam' => 'Reservasi hanya tersedia pada hari Senin sampai Kamis.',
            ])->withInput();
        }

        if (! in_array($reservationDateTime->format('H:i'), $this->getAvailableTimes(), true)) {
            return back()->withErrors([
                'tanggal_jam' => 'Jam reservasi hanya tersedia setiap 40 menit mulai 08:00 sampai 16:00 WIB, dengan jeda istirahat 12:00 sampai 13:00 WIB.',
            ])->withInput();
        }

        if (Reservation::where('tanggal_jam', $reservationDateTime->format('Y-m-d H:i:s'))->count() >= 7) {
            return back()->withErrors([
                'tanggal_jam' => 'Jadwal yang dipilih sudah penuh (maksimal 7 reservasi per slot). Silakan pilih slot lain.',
            ])->withInput();
        }

        $validatedData['tanggal_jam'] = $reservationDateTime->format('Y-m-d H:i:s');

        $kode_reservasi = 'RES-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        while (Reservation::where('kode_reservasi', $kode_reservasi)->exists()) {
            $kode_reservasi = 'RES-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        }

        $validatedData['kode_reservasi'] = $kode_reservasi;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $extension = $file->getClientOriginalExtension();
            $filename = 'LAMPIRAN-' . $kode_reservasi . '.' . $extension;
            $path = $file->storeAs('lampirans', $filename, 'public');
            $validatedData['lampiran'] = $path;
        }

        $validatedData['agent_id'] = $this->resolveAssignedAgentId($reservationDateTime);

        Reservation::create($validatedData);

        return redirect()->back()->with([
            'success' => 'Reservasi berhasil dibuat!',
            'kode_reservasi' => $kode_reservasi,
        ]);
    }

    public function download(string $kodeReservasi)
    {
        $reservation = Reservation::where('kode_reservasi', $kodeReservasi)->firstOrFail();
        $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->generate($reservation->kode_reservasi);

        $pdf = Pdf::loadView('roles.users.reservasi.pdf', [
            'reservation' => $reservation,
            'formattedSchedule' => Carbon::parse($reservation->tanggal_jam)->translatedFormat('d F Y, H:i'),
            'qrCodeDataUri' => 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg),
        ])->setPaper('a4');

        return $pdf->download('reservasi-' . $reservation->kode_reservasi . '.pdf');
    }

    private function isAllowedReservationDay(Carbon $dateTime): bool
    {
        return $dateTime->dayOfWeekIso >= 1 && $dateTime->dayOfWeekIso <= 4;
    }

    private function getAvailableTimes(): array
    {
        $times = [];
        $morningCurrent = Carbon::createFromTime(8, 0, 0);
        $morningEnd = Carbon::createFromTime(11, 20, 0);
        $afternoonCurrent = Carbon::createFromTime(13, 0, 0);
        $afternoonEnd = Carbon::createFromTime(15, 40, 0);

        while ($morningCurrent->lte($morningEnd)) {
            $times[] = $morningCurrent->format('H:i');
            $morningCurrent->addMinutes(40);
        }

        while ($afternoonCurrent->lte($afternoonEnd)) {
            $times[] = $afternoonCurrent->format('H:i');
            $afternoonCurrent->addMinutes(40);
        }

        return $times;
    }

    private function resolveAssignedAgentId(Carbon $reservationDateTime): ?int
    {
        $assignedAgentIdsInSlot = Reservation::query()
            ->where('tanggal_jam', $reservationDateTime->format('Y-m-d H:i:s'))
            ->whereNotNull('agent_id')
            ->pluck('agent_id');

        $candidateAgents = Agent::query()
            ->when(
                $assignedAgentIdsInSlot->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $assignedAgentIdsInSlot)
            )
            ->withCount([
                'reservations as same_day_reservations_count' => fn ($query) => $query
                    ->whereDate('tanggal_jam', $reservationDateTime->toDateString()),
                'reservations as active_reservations_count' => fn ($query) => $query
                    ->whereIn('status', ['pending', 'checked_in_front_desk', 'in_progress']),
            ])
            ->get();

        if ($candidateAgents->isEmpty()) {
            $candidateAgents = Agent::query()
                ->withCount([
                    'reservations as same_day_reservations_count' => fn ($query) => $query
                        ->whereDate('tanggal_jam', $reservationDateTime->toDateString()),
                    'reservations as active_reservations_count' => fn ($query) => $query
                        ->whereIn('status', ['pending', 'checked_in_front_desk', 'in_progress']),
                ])
                ->get();
        }

        return $this->pickFairAgentId($candidateAgents, $reservationDateTime);
    }

    private function pickFairAgentId(Collection $candidateAgents, Carbon $reservationDateTime): ?int
    {
        if ($candidateAgents->isEmpty()) {
            return null;
        }

        $minSameDayCount = $candidateAgents->min('same_day_reservations_count');
        $sameDayPool = $candidateAgents
            ->where('same_day_reservations_count', $minSameDayCount)
            ->values();

        $minActiveCount = $sameDayPool->min('active_reservations_count');
        $fairPool = $sameDayPool
            ->where('active_reservations_count', $minActiveCount)
            ->sortBy('id')
            ->values();

        if ($fairPool->count() === 1) {
            return $fairPool->first()->id;
        }

        $poolSignature = $fairPool->pluck('id')->implode('-');
        $seed = crc32($reservationDateTime->format('Y-m-d H:i') . '|' . $poolSignature);
        $index = abs((int) $seed) % $fairPool->count();

        return $fairPool->get($index)?->id;
    }
}
