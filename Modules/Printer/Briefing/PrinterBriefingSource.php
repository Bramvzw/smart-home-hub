<?php

namespace Modules\Printer\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Printer\Models\FilamentSpool;
use Modules\Printer\Models\PrinterPart;
use Modules\Printer\View\ViewModels\PrintingViewModel;

class PrinterBriefingSource implements BriefingSource
{
    public function __construct(
        private readonly PrintingViewModel $viewModel,
    ) {}

    public function key(): string
    {
        return 'printer';
    }

    public function label(): string
    {
        return '3D-printer';
    }

    public function priority(): int
    {
        return 50;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        $lowSpools = $this->viewModel->filament()->filter(fn (FilamentSpool $spool): bool => $spool->is_low)->values();
        $lowParts = $this->viewModel->parts()->filter(fn (PrinterPart $part): bool => $part->is_low)->values();

        if ($lowSpools->isEmpty() && $lowParts->isEmpty()) {
            return null;
        }

        $summaries = [];

        if ($lowSpools->isNotEmpty()) {
            $summaries[] = $lowSpools->count().' spool'.($lowSpools->count() === 1 ? '' : 's').' laag: '.$lowSpools
                ->map(fn (FilamentSpool $spool): string => "{$spool->material} {$spool->color_name} ({$spool->remaining_pct}%)")
                ->implode(', ');
        }

        if ($lowParts->isNotEmpty()) {
            $summaries[] = $lowParts->count().' '.($lowParts->count() === 1 ? 'onderdeel' : 'onderdelen').' laag: '.$lowParts
                ->map(fn (PrinterPart $part): string => "{$part->name} ({$part->quantity} {$part->unit})")
                ->implode(', ');
        }

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: implode(' | ', $summaries),
            data: [
                'low_spools' => $this->spoolData($lowSpools),
                'low_parts' => $this->partData($lowParts),
            ],
        );
    }

    /**
     * @param  Collection<int, FilamentSpool>  $spools
     * @return list<array<string, mixed>>
     */
    private function spoolData(Collection $spools): array
    {
        return $spools->map(fn (FilamentSpool $spool): array => [
            'material' => $spool->material,
            'color_name' => $spool->color_name,
            'remaining_pct' => $spool->remaining_pct,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, PrinterPart>  $parts
     * @return list<array<string, mixed>>
     */
    private function partData(Collection $parts): array
    {
        return $parts->map(fn (PrinterPart $part): array => [
            'name' => $part->name,
            'category' => $part->category,
            'quantity' => $part->quantity,
            'unit' => $part->unit,
        ])->values()->all();
    }
}
