<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaporanPeriod
{
    public function __construct(
        public readonly ?CarbonImmutable $start = null,
        public readonly ?CarbonImmutable $end = null,
        public readonly string $label = 'Semua tanggal',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $input = $request->validate([
            'period' => ['nullable', Rule::in(['all', 'monthly', 'yearly', 'custom'])],
            'year' => ['required_if:period,monthly,yearly', 'nullable', 'integer', 'between:1900,2100'],
            'month' => ['required_if:period,monthly', 'nullable', 'integer', 'between:1,12'],
            'date_from' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        return match ($input['period'] ?? 'all') {
            'monthly' => self::month((int) $input['year'], (int) $input['month']),
            'yearly' => new self(
                CarbonImmutable::create((int) $input['year'], 1, 1)->startOfDay(),
                CarbonImmutable::create((int) $input['year'], 12, 31)->startOfDay(),
                'Tahun '.$input['year'],
            ),
            'custom' => new self(
                CarbonImmutable::parse($input['date_from'])->startOfDay(),
                CarbonImmutable::parse($input['date_to'])->startOfDay(),
                CarbonImmutable::parse($input['date_from'])->locale('id')->translatedFormat('d F Y')
                    .' – '.CarbonImmutable::parse($input['date_to'])->locale('id')->translatedFormat('d F Y'),
            ),
            default => new self,
        };
    }

    private static function month(int $year, int $month): self
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return new self($start, $start->endOfMonth()->startOfDay(), $start->locale('id')->translatedFormat('F Y'));
    }

    public function apply(Builder $query): void
    {
        if (! $this->start || ! $this->end) {
            return;
        }

        // Data lama tanpa tanggal pelaporan mengikuti tanggal dibuat, seperti di tabel UI.
        $query->where(function (Builder $dates) {
            $dates->where(function (Builder $reported) {
                $reported->where('tanggal_laporan', '>=', $this->start->toDateString())
                    ->where('tanggal_laporan', '<', $this->end->addDay()->toDateString());
            })
                ->orWhere(function (Builder $legacy) {
                    $legacy->whereNull('tanggal_laporan')
                        ->where('created_at', '>=', $this->start)
                        ->where('created_at', '<', $this->end->addDay());
                });
        });
    }
}
