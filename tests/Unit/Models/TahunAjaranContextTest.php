<?php

declare(strict_types=1);

use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

describe('TahunAjaran context', function (): void {
    it('returns null when no active year and no session context', function (): void {
        session()->forget('tahun_ajaran_context');

        expect(TahunAjaran::getContext())->toBeNull();
    });

    it('falls back to the active academic year when no session context', function (): void {
        $active = TahunAjaran::factory()->active()->create();

        session()->forget('tahun_ajaran_context');

        expect(TahunAjaran::getContext()?->id)->toBe($active->id);
    });

    it('returns the session-stored academic year when set', function (): void {
        $other = TahunAjaran::factory()->create();

        TahunAjaran::setContext($other->id);

        expect(TahunAjaran::getContext()?->id)->toBe($other->id);
    });

    it('prioritises session context over the active year', function (): void {
        TahunAjaran::factory()->active()->create();
        $selected = TahunAjaran::factory()->create();

        TahunAjaran::setContext($selected->id);

        expect(TahunAjaran::getContext()?->id)->toBe($selected->id);
    });

    it('falls back to the active year when the session id no longer exists', function (): void {
        $active = TahunAjaran::factory()->active()->create();

        session(['tahun_ajaran_context' => 999999]);

        expect(TahunAjaran::getContext()?->id)->toBe($active->id);
    });

    it('clears session context when setContext is called with null', function (): void {
        $active = TahunAjaran::factory()->active()->create();
        $other = TahunAjaran::factory()->create();

        TahunAjaran::setContext($other->id);
        TahunAjaran::setContext(null);

        // Should fall back to the active year, not $other
        expect(TahunAjaran::getContext()?->id)->toBe($active->id);
        expect(session('tahun_ajaran_context'))->toBeNull();
    });

    it('queries the selected context only once per request', function (): void {
        $selected = TahunAjaran::factory()->create();

        TahunAjaran::setContext($selected->id);
        DB::flushQueryLog();
        DB::enableQueryLog();

        expect(TahunAjaran::getContext()?->id)->toBe($selected->id)
            ->and(TahunAjaran::getContext()?->id)->toBe($selected->id)
            ->and(TahunAjaran::getContext()?->id)->toBe($selected->id);

        $contextQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
            ->filter(fn (string $query): bool => str_contains($query, 'from tahun_ajaran'));

        expect($contextQueries)->toHaveCount(1);
    });

    it('invalidates the request memo when context changes', function (): void {
        $first = TahunAjaran::factory()->create();
        $second = TahunAjaran::factory()->create();

        TahunAjaran::setContext($first->id);
        expect(TahunAjaran::getContext()?->id)->toBe($first->id);

        TahunAjaran::setContext($second->id);
        expect(TahunAjaran::getContext()?->id)->toBe($second->id);
    });
});
