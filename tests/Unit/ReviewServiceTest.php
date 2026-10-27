<?php

namespace Tests\Unit;

use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('
            CREATE TABLE rezensionen (
                r_id INTEGER,
                active INTEGER,
                anzahl_sterne INTEGER,
                name TEXT,
                text TEXT,
                bestelldatum TEXT,
                u_id INTEGER
            )
        ');

        DB::statement('
            CREATE TABLE rezensionen_grafiken (
                id INTEGER,
                r_id INTEGER,
                grafik TEXT,
                alt_attribut TEXT,
                position INTEGER
            )
        ');
    }

    /* =========================================================
       Grundverhalten
       ========================================================= */

    public function test_returns_empty_array_when_no_reviews_exist(): void
    {
        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([], $result);
    }

    public function test_returns_active_reviews_ordered_by_date_descending(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max Mustermann', text: 'Top!', date: '2026-01-15');
        $this->insertReview(2, active: 1, rating: 4, name: 'Anna Schmidt',  text: 'Gut.', date: '2026-03-20');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertCount(2, $result);
        $this->assertSame(2, $result[0]['id']); // neueste zuerst
        $this->assertSame(1, $result[1]['id']);
    }

    /* =========================================================
       Namensformatierung
       ========================================================= */

    public function test_formats_name_as_first_name_plus_initial_of_last_name(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Sigrid Peters', text: 'Super', date: '2026-01-15');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame('Sigrid P.', $result[0]['name']);
    }

    public function test_formats_multi_word_names_using_last_word_as_surname(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Rene Bernd Holgerson', text: 'Super', date: '2026-01-15');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame('Rene H.', $result[0]['name']);
    }

    public function test_returns_only_the_first_name_when_no_surname_is_present(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Anna', text: 'Super', date: '2026-01-15');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame('Anna', $result[0]['name']);
    }

    /* =========================================================
       Datumsformatierung
       ========================================================= */

    public function test_formats_dates_in_yyyy_mm_as_month_name_plus_year(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-02');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame('Februar 2026', $result[0]['date']);
    }

    public function test_formats_dates_in_yyyy_mm_dd_as_day_month_year(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-04-20');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame('20. April 2026', $result[0]['date']);
    }

    public function test_returns_null_for_empty_or_missing_date(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.',  text: 'x', date: null);
        $this->insertReview(2, active: 1, rating: 5, name: 'Anna S.', text: 'y', date: '');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertCount(2, $result);
        $this->assertNull($result[0]['date']);
        $this->assertNull($result[1]['date']);
    }

    /* =========================================================
       Rating-Clamping
       ========================================================= */

    public function test_clamps_ratings_above_5_down_to_5(): void
    {
        $this->insertReview(1, active: 1, rating: 7, name: 'Max M.', text: 'x', date: '2026-01-01');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame(5, $result[0]['rating']);
    }

    public function test_clamps_negative_ratings_up_to_0(): void
    {
        $this->insertReview(1, active: 1, rating: -3, name: 'Max M.', text: 'x', date: '2026-01-01');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame(0, $result[0]['rating']);
    }

    /* =========================================================
       Filter: active + leerer Text
       ========================================================= */

    public function test_excludes_inactive_reviews(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.',  text: 'sichtbar',  date: '2026-01-01');
        $this->insertReview(2, active: 0, rating: 5, name: 'Anna S.', text: 'versteckt', date: '2026-01-02');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    public function test_includes_reviews_with_empty_or_whitespace_only_text(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.',  text: 'ok',  date: '2026-01-01');
        $this->insertReview(2, active: 1, rating: 5, name: 'Anna S.', text: '',    date: '2026-01-02');
        $this->insertReview(3, active: 1, rating: 5, name: 'Tom B.',  text: '   ', date: '2026-01-03');

        $result = (new ReviewService())->getActiveReviews();

        // Alle drei werden zurückgegeben – leerer Text ist erlaubt
        $this->assertCount(3, $result);

        // Neueste zuerst → id 3, 2, 1
        $this->assertSame(3, $result[0]['id']);
        $this->assertSame(2, $result[1]['id']);
        $this->assertSame(1, $result[2]['id']);

        // Text bleibt unverändert durchgereicht
        $this->assertSame('   ', $result[0]['text']);
        $this->assertSame('',    $result[1]['text']);
        $this->assertSame('ok',  $result[2]['text']);
    }

    /* =========================================================
       Bilder / Grafiken (multi-image)
       ========================================================= */

    public function test_returns_empty_images_array_when_no_graphics_exist(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-01-01');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([], $result[0]['images']);
    }

    public function test_returns_all_graphics_ordered_by_position(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-01-01');
        $this->insertGraphic(rId: 1, position: 3, grafik: 'third.jpg',  alt: 'Drittes');
        $this->insertGraphic(rId: 1, position: 1, grafik: 'first.jpg',  alt: 'Erstes');
        $this->insertGraphic(rId: 1, position: 2, grafik: 'second.jpg', alt: 'Zweites');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([
            ['src' => 'first.jpg',  'alt' => 'Erstes'],
            ['src' => 'second.jpg', 'alt' => 'Zweites'],
            ['src' => 'third.jpg',  'alt' => 'Drittes'],
        ], $result[0]['images']);
    }

    public function test_skips_graphics_with_empty_or_whitespace_path(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-01-01');
        $this->insertGraphic(rId: 1, position: 1, grafik: '',          alt: 'Leer');
        $this->insertGraphic(rId: 1, position: 2, grafik: '   ',       alt: 'Whitespace');
        $this->insertGraphic(rId: 1, position: 3, grafik: 'valid.jpg', alt: 'Gültig');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([
            ['src' => 'valid.jpg', 'alt' => 'Gültig'],
        ], $result[0]['images']);
    }

    public function test_returns_empty_images_array_when_all_graphic_paths_are_empty(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-01-01');
        $this->insertGraphic(rId: 1, position: 1, grafik: '',    alt: 'Leer');
        $this->insertGraphic(rId: 1, position: 2, grafik: '   ', alt: 'Whitespace');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([], $result[0]['images']);
    }

    public function test_image_alt_is_null_when_alt_attribut_is_empty(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-01-01');
        $this->insertGraphic(rId: 1, position: 1, grafik: 'img.jpg', alt: '');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([
            ['src' => 'img.jpg', 'alt' => null],
        ], $result[0]['images']);
    }

    public function test_image_alt_is_null_when_alt_attribut_is_null(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.', text: 'x', date: '2026-01-01');
        $this->insertGraphic(rId: 1, position: 1, grafik: 'img.jpg', alt: null);

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([
            ['src' => 'img.jpg', 'alt' => null],
        ], $result[0]['images']);
    }

    public function test_does_not_mix_graphics_between_reviews(): void
    {
        $this->insertReview(1, active: 1, rating: 5, name: 'Max M.',  text: 'x', date: '2026-01-01');
        $this->insertReview(2, active: 1, rating: 5, name: 'Anna S.', text: 'y', date: '2026-01-02');

        $this->insertGraphic(rId: 1, position: 1, grafik: 'for-review-1.jpg',   alt: 'A');
        $this->insertGraphic(rId: 2, position: 1, grafik: 'for-review-2-a.jpg', alt: 'B1');
        $this->insertGraphic(rId: 2, position: 2, grafik: 'for-review-2-b.jpg', alt: 'B2');

        $result = (new ReviewService())->getActiveReviews();

        // id 2 ist neuer → kommt zuerst
        $this->assertCount(2, $result[0]['images']);
        $this->assertSame('for-review-2-a.jpg', $result[0]['images'][0]['src']);
        $this->assertSame('for-review-2-b.jpg', $result[0]['images'][1]['src']);

        $this->assertCount(1, $result[1]['images']);
        $this->assertSame('for-review-1.jpg', $result[1]['images'][0]['src']);
    }

    /* =========================================================
       Komplettfall
       ========================================================= */

    public function test_transforms_a_full_review_row_correctly(): void
    {
        $this->insertReview(
            id: 42, active: 1, rating: 5,
            name: 'Holger Friedrich',
            text: 'Sehr gute Qualität!',
            date: '2026-04-20'
        );
        $this->insertGraphic(rId: 42, position: 1, grafik: 'holger-f.webp', alt: 'Eingebaute Fenster');

        $result = (new ReviewService())->getActiveReviews();

        $this->assertSame([[
            'id'     => 42,
            'name'   => 'Holger F.',
            'rating' => 5,
            'text'   => 'Sehr gute Qualität!',
            'images' => [
                ['src' => 'holger-f.webp', 'alt' => 'Eingebaute Fenster'],
            ],
            'date'   => '20. April 2026',
        ]], $result);
    }

    /* =========================================================
       Helpers
       ========================================================= */

    private function insertReview(
        int $id,
        int $active,
        int $rating,
        string $name,
        string $text,
        ?string $date,
    ): void {
        DB::table('rezensionen')->insert([
            'r_id'          => $id,
            'active'        => $active,
            'anzahl_sterne' => $rating,
            'name'          => $name,
            'text'          => $text,
            'bestelldatum'  => $date,
            'u_id'          => 0,
        ]);
    }

    private function insertGraphic(int $rId, int $position, string $grafik, ?string $alt = null): void
    {
        DB::table('rezensionen_grafiken')->insert([
            'id'           => $rId * 10 + $position,
            'r_id'         => $rId,
            'grafik'       => $grafik,
            'alt_attribut' => $alt,
            'position'     => $position,
        ]);
    }
}
