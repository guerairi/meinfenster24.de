<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Liefert aktive Rezensionen inklusive ALLER Grafiken pro Rezension,
     * sortiert nach position.
     */
    public function getActiveReviews(): array
    {
        // 1) Reviews laden
        $reviews = DB::table('rezensionen')
            ->where('active', 1)
            ->orderByRaw('bestelldatum DESC, r_id DESC')
            ->select('r_id as id', 'name', 'anzahl_sterne', 'text', 'bestelldatum')
            ->get();

        if ($reviews->isEmpty()) {
            return [];
        }

        $ids = $reviews->pluck('id')->all();
        $graphics = DB::table('rezensionen_grafiken')
            ->whereIn('r_id', $ids)
            ->whereRaw("TRIM(IFNULL(grafik, '')) <> ''")
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('r_id');


        return $reviews
            ->map(fn ($row) => $this->transform($row, $graphics->get($row->id, collect())->all()))
            ->all();
    }

    private function transform(object $row, array $graphics): array
    {
        $images = array_values(array_map(
            fn ($g) => [
                'src' => trim((string) $g->grafik),
                'alt' => trim((string) ($g->alt_attribut ?? '')) ?: null,
            ],
            $graphics
        ));

        return [
            'id'       => (int) $row->id,
            'name'     => $this->formatName((string) $row->name),
            'rating'   => max(0, min(5, (int) $row->anzahl_sterne)),
            'text'     => (string) $row->text,
            'images'   => $images,
            'date'     => $this->formatDate($row->bestelldatum),
        ];
    }

    /** Format name "Sigrid Peters" → "Sigrid P." Datenschutz */
    private function formatName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = $parts[0] ?? '';
        $last  = count($parts) > 1 ? end($parts) : '';

        return $last !== ''
            ? $first . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.'
            : $first;
    }

    private function formatDate(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $raw = trim($raw);
        $monate = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $m)) {
            return $monate[(int) $m[2]] . ' ' . $m[1];
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return (int) $m[3] . '. ' . $monate[(int) $m[2]] . ' ' . $m[1];
        }

        return $raw;
    }
}
